@include('backend.template.header')

{{-- @if (Auth::user()->roles[0]['name'] == 'super-admin')
    @include('backend.template.sidebar')
@endif --}}

@include('backend.template.sidebar')

{{-- @if (Auth::user()->roles[0]['name'] == 'tenaga-kerja')
    @include('backend.template.sidebar-pencari')
@endif

@if (Auth::user()->roles[0]['name'] == 'penyedia-kerja')
    @include('backend.template.sidebar-penyedia')
@endif

@if (Auth::user()->roles[0]['name'] == 'admin-bkk')
    @include('backend.template.sidebar-bkk')
@endif --}}


<body>
    @yield('content')
    @include('backend.template.footer')
    @stack('js')
</body>

</html>
