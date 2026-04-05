<?php
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
        'from' => 'smitpatel3433@gmail.com',
        'from_name' => 'Student Result Management System',
        'smtp_enabled' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => 'smitpatel3433@gmail.com',
        'smtp_password' => 'brbj bxlg wqkw mdjv',
        'smtp_timeout' => 15
    )
);
