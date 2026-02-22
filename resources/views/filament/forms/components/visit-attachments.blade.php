@php
    try {
        $record = $getRecord();
    } catch (\Throwable) {
        $record = null;
    }
    $attachments = $record?->attachments ?? collect();
@endphp
@if($attachments->isEmpty())
    <p class="text-gray-500 text-sm">لا توجد صور مرفوعة</p>
@else
    <div class="flex flex-wrap gap-4">
        @foreach($attachments as $att)
            @php $url = '/storage/' . ltrim($att->file_path, '/'); @endphp
            <a href="{{ $url }}" target="_blank" class="block">
                <img src="{{ $url }}" alt="صورة الزيارة" class="w-32 h-32 object-cover rounded-lg border border-gray-200 hover:opacity-90 transition" loading="lazy">
            </a>
        @endforeach
    </div>
    <p class="text-xs text-gray-500 mt-2">اضغط على الصورة لفتحها بحجم كامل</p>
@endif
