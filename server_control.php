<?php
header('Content-Type: application/json; charset=utf-8');

function isNodeServerRunning(): bool
{
    $host = '127.0.0.1';
    $port = 3001;
    $timeout = 1;
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp !== false) {
        fclose($fp);
        return true;
    }
    return false;
}

$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    echo json_encode(['running' => isNodeServerRunning()]);
    exit;
}

if ($action === 'start') {
    if (isNodeServerRunning()) {
        echo json_encode(['success' => true, 'message' => 'Servidor já está em execução']);
        exit;
    }

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    if ($isWindows) {
        // Windows: usar arquivo .bat para iniciar
        $batPath = __DIR__ . DIRECTORY_SEPARATOR . 'start_server.bat';
        $cmd = 'cmd.exe /C "' . escapeshellarg($batPath) . '"';
        exec($cmd);
    } else {
        // Linux/Mac
        $serverPath = __DIR__ . DIRECTORY_SEPARATOR . 'server.js';
        shell_exec('cd ' . escapeshellarg(__DIR__) . ' && nohup node ' . escapeshellarg($serverPath) . ' > /dev/null 2>&1 &');
    }

    // Aguardar um pouco para o processo iniciar
    sleep(2);

    echo json_encode(['success' => true, 'message' => 'Servidor iniciado']);
    exit;
}

if ($action === 'stop') {
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($isWindows) {
        shell_exec('taskkill /F /IM node.exe');
    } else {
        shell_exec('pkill -f "node server.js"');
    }

    echo json_encode(['success' => true, 'message' => 'Servidor parado']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Ação inválida']);
