<?php
session_start();
define('DB_FILE', __DIR__ . '/db.json');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123'); // change this
define('S6X_CLIENT_ID', 's6x_cooF6SOwGQyADAS7eywV57NBTJ1OzfwH');
define('S6X_CLIENT_SECRET', 'JOtws8IUHCcJyDQUYuOcCO3SVpVnpZ3rMgzZUmFX12jG35qLaBO9y8hNV4irx1YM');
define('S6X_BASE_URL', 'https://s6x.com.br/api/v1');
define('BOT_API_URL', 'http://localhost:3000/api'); // The bot's HTTP API endpoint

// Ensure DB file exists
if (!file_exists(DB_FILE)) {
    $initial = [
        'users' => [],
        'apostas' => [],
        'saques' => [],
        'pendingPixPayments' => [],
        'config' => [
            'mensagemBoasVindas' => 'Bem-vindo ao nosso bot de apostas!',
            'nomeBot' => 'Bot de Apostas',
            'saqueMinimo' => 10,
            'saqueMaximo' => 1000,
            'depositoMinimo' => 5,
            'depositoMaximo' => 300,
            'payoutPercentage' => 0.80
        ]
    ];
    file_put_contents(DB_FILE, json_encode($initial, JSON_PRETTY_PRINT));
}
?>