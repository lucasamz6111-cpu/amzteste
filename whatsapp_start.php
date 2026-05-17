<?php
function sendJson(array $data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

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

function runShellCommand(string $command, string &$output = null): bool
{
    if (function_exists('proc_open')) {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($command, $descriptors, $pipes);
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
            $output = trim($stdout . "\n" . $stderr);
            return $status === 0;
        }
    }

    if (function_exists('exec')) {
        $outputLines = [];
        exec($command . ' 2>&1', $outputLines, $status);
        $output = trim(implode("\n", $outputLines));
        return $status === 0;
    }

    if (function_exists('shell_exec')) {
        $output = shell_exec($command . ' 2>&1');
        return $output !== null;
    }

    $output = 'Nenhuma função de execução disponível';
    return false;
}

function findNodeExecutable(): ?string
{
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $command = $isWindows ? 'where node' : 'which node';
    $output = null;
    if (!runShellCommand($command, $output)) {
        return null;
    }

    $lines = preg_split('/\r?\n/', trim($output));
    if (empty($lines)) {
        return null;
    }

    return trim($lines[0]);
}

function startNodeServer(): array
{
    if (isNodeServerRunning()) {
        return ['success' => true, 'message' => 'Servidor já está em execução.'];
    }

    $nodePath = findNodeExecutable();
    if (empty($nodePath)) {
        return ['success' => false, 'message' => 'Node.js não encontrado no servidor.'];
    }

    $serverFile = __DIR__ . DIRECTORY_SEPARATOR . 'server.js';
    if (!file_exists($serverFile)) {
        return ['success' => false, 'message' => 'Arquivo server.js não encontrado.'];
    }

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    if ($isWindows) {
        // Windows: usar PowerShell para iniciar de forma mais robusta
        $cmd = 'powershell -Command "Start-Process -FilePath \'' . addslashes($nodePath) . '\' -ArgumentList \'' . addslashes($serverFile) . '\' -NoNewWindow -PassThru"';
    } else {
        $cmd = 'nohup "' . addslashes($nodePath) . '" "' . addslashes($serverFile) . '" > /dev/null 2>&1 &';
    }

    $output = null;
    $ok = runShellCommand($cmd, $output);
    if (!$ok) {
        return ['success' => false, 'message' => 'Falha ao iniciar o servidor: ' . $output];
    }

    // Aguarda o servidor inicializar na porta local (aumentado para 15 segundos)
    $tries = 0;
    $maxTries = 50; // 50 * 300ms = 15 segundos
    while ($tries < $maxTries) {
        if (isNodeServerRunning()) {
            return ['success' => true, 'message' => 'Servidor iniciado com sucesso.'];
        }
        usleep(300000);
        $tries++;
    }

    return ['success' => false, 'message' => 'Servidor iniciado, mas não respondeu a tempo. Tente novamente em alguns segundos.'];
}

function stopNodeServer(): array
{
    if (!isNodeServerRunning()) {
        return ['success' => true, 'message' => 'Servidor não está em execução.'];
    }

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $output = null;
    $pids = [];

    if ($isWindows) {
        if (!runShellCommand('netstat -ano | findstr ":3001"', $output)) {
            return ['success' => false, 'message' => 'Não foi possível identificar o processo na porta 3001.'];
        }
        if (preg_match_all('/\s+(\d+)\s*$/m', $output, $matches)) {
            $pids = array_unique($matches[1]);
        }
    } else {
        if (!runShellCommand('lsof -i tcp:3001 -sTCP:LISTEN -t', $output)) {
            return ['success' => false, 'message' => 'Não foi possível identificar o processo na porta 3001.'];
        }
        $lines = preg_split('/\r?\n/', trim($output));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $pids[] = $line;
            }
        }
    }

    if (empty($pids)) {
        return ['success' => false, 'message' => 'PID do Node não encontrado na porta 3001.'];
    }

    foreach ($pids as $pid) {
        if ($isWindows) {
            runShellCommand('taskkill /F /PID ' . escapeshellarg($pid), $output);
        } else {
            runShellCommand('kill -9 ' . escapeshellarg($pid), $output);
        }
    }

    $tries = 0;
    while ($tries < 10) {
        if (!isNodeServerRunning()) {
            return ['success' => true, 'message' => 'Servidor interrompido com sucesso.'];
        }
        usleep(300000);
        $tries++;
    }

    return ['success' => false, 'message' => 'Servidor não foi interrompido a tempo.'];
}

function restartNodeServer(): array
{
    $stopResult = stopNodeServer();
    if (!$stopResult['success']) {
        return $stopResult;
    }
    return startNodeServer();
}

$action = $_GET['action'] ?? 'status';
if ($action === 'status') {
    sendJson(['running' => isNodeServerRunning()]);
}

if ($action === 'check_server') {
    sendJson(['running' => isNodeServerRunning()]);
}

if ($action === 'start') {
    sendJson(startNodeServer());
}

if ($action === 'start_server') {
    sendJson(startNodeServer());
}

if ($action === 'restart') {
    sendJson(restartNodeServer());
}

http_response_code(400);
sendJson(['success' => false, 'message' => 'Ação inválida']);
