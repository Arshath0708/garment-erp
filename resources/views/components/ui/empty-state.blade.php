@props([
    'icon' => 'bi-inbox',
    'title' => 'Nothing here yet',
    'message' => null,
    'colspan' => null,
])

@if($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="text-center py-5 text-body-secondary">
            <i class="bi {{ $icon }} fs-1 d-block mb-2 opacity-50"></i>
            <div class="fw-semibold">{{ $title }}</div>
            @if($message)<div class="small">{{ $message }}</div>@endif
            {{ $slot }}
        </td>
    </tr>
@else
    <div class="text-center py-5 text-body-secondary">
        <i class="bi {{ $icon }} fs-1 d-block mb-2 opacity-50"></i>
        <div class="fw-semibold">{{ $title }}</div>
        @if($message)<div class="small">{{ $message }}</div>@endif
        {{ $slot }}
    </div>
@endif
