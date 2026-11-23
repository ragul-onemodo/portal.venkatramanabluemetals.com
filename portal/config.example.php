<?php
// Copy to config.php outside the public document root. Never commit credentials.
return [
    'dsn' => 'mysql:host=localhost;dbname=CPANELUSER_vbm;charset=utf8mb4',
    'user' => 'CPANELUSER_vbm',
    'password' => 'YOUR_DATABASE_PASSWORD',
    'https' => true,
];
