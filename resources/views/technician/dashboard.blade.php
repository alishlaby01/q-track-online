{{-- لوحة تحكم الفني: التذكرة → "في الطريق" (بدون GPS) → "وصلت وبدء العمل" (مع GPS) → "إنهاء المهمة" --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f59e0b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- منع المتصفح من تخزين الصفحة حتى تتحدث بعد Check-in --}}
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <title>لوحة الفني - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .btn-on-the-way { background: #22c55e; }
        .btn-on-the-way:hover { background: #16a34a; }
        .btn-arrive { background: #2563eb; }
        .btn-arrive:hover { background: #1d4ed8; }
        .btn-checkout { background: #f59e0b; }
        .btn-checkout:hover { background: #d97706; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-4 md:p-6 font-sans">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-wrap items-center gap-2 justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">التذاكر المكلف بها</h1>
            <div class="flex gap-2">
                <form action="{{ route('logout', [], false) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        تسجيل الخروج
                    </button>
                </form>
                <a href="{{ route('logout', [], false) }}" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition" title="استخدم هذا إذا زر تسجيل الخروج لا يعمل">
                    خروج مباشر
                </a>
            </div>
        </div>

        {{-- رسائل النجاح أو الخطأ بعد تسجيل الدخول / إنهاء المهمة --}}
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- كل تذكرة: لا زيارة → "في الطريق" | زيارة بدون arrived_at → "وصلت وبدء العمل" (GPS) | زيارة بـ arrived_at → "إنهاء المهمة" --}}
        @forelse($tickets as $ticket)
            @php $openVisit = $ticket->visits->first(); @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-4">
                <div class="flex flex-col gap-3">
                    <div>
                        <span class="text-sm font-medium text-gray-500">رقم التذكرة</span>
                        <p class="text-lg font-bold text-gray-900">{{ $ticket->ticket_number }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">اسم العميل</span>
                        <p class="text-gray-800">{{ $ticket->client_name }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">العنوان</span>
                        <p class="text-gray-800">{{ $ticket->client_address }}</p>
                        @php
                            $mapsUrl = $ticket->location_url
                                ?? (($ticket->lat && $ticket->lng) ? 'https://www.google.com/maps/dir/?api=1&destination=' . $ticket->lat . ',' . $ticket->lng : null);
                        @endphp
                        @if($mapsUrl)
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 mt-2 text-amber-600 hover:text-amber-700 text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                فتح في خرائط جوجل
                            </a>
                        @endif
                    </div>
                    @if($ticket->tasks->isNotEmpty())
                        <div>
                            <span class="text-sm font-medium text-gray-500">المهام</span>
                            <ul class="list-disc list-inside text-gray-700 mt-1 space-y-0.5">
                                @foreach($ticket->tasks as $task)
                                    <li>{{ $task->description }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="flex flex-col sm:flex-row gap-3 mt-2">
                        @if($openVisit && $openVisit->arrived_at)
                            <a href="{{ route('technician.checkout.form', [$openVisit], false) }}"
                               class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold text-white btn-checkout transition">
                                إنهاء المهمة (Check-out)
                            </a>
                        @elseif($openVisit)
                            <form action="{{ route('technician.arrive', [], false) }}" method="POST" class="arrive-form" data-visit-id="{{ $openVisit->id }}">
                                @csrf
                                <input type="hidden" name="visit_id" value="{{ $openVisit->id }}">
                                <input type="hidden" name="lat" class="geo-lat">
                                <input type="hidden" name="lng" class="geo-lng">
                                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold text-white btn-arrive transition arrive-btn">
                                    وصلت وبدء العمل
                                </button>
                            </form>
                        @else
                            <form action="{{ route('technician.on-the-way', [], false) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold text-white btn-on-the-way transition">
                                    في الطريق
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center text-gray-500">
                لا توجد تذاكر مفتوحة مكلفة بك حالياً.
            </div>
        @endforelse
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
        // عند العودة للصفحة من زر الرجوع أو بعد redirect: تأكد من عرض أحدث البيانات
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                window.location.reload();
            }
        });
        // نموذج «وصلت وبدء العمل»: قبل الإرسال نجلب إحداثيات GPS (التحقق من الوصول للموقع)
        document.querySelectorAll('.arrive-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('.arrive-btn');
                btn.disabled = true;
                btn.textContent = 'جاري جلب الموقع...';

                if (!navigator.geolocation) {
                    alert('المتصفح لا يدعم تحديد الموقع. يرجى التحديث أو استخدام متصفح آخر.');
                    btn.disabled = false;
                    btn.textContent = 'وصلت وبدء العمل';
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.querySelector('.geo-lat').value = pos.coords.latitude;
                        this.querySelector('.geo-lng').value = pos.coords.longitude;
                        this.submit();
                    },
                    (err) => {
                        alert('تعذر الحصول على الموقع: ' + (err.message || 'يرجى السماح بالوصول للموقع وإعادة المحاولة.'));
                        btn.disabled = false;
                        btn.textContent = 'وصلت وبدء العمل';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        });
    </script>
</body>
</html>
