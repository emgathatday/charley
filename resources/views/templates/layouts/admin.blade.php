<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Charley') }} Admin</title>
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        @include('admin.layouts.sidebar')

        <main class="content-wrapper">
            @yield('content')
        </main>
    </div>
</body>
</html>