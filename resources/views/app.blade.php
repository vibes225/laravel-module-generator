<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Module Generator</title>
        <link rel="stylesheet" href="{{ route('module-generator.asset', 'app.css') }}">
        <script type="module" src="{{ route('module-generator.asset', 'app.js') }}" defer></script>
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
