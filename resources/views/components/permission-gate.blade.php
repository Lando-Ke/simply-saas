@if($hasAccess())
    {{ $slot }}
@elseif($fallback)
    <div class="text-gray-500 text-sm">
        {{ $fallback }}
    </div>
@endif