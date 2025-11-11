<?php

// Cargar variables desde .env si no están cargadas aún
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $vars = parse_ini_file($envPath);
    foreach ($vars as $key => $value) {
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

return [
    'google' => [
        'clientId'     => getenv('GOOGLE_CLIENT_ID'),
        'clientSecret' => getenv('GOOGLE_CLIENT_SECRET'),
        'redirectUri'  => getenv('GOOGLE_REDIRECT_URI'),
    ],
    'facebook' => [
        'appId'        => getenv('FACEBOOK_CLIENT_ID'), 
        'appSecret'    => getenv('FACEBOOK_CLIENT_SECRET'), 
        'redirectUri'  => getenv('FACEBOOK_REDIRECT_URI'),
    ],
];
