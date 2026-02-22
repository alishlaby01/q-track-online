{{-- صفحة إنهاء الزيارة (Check-out): نموذج حالة المهام، حالة الزيارة، ملاحظات، صور مطلوبة، وإحداثيات GPS تُملأ عبر JavaScript قبل الإرسال --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>إنهاء الزيارة - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .btn-submit { background: #f59e0b; }
        .btn-submit:hover { background: #d97706; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-4 md:p-6 font-sans">
    <div class="max-w-xl mx-auto">
        <div class="flex flex-wrap items-center gap-2 justify-between mb-4">
            <a href="{{ route('technician.index', [], false) }}" class="text-amber-600 hover:underline">← العودة للوحة التحكم</a>
            <div class="flex gap-2">
                <form id="logout-form" action="{{ route('logout', [], false) }}" method="POST" class="inline">
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

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                <p class="font-medium mb-2">يرجى تصحيح الأخطاء التالية:</p>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
        @endif

        <h1 class="text-2xl font-bold text-gray-800 mb-6">إنهاء المهمة (Check-out)</h1>
        <p class="text-gray-600 mb-6">التذكرة: {{ $visit->ticket->ticket_number }}</p>

        <form id="checkout-form" action="{{ route('technician.check-out', [], false) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf
            <input type="hidden" name="visit_id" value="{{ $visit->id }}">
            {{-- lat/lng تُملآن من JavaScript (Geolocation API) قبل إرسال النموذج --}}
            <input type="hidden" name="lat" id="geo-lat">
            <input type="hidden" name="lng" id="geo-lng">

            @if($visit->ticket->tasks->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">حالة كل مهمة</label>
                    <div class="space-y-3">
                        @foreach($visit->ticket->tasks as $task)
                            <div class="p-3 border border-gray-200 rounded-lg">
                                <p class="font-medium text-gray-800 mb-2">{{ $task->description }}</p>
                                <select name="tasks[{{ $task->id }}][status]" required class="w-full rounded-lg border-gray-300 mb-2">
                                    <option value="completed">تمت</option>
                                    <option value="incomplete">لم تتم</option>
                                </select>
                                <input type="text" name="tasks[{{ $task->id }}][comment]" placeholder="تعليق (مثال: ناقص كبلات)" class="w-full rounded-lg border-gray-300" maxlength="500">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">حالة الزيارة</label>
                <select name="status" id="status" required class="w-full rounded-lg border-gray-300">
                    <option value="completed">مكتملة</option>
                    <option value="incomplete">غير مكتملة</option>
                </select>
            </div>

            <div id="failure-reason-wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">سبب الفشل <span class="text-red-500">*</span></label>
                <input type="text" name="failure_reason" class="w-full rounded-lg border-gray-300" maxlength="500">
                @error('failure_reason')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300" maxlength="1000"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">صور <span class="text-red-500">*</span></label>
                <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp" class="w-full text-sm" required>
                <p class="text-xs text-gray-500 mt-1">صورة واحدة على الأقل. PNG, JPG, WEBP. الحد الأقصى 2MB</p>
                @error('images')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" id="submit-btn" class="w-full px-6 py-3 rounded-lg font-semibold text-white btn-submit transition">
                إنهاء الزيارة
            </button>
        </form>
    </div>

    <script>
        document.getElementById('status').addEventListener('change', function() {
            document.getElementById('failure-reason-wrap').classList.toggle('hidden', this.value !== 'incomplete');
            document.querySelector('[name="failure_reason"]').required = this.value === 'incomplete';
        });

        // عند الضغط على "إنهاء الزيارة": إن لم تكن lat/lng مملوءتين نطلب الموقع من المتصفح ثم نرسل النموذج
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn = document.getElementById('submit-btn');
            const latInput = document.getElementById('geo-lat');
            const lngInput = document.getElementById('geo-lng');

            function doSubmit() {
                btn.disabled = true;
                btn.textContent = 'جاري الإرسال...';
                form.submit();
            }

            if (latInput.value && lngInput.value) {
                doSubmit();
                return;
            }

            btn.disabled = true;
            btn.textContent = 'جاري جلب الموقع...';

            if (!navigator.geolocation) {
                if (confirm('المتصفح لا يدعم تحديد الموقع. هل تريد المتابعة بدون موقع؟ (سيتم حفظ 0,0)')) {
                    latInput.value = '0';
                    lngInput.value = '0';
                    doSubmit();
                } else {
                    btn.disabled = false;
                    btn.textContent = 'إنهاء الزيارة';
                }
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    latInput.value = pos.coords.latitude;
                    lngInput.value = pos.coords.longitude;
                    doSubmit();
                },
                (err) => {
                    if (confirm('تعذر الحصول على الموقع. هل تريد المتابعة بدون موقع؟ (سيتم حفظ 0,0)')) {
                        latInput.value = '0';
                        lngInput.value = '0';
                        doSubmit();
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'إنهاء الزيارة';
                    }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });

        document.getElementById('status').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
