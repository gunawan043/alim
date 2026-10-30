<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $setting->title }} — {{ $student->name }}</title>
    @include('students.graduation-certificate._styles', ['setting' => $setting])
</head>

<body>
    @include('students.graduation-certificate._page')
</body>

</html>
