<?php

return [
    'path' => env('TERMINAL_NOTIFICATION_EXECUTABLE_PATH', 'terminal-notifier'),

    'title' => env('TERMINAL_NOTIFICATION_TITLE', env('APP_NAME')),

    'icon' => env('TERMINAL_NOTIFICATION_ICON', 'https://laravel.com/img/favicon/apple-touch-icon.png'),

    'sound' => env('TERMINAL_NOTIFICATION_SOUND', 'default'),
];
