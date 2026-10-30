<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $setting->title }} — Massal</title>
    @include('students.graduation-certificate._styles', ['setting' => $setting])
</head>

<body>
    @foreach ($pages as $page)
        @include('students.graduation-certificate._page', $page)
        @unless ($loop->last)
            <div class="page-break"></div>
        @endunless
    @endforeach
</body>

</html>
