<!DOCTYPE html>
<html lang="en">

@include('admin/utama/layout/global/top')

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{ asset('dist/img/AdminLTELogo.png') }}" alt="AdminLTELogo" height="60" width="60">
        </div>

        <!-- Navbar -->
        @include('admin/utama/layout/global/topbar')
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        @include('admin/utama/layout/global/sidebar')

        <!-- Content Wrapper. Contains page content -->

        <!-- /.content-wrapper -->
        <div class="content-wrapper" style="height:auto!important; min-height:auto!important;">
            @yield('dashcontent')
        </div>

        <!-- ./wrapper -->

        <!-- jQuery -->

        @include('admin/utama/layout/global/footer')

        @yield('footers_js')

</body>

</html>