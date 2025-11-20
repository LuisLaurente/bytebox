<?php

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
