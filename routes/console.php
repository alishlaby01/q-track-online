<?php

use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('visit:force-complete {visit_id : رقم الزيارة المراد إنهاؤها}', function () {
    $visit = Visit::find($this->argument('visit_id'));
    if (!$visit) {
        $this->error('لم يتم العثور على الزيارة المحددة.');

        return 1;
    }
    if ($visit->check_out_at) {
        $this->warn('هذه الزيارة منتهية مسبقاً.');

        return 0;
    }
    $visit->update([
        'check_out_at' => Carbon::now(),
        'end_lat'      => $visit->start_lat ?? 0,
        'end_lng'      => $visit->start_lng ?? 0,
        'status'       => 'incomplete',
        'failure_reason' => 'تم الإنهاء يدوياً عبر الأمر (visit:force-complete)',
    ]);
    $this->info('تم إنهاء الزيارة رقم '.$visit->id.' بنجاح. التذكرة: '.$visit->ticket->ticket_number);

    return 0;
})->purpose('إنهاء زيارة عالقة يدوياً (للمسؤول عند تعذر إنهائها من الواجهة)');
