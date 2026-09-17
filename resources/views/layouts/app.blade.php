<x-library-layout title="Minha conta">
    @isset($header)<div class="page-heading">{{ $header }}</div>@endisset
    {{ $slot }}
</x-library-layout>
