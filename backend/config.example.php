<?php
// Copy this file to config.php and fill in the values. config.php is git-ignored.

return [
    // true only on your computer: error details are then shown in API responses
    'debug' => false,

    'timezone' => 'Asia/Tashkent',

    // Public address of backend/public, used to build image URLs.
    // Leave empty to detect it from the request (works for OSPanel and `php -S`).
    'app_url' => '',

    'db' => [
        // OSPanel 6: the host is the MySQL module name, e.g. 'MySQL-8.0' or 'MySQL-8.2'
        // (the same server phpMyAdmin connects to). Other setups: usually '127.0.0.1'.
        'host'     => 'MySQL-8.0',
        'port'     => 3306,
        'database' => 'partfoliyo',
        'username' => 'root',
        'password' => '',
    ],

    // Sites allowed to call the API from the browser (the Vite dev server, preview and the live site)
    'cors_origins' => [
        'http://localhost:5173',
        'http://localhost:4173',
        'https://shaxzod771.github.io',
    ],

    // How long an admin stays logged in
    'token_ttl_days' => 7,

    // Max size of an uploaded project image
    'upload_max_mb' => 3,

    // New contact messages are saved in the database and emailed to `to`.
    // Gmail: turn on 2-Step Verification, then create an App Password at
    // https://myaccount.google.com/apppasswords and put it in `password` (not your normal password).
    // Leave `password` empty to only store messages in the database.
    'mail' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 465,
        'encryption' => 'ssl',   // 'ssl' for port 465, 'tls' for port 587
        'username'   => 'isomiddinovshaxzod771@gmail.com',
        'password'   => '',
        'from'       => 'isomiddinovshaxzod771@gmail.com',
        'to'         => 'isomiddinovshaxzod771@gmail.com',
    ],
];
