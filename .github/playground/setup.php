<?php

// Branche le middleware Inertia et un layout minimal dans une application Laravel fraîche.
$bootstrap = 'bootstrap/app.php';
$content = file_get_contents($bootstrap);
$content = str_replace(
    "->withMiddleware(function (Middleware \$middleware): void {\n        //\n",
    "->withMiddleware(function (Middleware \$middleware): void {\n        \$middleware->web(append: [\n            App\Http\Middleware\HandleInertiaRequests::class,\n        ]);\n",
    $content,
);
file_put_contents($bootstrap, $content);
@unlink('resources/js/app.js');
@unlink('resources/views/welcome.blade.php');
echo "setup ok\n";
