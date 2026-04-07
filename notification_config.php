<?php
// Load environment variables from .env file
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
        }
    }
}

// Configure provider credentials before using notifications in production.
return array(
    /*
    'sms' => array(
        'enabled' => true,
        'provider' => 'fast2sms',
        'api_key' => 'YOUR_FAST2SMS_API_KEY',
        'sender_id' => 'FSTSMS',
        'route' => 'v3'
    ),
    */
    'email' => array(
        'enabled' => true,
        'from' => getenv('smtp_email'),
        'from_name' => 'Student Result Management System',
        'smtp_enabled' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => getenv('smtp_email'),
        'smtp_password' => getenv('smtp_pass'),
        'smtp_timeout' => 15
    )
);
