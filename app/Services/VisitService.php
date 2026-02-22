<?php

namespace App\Services;

use App\Exceptions\GeofencingException;
use App\Exceptions\VisitException;
use App\Models\Visit;
use App\Models\VisitTaskResult;
use App\Models\Ticket;
use App\Notifications\VisitCompletedNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/**
 * خدمة الزيارات (VisitService)
 *
 * تحتوي كل منطق تسجيل الدخول للمهمة (Check-in) وإنهاء الزيارة (Check-out):
 * - التحقق من الجيوفنسينج (المسافة المسموحة من موقع التذكرة، من config/geofence أو .env GEOFENCE_MAX_METERS)
 * - إنشاء وتحديث سجلات الزيارات، نتائج المهام، والمرفقات (صور)
 * - تحديث حالة التذكرة (مثلاً closed بعد إنهاء ناجح)
 * - إرسال إشعار للمدير عند انتهاء الزيارة
 */
class VisitService
{
    /**
     * نصف قطر الأرض بالمتر (constant للأداء)
     */
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * المسافة المسموح بها للـ Check-in و Check-out (بالأمتار)
     * تُقرأ من الإعدادات؛ للتعطيل المؤقت ضع في .env: GEOFENCE_MAX_METERS=999999
     */
    private function getAllowedDistanceMeters(): int
    {
        return (int) config('geofence.max_meters', 200);
    }

    /**
     * «في الطريق»: تسجيل أن الفني متجه للموقع (بدون اشتراط GPS). العميل يرى "في الطريق".
     */
    public function recordOnTheWay(int $ticketId): Visit
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new VisitException('يجب تسجيل الدخول أولاً.');
        }

        $ticket = Ticket::findOrFail($ticketId);

        $hasOpenVisit = Visit::where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->where('status', 'incomplete')
            ->whereNull('check_out_at')
            ->exists();

        if ($hasOpenVisit) {
            throw new VisitException('لديك زيارة مفتوحة لهذه التذكرة. استخدم «وصلت وبدء العمل» أو «إنهاء المهمة».');
        }

        return Visit::create([
            'ticket_id'   => $ticketId,
            'user_id'     => $userId,
            'check_in_at' => Carbon::now(),
            'start_lat'   => null,
            'start_lng'   => null,
            'arrived_at'  => null,
            'status'      => 'incomplete',
        ]);
    }

    /**
     * «وصلت وبدء العمل»: يتحقق من المسافة (GPS) ثم يحدّث الزيارة بـ arrived_at و start_lat/lng. العميل يرى "جاري العمل".
     */
    public function recordArrived(int $visitId, float $lat, float $lng): Visit
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new VisitException('يجب تسجيل الدخول أولاً.');
        }

        $visit = Visit::with('ticket')->findOrFail($visitId);
        if ($visit->user_id !== $userId) {
            throw new VisitException('هذه الزيارة لا تخصك.');
        }
        if ($visit->check_out_at !== null) {
            throw new VisitException('تم إنهاء هذه الزيارة مسبقاً.');
        }
        if ($visit->arrived_at !== null) {
            throw new VisitException('تم تسجيل الوصول مسبقاً.');
        }

        $ticket = $visit->ticket;
        if (is_null($ticket->lat) || is_null($ticket->lng)) {
            throw new GeofencingException('موقع العميل غير محدد في التذكرة.');
        }

        $distance = $this->calculateDistance($lat, $lng, $ticket->lat, $ticket->lng);
        $allowedMeters = $this->getAllowedDistanceMeters();
        if ($distance > $allowedMeters) {
            throw new GeofencingException(
                'أنت بعيد عن موقع العميل. المسافة الحالية: ' . round($distance) . ' متر. يجب الوصول ضمن ' . $allowedMeters . ' متر.'
            );
        }

        $visit->update([
            'arrived_at' => Carbon::now(),
            'start_lat'  => $lat,
            'start_lng'  => $lng,
        ]);

        return $visit->fresh();
    }

    /**
     * معادلة Haversine لحساب المسافة بالمتر
     * محسّنة للأداء باستخدام constant و early calculations
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Early return لو الإحداثيات متطابقة
        if ($lat1 === $lat2 && $lon1 === $lon2) {
            return 0.0;
        }

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);

        $a = sin($dLat / 2) ** 2 +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }
    /**
     * تسجيل نهاية الزيارة (Check-out): يتحقق من الجيوفنسينج، يحدّث الزيارة والتذكرة، يحفظ نتائج المهام والصور، ويُرسل إشعار للمدير
     */
    public function recordCheckOut(int $visitId, array $data, array $images = [])
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new VisitException('يجب تسجيل الدخول أولاً.');
        }

        $visit = Visit::with('ticket')->findOrFail($visitId);

        // التأكد إن الزيارة تخص الفني المسجل
        if ($visit->user_id !== $userId) {
            throw new VisitException('هذه الزيارة لا تخصك. لا يمكنك إجراء Check-out لها.');
        }

        // التأكد إن الزيارة لسه مفتوحة (incomplete)
        if ($visit->status === 'completed' || $visit->check_out_at !== null) {
            throw new VisitException('هذه الزيارة مقفولة مسبقاً.');
        }

        $ticket = $visit->ticket;

        // Geofencing: التحقق من وجود إحداثيات موقع العميل
        if (is_null($ticket->lat) || is_null($ticket->lng)) {
            throw new GeofencingException('موقع العميل غير محدد في التذكرة.');
        }

        // Geofencing: التحقق من أن الفني ضمن المسافة المسموحة من موقع العميل عند Check-out
        $allowedMeters = $this->getAllowedDistanceMeters();
        $distance = $this->calculateDistance(
            (float) $data['lat'],
            (float) $data['lng'],
            $ticket->lat,
            $ticket->lng
        );
        if ($distance > $allowedMeters) {
            throw new GeofencingException(
                'أنت بعيد جداً عن موقع العميل. المسافة الحالية: ' . round($distance) . ' متر.'
            );
        }

        $status = $data['status'] ?? 'completed';

        // تحديث بيانات الزيارة
        $visit->update([
            'check_out_at'     => Carbon::now(),
            'end_lat'          => $data['lat'],
            'end_lng'          => $data['lng'],
            'status'           => $status,
            'technician_notes' => $data['notes'] ?? '',
            'failure_reason'   => $data['failure_reason'] ?? null,
        ]);

        // تحديث حالة التذكرة: Completed → closed، Incomplete → تبقى open
        if ($status === 'completed') {
            $visit->ticket->update(['status' => 'closed']);
        }

        // حفظ نتائج المهام (visit_task_results)
        foreach ($data['task_results'] ?? [] as $item) {
            VisitTaskResult::updateOrCreate(
                [
                    'visit_id'        => $visit->id,
                    'ticket_task_id'  => $item['ticket_task_id'],
                ],
                [
                    'status'  => $item['status'],
                    'comment' => $item['comment'] ?? null,
                ]
            );
        }

        // حفظ الصور المرفوعة (phase = check_out)
        if (!empty($images)) {
            foreach ($images as $image) {
                if ($image instanceof UploadedFile && $image->isValid()) {
                    $filePath = $image->store('visits/photos', 'public');
                    $visit->attachments()->create([
                        'file_path' => $filePath,
                        'file_type' => 'image',
                        'phase'     => 'check_out',
                    ]);
                }
            }
        }

        // إشعار المدير عند انتهاء الزيارة
        $manager = $visit->ticket->assignedManager;
        if ($manager) {
            $manager->notify(new VisitCompletedNotification($visit));
        }

        return $visit->load(['attachments', 'taskResults', 'ticket']);
    }
}