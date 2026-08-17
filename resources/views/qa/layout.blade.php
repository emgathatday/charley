<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Q&A Community')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
        <div class="container">
            <a href="{{ route('qa.community.index') }}" class="navbar-brand">
                <span class="brand-text font-weight-bold">Charley Q&A</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a href="{{ route('qa.community.index') }}" class="nav-link">Questions</a></li>
                    <li class="nav-item"><a href="{{ route('qa.community.ask') }}" class="nav-link">Ask Question</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container">
                @yield('header')
            </div>
        </section>
        <section class="content">
            <div class="container pb-4">
                @yield('content')
            </div>
        </section>
    </div>
</div>
</body>
</html>