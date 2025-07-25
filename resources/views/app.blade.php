<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Photobook Studio</title>
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,600">
    
    <!-- Styles (jika ada CSS yang di-compile) -->
    @if(file_exists(public_path('css/app.css')))
        <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @endif
</head>
<body>
    <div id="app"></div>
    
    <!-- Scripts -->
    <script src="{{ mix('js/app.js') }}"></script>
</body>
</html>