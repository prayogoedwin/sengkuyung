    <!-- Footer -->
    <footer class="content-footer footer bg-footer-theme">
        <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">
                {{-- ©
                <script>
                    document.write(new Date().getFullYear());
                </script>
                , made with ❤️ by
                <a href="https://themeselection.com" target="_blank" class="footer-link fw-bolder">ThemeSelection</a> & <a
                    href="https://themeselection.com" target="_blank" class="footer-link fw-bolder">PT Ezra Pratama</a> --}}
            </div>
            <div>
            Version

                <a href="https://github.com/themeselection/sneat-html-admin-template-free/issues" target="_blank"
                    class="footer-link me-4">V 1.0</a>
            </div>
        </div>
    </footer>
    <!-- / Footer -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="{{ asset('assets/nakerbisa_be/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/nakerbisa_be/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/nakerbisa_be/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/nakerbisa_be/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

    <script src="{{ asset('assets/nakerbisa_be/vendor/js/menu.js') }}"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('assets/nakerbisa_be/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/nakerbisa_be/js/main.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ asset('assets/nakerbisa_be/js/dashboards-analytics.js') }}"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Masukkan script Summernote -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <!-- datatable Js -->
    <script src="{{ asset('assets') }}/etam_be/js/plugins/jquery.dataTables.min.js"></script>
    <script src="{{ asset('assets') }}/etam_be/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="{{ asset('assets') }}/etam_be/js/pages/data-basic-custom.js"></script>
    <script src="{{ asset('assets') }}/plugins/moment.min.js"></script>
    <script src="{{ asset('assets') }}/plugins/bootstrap-datetimepicker.min.js"></script>

    <!-- Apex Chart -->
    <script src="{{ asset('assets') }}/etam_be/js/plugins/apexcharts.min.js"></script>
    <!-- custom-chart js -->
    {{-- <script src="{{ asset('assets') }}/etam_be/js/pages/dashboard-main.js"></script> --}}
    <script>
        $(document).ready(function() {
            checkCookie();
        });

        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toGMTString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function getCookie(cname) {
            var name = cname + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }

        function checkCookie() {
            var ticks = getCookie("modelopen");
            if (ticks != "") {
                ticks++;
                setCookie("modelopen", ticks, 1);
                if (ticks == "2" || ticks == "1" || ticks == "0") {
                    $('#exampleModalCenter').modal();
                }
            } else {
                // user = prompt("Please enter your name:", "");
                $('#exampleModalCenter').modal();
                ticks = 1;
                setCookie("modelopen", ticks, 1);
            }
        }
    </script>
