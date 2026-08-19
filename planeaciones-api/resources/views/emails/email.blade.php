@component('mail::layout')
{{-- Header --}}
@slot('header')
    @component('mail::header', ['url' => config('app.url')])
        <div style="text-align: center;">
            <img src="{{ asset('img/uth.webp') }}" alt="Logo Universidad" style="max-width: 150px; height: auto;">
        </div>
    @endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. @lang('Todos los derechos reservados.')
@endcomponent
@endslot
@endcomponent