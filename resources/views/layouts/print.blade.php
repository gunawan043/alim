<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Print')</title>
    <style>
        @page { margin: 1.5cm 1.5cm 1.5cm 2cm; size: A4 landscape; }
    </style>
    @yield('css')
</head>
<body>
    @yield('content')
</body>
</html>