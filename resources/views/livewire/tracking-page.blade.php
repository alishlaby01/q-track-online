{{--
    صفحة تتبع التذكرة للعميل (بدون تسجيل دخول).
    الرابط: /track/{uuid} — يُرسل للعميل من لوحة الأدمن.

    مراحل ما يظهر عند العميل:
    1) تم التعيين   — بعد تكليف الفني: اسم الفني + رقم تلفونه (بدون «في الطريق» بعد).
    2) في الطريق    — عندما يضغط الفني «في الطريق» من لوحته.
    3) جاري العمل   — عندما يسجّل الفني «وصلت وبدء العمل» (بعد التحقق من GPS).
    4) تم الانتهاء   — بعد «إنهاء المهمة» ثم نموذج التقييم إن لم يُرسل.
    wire:poll.5s = تحديث تلقائي كل 5 ثوانٍ.
--}}
<div wire:poll.5s class="max-w-2xl mx-auto px-4 py-8" wire:key="tracking-{{ $uuid }}">
    @php
        $ticket = $this->getTicket();
        $status = $this->getDisplayStatus();   // assigned | in_transit | working | completed
        $visit = $ticket?->visits->first();
        $visitEnded = $visit && $visit->check_out_at;
        $hasEvaluation = $ticket?->evaluation !== null;
    @endphp

    @if(!$ticket)
        <div class="text-center py-16 text-gray-500 dark:text-gray-400">التذكرة غير موجودة</div>
        @return
    @endif

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">تتبع التذكرة</h1>
        <button @click="dark = !dark" class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
            <span x-show="!dark"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg></span>
            <span x-show="dark" x-cloak><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
        </button>
    </div>

    {{-- رسائل النجاح/الخطأ إن وُجدت --}}
    @if (session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-xl">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-xl">{{ session('error') }}</div>
    @endif

    @php $technician = $visit?->technician ?? $ticket->assignedTechnician; @endphp
    {{-- بطاقة: اسم العميل، رقم التذكرة، والفني (اسم + تلفون) من أول مرحلة «تم التعيين» --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">اسم العميل</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $ticket->client_name }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">رقم التذكرة</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $ticket->ticket_number }}</p>
            </div>
        </div>

        @if($technician)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-600 flex items-center gap-4">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($technician->name) }}&size=64&background=6366f1&color=fff" alt="" class="w-16 h-16 rounded-full ring-2 ring-indigo-500/50">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">الفني المكلف</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $technician->name }}</p>
                    @if($technician->phone)
                        <p class="text-base text-gray-600 dark:text-gray-300 mt-1">
                            <a href="tel:{{ $technician->phone }}" class="hover:underline">{{ $technician->phone }}</a>
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- مراحل الزيارة (ستيبّر): تم التعيين → في الطريق → جاري العمل → تم الانتهاء --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6">مراحل الزيارة</h2>
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    'assigned'    => ['icon' => 'user-add', 'label' => 'تم التعيين', 'desc' => 'تم تكليف فني بالتذكرة'],
                    'in_transit'  => ['icon' => 'truck', 'label' => 'في الطريق', 'desc' => 'الفني متجه إليك'],
                    'working'     => ['icon' => 'wrench', 'label' => 'جاري العمل', 'desc' => 'الفني يعمل حالياً'],
                    'completed'   => ['icon' => 'check-circle', 'label' => 'تم الانتهاء', 'desc' => 'اكتملت الزيارة'],
                ];
                $order = ['assigned', 'in_transit', 'working', 'completed'];
                $currentIndex = array_search($status, $order);
            @endphp
            @foreach($order as $i => $stepKey)
                @php $step = $steps[$stepKey]; $active = $i <= $currentIndex; @endphp
                <div class="flex flex-col items-center flex-1 {{ $i < 3 ? 'border-e border-gray-200 dark:border-gray-600' : '' }}">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2 {{ $active ? 'bg-indigo-500 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500' }}">
                        @if($step['icon'] === 'user-add')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        @elseif($step['icon'] === 'truck')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1h1m4-1V6a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        @elseif($step['icon'] === 'wrench')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <p class="text-sm font-medium {{ $active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $step['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- نموذج التقييم: يظهر بعد انتهاء الزيارة وقبل إرسال التقييم --}}
    @if($visitEnded && !$hasEvaluation)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">قيّم الخدمة</h2>
            <form wire:submit="submitEvaluation" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تقييم الفني (بالنجوم)</label>
                    <div class="flex gap-1">
                        @foreach(range(1,5) as $i)
                            <button type="button" wire:click="$set('technician_rating', {{ $i }})" class="p-1 focus:outline-none {{ ($technician_rating ?? 3) >= $i ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تقييم الشركة</label>
                    <select wire:model="company_rating" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach(range(1,5) as $i)
                            <option value="{{ $i }}">{{ $i }} {{ $i === 5 ? 'ممتاز' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تعليق (اختياري)</label>
                    <textarea wire:model="comment" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" maxlength="1000"></textarea>
                </div>
                <button type="submit" class="w-full px-6 py-3 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    إرسال التقييم
                </button>
            </form>
        </div>
    @elseif($hasEvaluation)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 text-center text-green-600 dark:text-green-400">
            تم إرسال التقييم. شكراً لك!
        </div>
    @endif
</div>
