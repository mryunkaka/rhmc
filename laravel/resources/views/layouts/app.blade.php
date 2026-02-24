@include('layouts.partials.header')
@include('layouts.partials.sidebar')

@yield('content')

@stack('scripts')
@include('layouts.partials.footer')
