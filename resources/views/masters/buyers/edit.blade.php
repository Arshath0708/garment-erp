<x-app-layout>
    <x-slot name="header">Edit Buyer</x-slot>

    <x-ui.card title="{{ $buyer->display_code }} — {{ $buyer->company_name }}" variant="primary">
        <form action="{{ route('masters.buyers.update', $buyer) }}" method="POST">
            @csrf
            @method('PUT')
            @include('masters.buyers._form')
        </form>
    </x-ui.card>
</x-app-layout>
