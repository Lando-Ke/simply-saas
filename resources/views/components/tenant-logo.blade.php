@props(['size' => 'h-9 w-auto'])

@if($brandingService->getLogo())
    <img {{ $attributes->merge(['class' => $size]) }} 
         src="{{ $brandingService->getLogo() }}" 
         alt="{{ $brandingService->getOrganizationName() }}"
         style="max-height: 2.25rem;">
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center rounded-lg font-bold text-white ' . $size]) }}
         style="background-color: {{ $brandingService->getPrimaryColor() }}; min-width: 2.25rem; height: 2.25rem;">
        {{ $brandingService->getInitials() }}
    </div>
@endif
