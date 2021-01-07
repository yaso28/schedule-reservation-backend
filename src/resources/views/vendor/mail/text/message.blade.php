@component('mail::layout')
{{-- Body --}}
{{ $slot }}
{{-- Footer --}}
@slot('footer')
@component('mail::footer')
@endcomponent
@endslot
@endcomponent
