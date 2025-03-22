@include('backend.template.plain-header')

<body>
    @yield('content')
    @include('backend.template.footer')
    @stack('js')
</body>

</html>