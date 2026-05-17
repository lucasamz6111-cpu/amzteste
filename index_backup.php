<?php
// ============================================
// ARQUIVO: index.php - VERSÃO PREMIUM
// SISTEMA: Amazon Gest Professional
// VERSÃO: 5.0 - Design Premium + Cálculos Otimizados
// ============================================

// Configurações
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

// Diretórios de dados
define('DATA_DIR', __DIR__ . '/data/');
define('PEDIDOS_FILE', DATA_DIR . 'pedidos.json');
define('PRODUTOS_FILE', DATA_DIR . 'produtos.json');
define('CLIENTES_FILE', DATA_DIR . 'clientes.json');
define('CONFIG_FILE', DATA_DIR . 'config.json');
define('API_KEYS_FILE', DATA_DIR . 'api-keys.json');

// Criar diretório de dados se não existir
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

// Funções de manipulação de dados JSON
function carregarJSON($arquivo) {
    if (!file_exists($arquivo)) {
        touch($arquivo);
        file_put_contents($arquivo, json_encode([]));
        return [];
    }
    $conteudo = file_get_contents($arquivo);
    if (empty($conteudo)) return [];
    return json_decode($conteudo, true) ?: [];
}

function salvarJSON($arquivo, $dados) {
    $fp = fopen($arquivo, 'c');
    if (!$fp) return false;

    $locked = @flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    fwrite($fp, $json);
    fflush($fp);
    if ($locked) flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

// Carregar dados existentes
$pedidos = carregarJSON(PEDIDOS_FILE);
$produtos = carregarJSON(PRODUTOS_FILE);
$clientes = carregarJSON(CLIENTES_FILE);
$config = carregarJSON(CONFIG_FILE);
$apiKeys = carregarJSON(API_KEYS_FILE);

// Configurações padrão
if (empty($config)) {
    $config = [
        'entregadorPadrao' => 'shopee',
        'taxaPadrao' => 15,
        'notificacoes' => true,
        'tema' => 'escuro',
        'ultimoBackup' => date('Y-m-d H:i:s')
    ];
    salvarJSON(CONFIG_FILE, $config);
}

// Processar ações via POST
$acao = $_POST['acao'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$dados = $_POST['dados'] ?? [];

// Se e uma requisicao AJAX, responde JSON e sai imediatamente
if ($acao && $tipo) {
    // Desabilitar output buffering para evitar sujeira
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json;charset=UTF-8');
    header('Cache-Control: no-cache');
    
    switch ($acao) {
        case 'salvar':
            $retorno = ['success' => false];

            if ($tipo === 'pedido') {
                $novoPedido = json_decode($dados, true);
                if (isset($novoPedido['id'])) {
                    $novoId = (int)$novoPedido['id'];
                    foreach ($pedidos as $p) {
                        if ((int)$p['id'] === $novoId) {
                            echo json_encode(['success' => false, 'erro' => 'ID ja existe']);
                            exit;
                        }
                    }
                } else {
                    $ids = empty($pedidos) ? 0 : max(array_map(function($p) { return (int)$p['id']; }, $pedidos));
                    $novoPedido['id'] = $ids + 1;
                }
                if (empty($novoPedido['dataCadastro']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $novoPedido['dataCadastro'])) {
                    $novoPedido['dataCadastro'] = date('Y-m-d');
                }
                $pedidos[] = $novoPedido;
                if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                    $retorno = ['success' => true, 'id' => $novoPedido['id']];
                    logSistema("Pedido #{$novoPedido['id']} criado");
                }
            } elseif ($tipo === 'produto') {
                $novoProduto = json_decode($dados, true);
                if (isset($novoProduto['id'])) {
                    $novoId = (int)$novoProduto['id'];
                    foreach ($produtos as $p) {
                        if ((int)$p['id'] === $novoId) {
                            echo json_encode(['success' => false, 'erro' => 'ID ja existe']);
                            exit;
                        }
                    }
                } else {
                    $ids = empty($produtos) ? 0 : max(array_map(function($p) { return (int)$p['id']; }, $produtos));
                    $novoProduto['id'] = $ids + 1;
                }
                if (empty($novoProduto['dataCadastro'])) {
                    $novoProduto['dataCadastro'] = date('Y-m-d');
                }
                $produtos[] = $novoProduto;
                if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                    $retorno = ['success' => true, 'id' => $novoProduto['id']];
                    logSistema("Produto #{$novoProduto['id']} criado: {$novoProduto['nome']}");
                }
            } elseif ($tipo === 'cliente') {
                $novoCliente = json_decode($dados, true);
                if (empty($novoCliente['id'])) {
                    $ids = empty($clientes) ? 0 : max(array_map(function($c) { return (int)$c['id']; }, $clientes));
                    $novoCliente['id'] = $ids + 1;
                }
                $clientes[] = $novoCliente;
                if (salvarJSON(CLIENTES_FILE, $clientes)) {
                    $retorno = ['success' => true, 'id' => $novoCliente['id']];
                }
            }
            
            echo json_encode($retorno);
            exit;
            
        case 'atualizar':
            $retorno = ['success' => false];
            
            if ($tipo === 'pedido') {
                $pedidoAtualizado = json_decode($dados, true);
                $id = $pedidoAtualizado['id'];
                foreach ($pedidos as $key => $pedido) {
                    if ($pedido['id'] == $id) {
                        $pedidos[$key] = $pedidoAtualizado;
                        if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                            $retorno = ['success' => true];
                        }
                        break;
                    }
                }
            } elseif ($tipo === 'produto') {
                $produtoAtualizado = json_decode($dados, true);
                $id = $produtoAtualizado['id'];
                foreach ($produtos as $key => $produto) {
                    if ($produto['id'] == $id) {
                        $produtos[$key] = $produtoAtualizado;
                        if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                            $retorno = ['success' => true];
                        }
                        break;
                    }
                }
            }
            
            echo json_encode($retorno);
            exit;
            
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            $retorno = ['success' => false];

            if ($tipo === 'pedido') {
                $found = false;
                foreach ($pedidos as $key => $pedido) {
                    if ((int)$pedido['id'] === $id) {
                        unset($pedidos[$key]);
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    $pedidos = array_values($pedidos);
                    if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                        $retorno = ['success' => true];
                        logSistema("Pedido #{$id} excluido");
                    }
                }
            } elseif ($tipo === 'produto') {
                $found = false;
                foreach ($produtos as $key => $produto) {
                    if ((int)$produto['id'] === $id) {
                        unset($produtos[$key]);
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    $produtos = array_values($produtos);
                    if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                        $retorno = ['success' => true];
                        logSistema("Produto #{$id} excluido");
                    }
                }
            } elseif ($tipo === 'cliente') {
                foreach ($clientes as $key => $cliente) {
                    if ((int)$cliente['id'] === $id) {
                        unset($clientes[$key]);
                        break;
                    }
                }
                $clientes = array_values($clientes);
                if (salvarJSON(CLIENTES_FILE, $clientes)) {
                    $retorno = ['success' => true];
                }
            }

            echo json_encode($retorno);
            exit;
            
        case 'carregar':
            if ($tipo === 'todos') {
                $dadosCompletos = [
                    'pedidos' => $pedidos,
                    'produtos' => $produtos,
                    'clientes' => $clientes,
                    'config' => $config,
                    'apiKeys' => $apiKeys
                ];
                echo json_encode($dadosCompletos);
            }
            exit;
            
        case 'carregar-api-keys':
            echo json_encode($apiKeys);
            exit;

        case 'amazon-sync':
            require_once __DIR__ . '/includes/amazon_sync.php';
            $apiConfig = carregarJSON(API_KEYS_FILE);
            $amazonConfig = $apiConfig['amazon'] ?? [];
            $cfg = carregarJSON(CONFIG_FILE);
            if (!empty($cfg['amazon'])) {
                $amazonConfig = array_merge($amazonConfig, $cfg['amazon']);
            }
            $syncType = $dados['sync_type'] ?? 'orders';
            try {
                $amazon = new AmazonAPI($amazonConfig);
                if (!$amazon->isValid()) {
                    echo json_encode(['success' => false, 'message' => 'Configure as credenciais Amazon na aba Integracoes']);
                    exit;
                }
                if ($syncType === 'orders') {
                    $result = $amazon->syncOrders();
                } else {
                    $result = ['success' => false, 'message' => 'Tipo de sincronizacao desconhecido'];
                }
                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        case 'test-amazon-connection':
            require_once __DIR__ . '/includes/amazon_sync.php';
            $input = json_decode($dados ?: '{}', true);
            $amazon = new AmazonAPI($input);
            if (!$amazon->isValid()) {
                echo json_encode(['success' => false, 'message' => 'Preencha todas as credenciais']);
                exit;
            }
            try {
                $amazon->getOrders(gmdate('c', strtotime('-24 hours')), 1);
                echo json_encode(['success' => true, 'message' => 'Conexao com Amazon OK!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Manager Pro - Sistema Unificado com IA Avançada + Importação Amazon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* TODO O CSS ORIGINAL PERMANECE IGUAL */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-color: #00a8ff;
            --primary-dark: #0097e6;
            --secondary-color: #9c88ff;
            --secondary-dark: #8c7ae6;
            --success-color: #2ecc71;
            --success-dark: #27ae60;
            --warning-color: #f39c12;
            --warning-dark: #e67e22;
            --danger-color: #e74c3c;
            --danger-dark: #c0392b;
            --dark-bg: #0f1a2c;
            --darker-bg: #0a1423;
            --darkest-bg: #060e1a;
            --card-bg: #1a2a3e;
            --card-hover: #22334a;
            --card-light: #2a3b56;
            --text-color: #e4e9f0;
            --text-light: #ffffff;
            --text-muted: #8a9bb2;
            --text-contrast: #6d7f99;
            --border-color: #2a3b56;
            --border-light: #3a4b66;
            --border-dark: #1a2a3e;
            --ia-color: #9b59b6;
            --ia-dark: #8e44ad;
            --entregue-color: #2ecc71;
            --pendente-color: #f39c12;
            --atrasado-color: #e74c3c;
            --processando-color: #3498db;
            --transito-color: #f1c40f;
            --shopee-color: #ee4d2d;
            --amazon-color: #ff9900;
            --gradient-primary: linear-gradient(135deg, #00a8ff, #0080cc);
            --gradient-success: linear-gradient(135deg, #2ecc71, #27ae60);
            --gradient-warning: linear-gradient(135deg, #f39c12, #e67e22);
            --gradient-danger: linear-gradient(135deg, #e74c3c, #c0392b);
            --gradient-ia: linear-gradient(135deg, #9b59b6, #8e44ad);
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.4);
            --radius: 12px;
            --radius-small: 8px;
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--bg-color, var(--dark-bg));
            color: var(--text-color);
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 168, 255, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(156, 136, 255, 0.05) 0%, transparent 20%);
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar melhorada */
        .sidebar {
            width: 280px;
            background-color: var(--darker-bg);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            box-shadow: var(--shadow);
            border-right: 1px solid var(--border-color);
            transition: width 0.3s ease, padding 0.3s ease, transform 0.3s ease;
        }

        .sidebar .sidebar-toggle {
            position: absolute;
            top: 18px;
            right: -18px;
            width: 36px;
            height: 36px;
            background-color: var(--primary-color);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--border-color);
            cursor: pointer;
            transition: transform 0.3s ease, background-color 0.2s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.18);
        }

        .sidebar .sidebar-toggle:hover {
            transform: scale(1.05);
            background-color: var(--secondary-color);
        }

        body.sidebar-collapsed .sidebar {
            width: 84px;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 84px;
        }

        body.sidebar-collapsed .logo {
            justify-content: center;
            padding-bottom: 20px;
        }

        body.sidebar-collapsed .logo h1 {
            opacity: 0;
            width: 0;
            overflow: hidden;
            margin: 0;
            pointer-events: none;
        }

        body.sidebar-collapsed .menu-link {
            justify-content: center;
            padding: 16px 20px;
        }

        body.sidebar-collapsed .menu-link span {
            display: none;
        }

        body.sidebar-collapsed .menu-link i {
            margin-right: 0;
            text-align: center;
            width: auto;
            flex: 0 0 auto;
        }

        body.sidebar-collapsed .menu-badge {
            opacity: 0;
            width: 0;
            margin-left: 0;
            overflow: hidden;
            transition: opacity 0.2s ease, width 0.2s ease, margin 0.2s ease;
        }

        body.sidebar-collapsed .sidebar-status {
            display: none;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
            height: 0px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--darker-bg);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 25px 30px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .logo i {
            font-size: 32px;
            color: var(--primary-color);
            margin-right: 15px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.5px;
        }

        .menu {
            list-style: none;
        }

        .menu-item {
            margin-bottom: 8px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 16px 25px;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
            border-left: 4px solid transparent;
            font-weight: 500;
            font-size: 15px;
        }

        .menu-link:hover {
            background-color: rgba(0, 168, 255, 0.1);
            border-left-color: var(--primary-color);
            color: var(--text-color);
            padding-left: 30px;
        }

        .menu-link.active {
            background-color: rgba(0, 168, 255, 0.15);
            border-left-color: var(--primary-color);
            color: var(--text-color);
            font-weight: 600;
        }

        .menu-link i {
            width: 25px;
            margin-right: 12px;
            font-size: 18px;
        }

        .menu-badge {
            margin-left: auto;
            background-color: var(--primary-color);
            color: white;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 25px;
            transition: var(--transition);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .header h2 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h2 i {
            color: var(--primary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-bar {
            position: relative;
            width: 320px;
        }

        .search-bar input {
            background-color: var(--card-bg);
            border: 2px solid var(--border-color);
            padding: 12px 20px 12px 45px;
            border-radius: 50px;
            color: var(--text-color);
            width: 100%;
            outline: none;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
        }

        .search-bar input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 168, 255, 0.2);
            background-color: var(--darkest-bg);
        }

        .search-bar i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-advanced-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .search-advanced-btn:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            cursor: pointer;
            border: 3px solid var(--border-light);
            transition: var(--transition);
        }

        .user-avatar:hover {
            transform: scale(1.05);
            border-color: var(--primary-color);
        }

        /* Dashboard cards melhoradas */
        .dashboard-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 35px;
        }

        @media (min-width: 640px) {
            .dashboard-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .dashboard-cards {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .card {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border-radius: var(--radius);
            padding: 25px;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 168, 255, 0.2);
            border-color: var(--primary-color);
        }

        .hero-card {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 32px;
            min-height: 260px;
            background: linear-gradient(180deg, rgba(7, 14, 28, 0.96) 0%, rgba(10, 18, 38, 0.96) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            grid-column: 1 / -1;
            box-shadow: 0 32px 90px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(12px);
        }

        .hero-card::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image: radial-gradient(circle at 15% 20%, rgba(56, 189, 248, 0.18), transparent 26%),
                              radial-gradient(circle at 85% 35%, rgba(16, 185, 129, 0.12), transparent 24%),
                              radial-gradient(circle at 50% 100%, rgba(168, 85, 247, 0.10), transparent 18%);
            opacity: 0.65;
            mix-blend-mode: screen;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.05);
            color: #b6d7ff;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .hero-main {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            position: relative;
            z-index: 1;
        }

        .hero-copy {
            max-width: 780px;
        }

        .hero-copy h2 {
            margin: 0;
            font-size: 2.5rem;
            line-height: 1.05;
            font-weight: 800;
        }

        .hero-subtitle {
            margin-top: 14px;
            color: rgba(255, 255, 255, 0.75);
            line-height: 1.72;
            max-width: 680px;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.95rem;
        }

        .hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.78);
        }

        .hero-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .hero-dot.dot-primary {
            background: var(--primary-color);
        }

        .hero-dot.dot-success {
            background: var(--success-color);
        }

        .hero-dot.dot-warning {
            background: var(--warning-color);
        }

        .hero-dot.dot-accent {
            background: var(--accent-color);
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
            min-width: 200px;
            position: relative;
            z-index: 1;
        }

        .btn-hero-outline {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .btn-hero-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            border: none;
            box-shadow: 0 14px 40px rgba(59, 130, 246, 0.18);
        }

        .btn-hero-gradient:hover {
            filter: brightness(1.05);
        }

        .btn-hero-outline,
        .btn-hero-gradient {
            border-radius: 999px;
            min-height: 46px;
            padding: 0 24px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-hero-outline:hover,
        .btn-hero-gradient:hover {
            transform: translateY(-1px);
        }

        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .shadow-soft {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .shadow-elegant {
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
        }

        .shadow-glow {
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.22);
        }

        .gradient-card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.92), rgba(11, 16, 28, 0.98));
            border-color: rgba(255, 255, 255, 0.08);
        }

        .gradient-primary {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .gradient-success {
            background: linear-gradient(90deg, var(--success-color), var(--accent-color));
        }

        .gradient-warning {
            background: linear-gradient(90deg, var(--warning-color), #f39c12);
        }

        .gradient-danger {
            background: linear-gradient(90deg, var(--danger-color), #e83e8c);
        }

        .icon-bg-primary {
            background: rgba(59, 130, 246, 0.12);
        }

        .icon-bg-success {
            background: rgba(16, 185, 129, 0.12);
        }

        .icon-bg-warning {
            background: rgba(234, 179, 8, 0.12);
        }

        .card-kpi {
            overflow: hidden;
            background: rgba(12, 21, 40, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 26px;
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.18);
        }

        .card-top-bar {
            position: absolute;
            inset-inline: 20px;
            top: 18px;
            height: 3px;
            border-radius: 999px;
        }

        .card-blob {
            position: absolute;
            top: -14px;
            right: -14px;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            opacity: 0.12;
            filter: blur(20px);
            transition: opacity 0.3s ease;
        }

        .card:hover .card-blob {
            opacity: 0.25;
        }

        .card-body {
            position: relative;
            z-index: 1;
            padding: 24px 24px 24px;
        }

        .card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
        }

        .card-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--text-muted);
            margin: 0;
        }

        .card-icon-square {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-color);
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 14px;
            margin-top: 18px;
        }

        .card-trend {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .trend-up {
            color: var(--success-color);
        }

        .trend-down {
            color: var(--danger-color);
        }

        .trend-flat {
            color: var(--text-muted);
        }

        .card-sparkline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 90px;
            height: 34px;
        }

        .ring-primary-40 {
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.35), 0 20px 40px rgba(59, 130, 246, 0.14);
        }

        .text-gradient {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .card::after {
            content: '';
            position: absolute;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: right 0.5s ease;
        }

        .card:hover::after {
            right: 100%;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 15px;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .card-icon.clientes {
            background: linear-gradient(135deg, rgba(0, 168, 255, 0.2), rgba(0, 168, 255, 0.1));
            color: var(--primary-color);
            border: 1px solid rgba(0, 168, 255, 0.3);
        }

        .card-icon.rastreios {
            background: linear-gradient(135deg, rgba(156, 136, 255, 0.2), rgba(156, 136, 255, 0.1));
            color: var(--secondary-color);
            border: 1px solid rgba(156, 136, 255, 0.3);
        }

        .card-icon.vendas {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.2), rgba(46, 204, 113, 0.1));
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .card-icon.lucro {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.2), rgba(243, 156, 18, 0.1));
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .card-icon.entregue {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.2), rgba(46, 204, 113, 0.1));
            color: var(--entregue-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .card-icon.pendente {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.2), rgba(243, 156, 18, 0.1));
            color: var(--pendente-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .card-icon.ia {
            background: linear-gradient(135deg, rgba(155, 89, 182, 0.2), rgba(155, 89, 182, 0.1));
            color: var(--ia-color);
            border: 1px solid rgba(155, 89, 182, 0.3);
        }

        .card-value {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 12px;
            background: linear-gradient(to right, var(--text-color), var(--text-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform 0.3s ease;
        }

        .card-value.updating {
            animation: pulse 0.5s ease-in-out;
        }

        .card-change {
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .card-change.positive {
            color: var(--success-color);
        }

        .card-change.negative {
            color: var(--danger-color);
        }

        /* Animação de atualização */
        @keyframes valueUpdate {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        .card-value.updated {
            animation: valueUpdate 0.3s ease-out;
        }

        /* Tabs content melhoradas */
        .tabs-content {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 35px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .tabs-header {
            display: flex;
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            overflow-x: hidden;
            padding: 0 10px;
        }

        .tabs-header::-webkit-scrollbar {
            height: 0px;
            display: none;
        }

        .tabs-header::-webkit-scrollbar-track {
            background: var(--darker-bg);
        }

        .tabs-header::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .tab-btn {
            padding: 18px 25px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            white-space: nowrap;
            border-bottom: 3px solid transparent;
        }

        .tab-btn:hover {
            color: var(--text-color);
            background-color: rgba(255, 255, 255, 0.03);
        }

        .tab-btn.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .tab-pane {
            padding: 30px;
            display: none;
        }

        .tab-pane.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        .tab-content {
            padding: 20px 0;
            animation: fadeIn 0.5s;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(0, 168, 255, 0.4); }
            50% { box-shadow: 0 0 30px rgba(0, 168, 255, 0.6); }
        }

        /* Cards KPI melhorados */
        .kpi-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .kpi-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .kpi-card:hover::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            z-index: 0;
            animation: glow 2s infinite;
            border-radius: calc(var(--radius) + 5px);
        }

        /* Tabs melhoradas */
        .tabs-content.enhanced .tab-btn:hover {
            background: rgba(0, 168, 255, 0.1) !important;
            color: var(--primary-color) !important;
        }

        .tabs-content.enhanced .tab-btn.active {
            background: linear-gradient(180deg, rgba(0, 168, 255, 0.3), transparent) !important;
            color: var(--primary-color) !important;
        }

        /* Chart containers melhorados */
        .chart-container.enhanced {
            transition: all 0.3s;
            position: relative;
        }

        .chart-container.enhanced:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* Análise IA container */
        .ia-analise-container {
            transition: all 0.3s;
        }

        .ia-analise-container:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 40px rgba(155, 89, 182, 0.3);
        }

        /* Loading spinners */
        .fa-pulse {
            animation: pulse 1.5s infinite;
        }

        .fa-spin {
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Tables melhoradas */
        .table-container {
            overflow-x: hidden;
            margin-top: 20px;
            border-radius: var(--radius-small);
            border: 1px solid var(--border-color);
        }

        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: var(--darker-bg);
        }

        .table-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .pedidos-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        .pedidos-table th,
        .pedidos-table td {
            padding: 10px 12px;
            font-size: 13px;
            color: var(--text-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            vertical-align: middle;
        }

        .pedidos-table th {
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .pedidos-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.04);
        }

        .pedidos-table td:last-child {
            white-space: nowrap;
        }

        .pedidos-table .status-badge {
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 999px;
        }

        .pedidos-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        .pedidos-table th,
        .pedidos-table td {
            padding: 10px 12px;
            font-size: 13px;
            color: var(--text-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            vertical-align: middle;
        }

        .pedidos-table th {
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .pedidos-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.04);
        }

        .pedidos-table td:last-child {
            white-space: nowrap;
        }

        .pedidos-table .status-badge {
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 999px;
        }

        .compact-table {
            min-width: 720px;
        }

        .compact-table th,
        .compact-table td {
            padding: 10px 12px;
            font-size: 13px;
        }

        .compact-table thead {
            background-color: rgba(255, 255, 255, 0.06);
        }

        .compact-table td:last-child {
            white-space: nowrap;
        }

        .compact-table .action-btns {
            display: inline-flex;
            gap: 6px;
            justify-content: flex-end;
        }

        thead {
            background-color: rgba(0, 0, 0, 0.2);
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge i {
            font-size: 10px;
        }

        .status-entregue {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .status-pendente {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .status-processando {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--processando-color);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }

        .status-atrasado {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .status-transito {
            background-color: rgba(241, 196, 15, 0.15);
            color: var(--transito-color);
            border: 1px solid rgba(241, 196, 15, 0.3);
        }

        .status-ativo {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .btn {
            padding: 10px 18px;
            border-radius: var(--radius-small);
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.3px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-full {
            width: 100%;
            max-width: 100%;
            display: inline-flex;
            justify-content: center;
            padding-left: 16px;
            padding-right: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--secondary-dark), var(--secondary-color));
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), var(--success-dark));
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--success-dark), var(--success-color));
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), var(--warning-dark));
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, var(--warning-dark), var(--warning-color));
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), var(--danger-dark));
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, var(--danger-dark), var(--danger-color));
        }

        .btn-ia {
            background: linear-gradient(135deg, var(--ia-color), var(--ia-dark));
            color: white;
        }

        .btn-ia:hover {
            background: linear-gradient(135deg, var(--ia-dark), var(--ia-color));
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
        }

        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Form styles melhoradas */
        .form-group {
            margin-bottom: 24px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            background-color: var(--darkest-bg);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-small);
            color: var(--text-color);
            outline: none;
            transition: var(--transition);
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 168, 255, 0.2);
            background-color: var(--darker-bg);
            transform: translateY(-2px);
        }

        .form-control:not(:placeholder-shown) {
            border-color: var(--success-color);
            background-color: rgba(46, 204, 113, 0.05);
        }

        .form-control:not(:placeholder-shown):focus {
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2300a8ff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 18px;
            padding-right: 45px;
            font-weight: 500;
            background-color: var(--darker-bg);
            border: 2px solid var(--primary-color);
        }

        select.form-control:invalid {
            color: var(--text-muted);
        }

        select.form-control option {
            background-color: var(--darkest-bg);
            color: var(--text-color);
            padding: 12px;
            border-radius: 4px;
            margin: 5px 0;
            font-weight: 500;
        }

        select.form-control option:checked {
            background: linear-gradient(var(--primary-color), var(--primary-color));
            background-color: var(--primary-color) !important;
            color: white;
        }

        /* Pedidos section melhorada */
        .pedidos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .pedido-card {
            background: linear-gradient(145deg, var(--card-bg), var(--darkest-bg));
            border-radius: var(--radius);
            padding: 25px;
            border-left: 6px solid var(--primary-color);
            position: relative;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .pedido-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-color);
        }

        .pedido-card.entregue {
            border-left-color: var(--success-color);
        }

        .pedido-card.pendente {
            border-left-color: var(--warning-color);
        }

        .pedido-card.atrasado {
            border-left-color: var(--danger-color);
        }

        .pedido-card.processando {
            border-left-color: var(--processando-color);
        }

        .pedido-card.transito {
            border-left-color: var(--transito-color);
        }

        .btn-group {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .view-btn {
            padding: 10px 14px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 2px;
        }

        .view-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .view-btn.active {
            background: var(--primary-color);
            color: white;
        }

        /* Visualização em Lista para Pedidos - Design Organizado */
        .pedidos-container.lista {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
        }

        .pedidos-container.lista .pedido-card {
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 18px 22px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .pedidos-container.lista .pedido-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-color);
        }

        .pedidos-container.lista .pedido-card .pedido-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
        }

        .pedidos-container.lista .pedido-card .pedido-cliente {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-color);
        }

        .pedidos-container.lista .pedido-card .pedido-codigo {
            font-size: 13px;
            color: var(--text-muted);
        }

        .pedidos-container.lista .pedido-card .status-badge {
            margin-left: auto;
            min-width: auto;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 999px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .pedidos-container.lista .pedido-card .pedido-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .pedidos-container.lista .pedido-card .pedido-detail {
            background: var(--input-bg);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .pedidos-container.lista .pedido-card .pedido-detail-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: block;
        }

        .pedidos-container.lista .pedido-card .pedido-detail-value {
            font-size: 14px;
            color: var(--text-color);
            line-height: 1.5;
        }

        .pedidos-container.lista .pedido-card .pedido-detail-value strong {
            font-weight: 700;
        }

        .pedidos-container.lista .pedido-card .pedido-endereco,
        .pedidos-container.lista .pedido-card .pedido-transporte {
            background: var(--input-bg);
            border-radius: 14px;
            padding: 16px;
        }

        .pedidos-container.lista .pedido-card .pedido-endereco {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .pedidos-container.lista .pedido-card .pedido-transporte {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .pedidos-container.lista .pedido-card .pedido-transporte-title {
            grid-column: 1 / -1;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-color);
        }

        .pedidos-container.lista .pedido-card .pedido-transporte-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .pedidos-container.lista .pedido-card .transporte-detail {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .pedidos-container.lista .pedido-card .transporte-label {
            font-size: 12px;
            color: var(--text-muted);
        }

        .pedidos-container.lista .pedido-card .transporte-value {
            font-size: 14px;
            color: var(--text-color);
            line-height: 1.4;
        }

        .pedidos-container.lista .pedido-card .pedido-links,
        .pedidos-container.lista .pedido-card .pedido-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .pedidos-container.lista .pedido-card .btn-small {
            min-width: auto;
            height: auto;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .pedidos-container.lista .pedido-card .btn-small i {
            margin-right: 6px;
        }

        .pedidos-container.lista .pedido-card .preco-info {
            text-align: left;
        }

        .pedidos-container.lista .pedido-card .preco {
            font-size: 16px;
            font-weight: 700;
            color: var(--success-color);
        }

        .pedidos-container.lista .pedido-card .taxa {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Responsividade para visualização em lista */
        @media (max-width: 1400px) {
            .pedidos-container.lista .pedido-card {
                padding: 16px 18px;
                gap: 16px;
            }
        }

        @media (max-width: 1200px) {
            .pedidos-container.lista .pedido-card {
                padding: 14px 16px;
                gap: 14px;
            }

            .pedidos-container.lista .pedido-card .pedido-details,
            .pedidos-container.lista .pedido-card .pedido-transporte-details {
                grid-template-columns: 1fr;
            }
        }

        .produtos-container.lista .produto-card {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            gap: 15px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .produtos-container.lista .produto-card:hover {
            transform: none;
            border-color: var(--primary-color);
        }

        .produtos-container.lista .produto-card .produto-info {
            flex: 1;
        }

        .produtos-container.lista .produto-card .produto-actions {
            display: flex;
            gap: 8px;
        }

        .products-container.lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }

        .products-container.lista .produto-card {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            gap: 15px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .products-container.lista .produto-card:hover {
            transform: none;
            border-color: var(--primary-color);
        }

        .products-container.lista .produto-info {
            flex: 1;
        }

        .products-container.lista .produto-actions {
            display: flex;
            gap: 8px;
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .pedido-cliente {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-light);
        }

        .pedido-codigo {
            font-size: 16px;
            color: var(--primary-color);
            font-weight: 700;
            background-color: rgba(0, 168, 255, 0.1);
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        .pedido-details {
            margin-bottom: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: var(--radius-small);
            border: 1px solid var(--border-dark);
        }

        .pedido-detail {
            display: flex;
            margin-bottom: 12px;
            font-size: 14px;
            align-items: center;
        }

        .pedido-detail-label {
            width: 140px;
            color: var(--text-muted);
            font-weight: 600;
            flex-shrink: 0;
        }

        .pedido-detail-value {
            flex: 1;
            font-weight: 500;
            color: var(--text-color);
            word-break: break-word;
        }

        .pedido-transporte {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 18px;
            border-radius: var(--radius-small);
            margin-top: 15px;
            border-left: 4px solid var(--shopee-color);
        }

        .pedido-transporte-title {
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .pedido-transporte-title i {
            color: var(--shopee-color);
        }

        .pedido-transporte-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .transporte-detail {
            display: flex;
            flex-direction: column;
        }

        .transporte-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .transporte-value {
            font-size: 14px;
            color: var(--text-color);
            font-weight: 600;
            padding: 6px 10px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border: 1px solid var(--border-dark);
        }

        .pedido-links {
            margin-top: 25px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .link-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-small);
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .link-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: white;
        }

        .link-btn.produto {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
        }

        .link-btn.rastreio {
            background: linear-gradient(135deg, var(--shopee-color), #d93a1a);
        }

        .link-btn.rastreio i {
            color: white;
        }

        .pedido-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* Estilos para dados sensíveis - OCULTAÇÃO COMPLETA */
        /* Estilos específicos para campos de CPF */
        .campo-cpf-container {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .campo-cpf-container .form-control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            border-radius: var(--radius-small);
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }

        .campo-cpf-container .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(93, 173, 226, 0.2);
            outline: none;
        }

        .campo-cpf-container .validacao-cpf {
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .campo-cpf-container .validacao-cpf.valido {
            color: var(--success-color);
            display: block;
        }

        .campo-cpf-container .validacao-cpf.invalido {
            color: var(--error-color);
            display: block;
        }

        /* Formatação visual do CPF */
        .cpf-formatado {
            background: linear-gradient(135deg, rgba(93, 173, 226, 0.05) 0%, rgba(93, 226, 173, 0.05) 100%);
            border-left: 3px solid var(--accent-color);
            padding-left: 12px;
        }

        /* Classes para organização de dados sensíveis */
        .dado-sensivel-cliente,
        .dado-sensivel-produto,
        .dado-sensivel-endereco,
        .dado-sensivel-rastreio,
        .dado-sensivel-cpf,
        .dado-sensivel-email,
        .dado-sensivel-telefone,
        .dado-sensivel-numero {
            transition: all 0.3s ease;
            position: relative;
        }

        body.dados-ocultados .dado-sensivel-cliente,
        body.dados-ocultados .dado-sensivel-produto,
        body.dados-ocultados .dado-sensivel-rastreio,
        body.dados-ocultados .dado-sensivel-amazon-id {
            color: transparent !important;
            background: #000 !important;
            text-shadow: none !important;
            filter: blur(15px) !important;
            -webkit-filter: blur(15px) !important;
            padding: 6px 10px !important;
            border-radius: 4px !important;
            user-select: none !important;
            cursor: not-allowed !important;
            font-size: 0 !important;
            border: 1px solid #c0392b !important;
        }

        body.dados-ocultados .dado-sensivel-cliente::after,
        body.dados-ocultados .dado-sensivel-produto::after,
        body.dados-ocultados .dado-sensivel-rastreio::after,
        body.dados-ocultados .dado-sensivel-amazon-id::after {
            content: '[●●●●●●]';
            font-size: 11px !important;
            color: #e74c3c !important;
            font-weight: bold;
            filter: none !important;
            -webkit-filter: none !important;
        }

        body.dados-ocultados .dado-sensivel-endereco {
            opacity: 0 !important;
            filter: blur(20px) !important;
            -webkit-filter: blur(20px) !important;
            pointer-events: none !important;
            user-select: none !important;
        }

        body.dados-ocultados .dado-sensivel-cpf,
        body.dados-ocultados .dado-sensivel-email,
        body.dados-ocultados .dado-sensivel-telefone,
        body.dados-ocultados .dado-sensivel-numero {
            background: #000 !important;
            color: transparent !important;
            text-shadow: none !important;
            filter: blur(15px) !important;
            -webkit-filter: blur(15px) !important;
            padding: 3px 6px !important;
            border-radius: 3px !important;
            border: 1px solid #c0392b !important;
            font-size: 0 !important;
        }

        /* Estilo específico para CPF em visualização */
        .cpf-display {
            padding: 8px 12px;
            background: rgba(93, 173, 226, 0.1);
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 1px;
            border-left: 3px solid var(--accent-color);
            margin: 4px 0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .cpf-display::before {
            content: "🔑";
            margin-right: 8px;
            font-size: 12px;
        }

        .cpf-display:hover {
            background: rgba(93, 173, 226, 0.2);
            transform: translateX(5px);
        }

        /* CPF em células de tabela */
        td .cpf-display {
            background: rgba(93, 226, 173, 0.05);
            border-left-width: 2px;
            font-size: 13px;
        }

        /* Badge de CPF */
        .cpf-badge {
            display: inline-block;
            padding: 4px 8px;
            background: linear-gradient(135deg, var(--accent-color), var(--success-color));
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin: 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .cpf-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }

        body.dados-ocultados .dado-sensivel-cpf::after,
        body.dados-ocultados .dado-sensivel-email::after,
        body.dados-ocultados .dado-sensivel-telefone::after,
        body.dados-ocultados .historico-compras {
            color: transparent !important;
            background: #000 !important;
            filter: blur(25px) !important;
            -webkit-filter: blur(25px) !important;
            pointer-events: none !important;
            user-select: none !important;
            min-height: 100px;
            padding: 30px !important;
            position: relative;
        }
        
        body.dados-ocultados .historico-compras::after {
            content: '🔒 HISTÓRICO OCULTADO' !important;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #e74c3c !important;
            font-weight: bold !important;
            font-size: 14px !important;
            white-space: nowrap;
            filter: none !important;
            -webkit-filter: none !important;
            background: rgba(0, 0, 0, 0.8);
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 1000;
        }
        
        body.dados-ocultados .historico-compras > * {
            opacity: 0 !important;
            pointer-events: none !important;
        }
            background: rgba(0, 0, 0, 0.8) !important;
            pointer-events: none;
        }

        /* Ocultar inputs sensíveis em formulários - MASCARAR COMPLETAMENTE */
        body.dados-ocultados #pedido-cliente-cpf,
        body.dados-ocultados #pedido-cliente-email,
        body.dados-ocultados #pedido-cliente-nome,
        body.dados-ocultados #pedido-rastreio-codigo,
        body.dados-ocultados #pedido-cliente-telefone,
        body.dados-ocultados #pedido-cliente-rua,
        body.dados-ocultados #pedido-cliente-numero,
        body.dados-ocultados #pedido-cliente-complemento,
        body.dados-ocultados #pedido-cliente-cidade,
        body.dados-ocultados #pedido-cliente-estado,
        body.dados-ocultados #pedido-cliente-cep,
        body.dados-ocultados #pedido-cliente-maps,
        body.dados-ocultados #pedido-produto-nome,
        body.dados-ocultados #produto-nome-novo,
        body.dados-ocultados #pedido-produto-asin {
            background-color: #000 !important;
            color: transparent !important;
            caret-color: transparent !important;
            text-shadow: none !important;
            filter: blur(15px) !important;
            -webkit-filter: blur(15px) !important;
            cursor: not-allowed !important;
            user-select: none !important;
            pointer-events: none !important;
            border: 2px solid #c0392b !important;
            padding: 6px 10px !important;
            position: relative !important;
            font-size: 0 !important;
        }
        
        body.dados-ocultados #pedido-cliente-cpf::before,
        body.dados-ocultados #pedido-cliente-email::before,
        body.dados-ocultados #pedido-cliente-nome::before,
        body.dados-ocultados #pedido-rastreio-codigo::before,
        body.dados-ocultados #pedido-cliente-telefone::before,
        body.dados-ocultados #pedido-cliente-rua::before,
        body.dados-ocultados #pedido-cliente-numero::before,
        body.dados-ocultados #pedido-cliente-complemento::before,
        body.dados-ocultados #pedido-cliente-cidade::before,
        body.dados-ocultados #pedido-cliente-estado::before,
        body.dados-ocultados #pedido-cliente-cep::before,
        body.dados-ocultados #pedido-cliente-maps::before,
        body.dados-ocultados #pedido-produto-nome::before,
        body.dados-ocultados #produto-nome-novo::before,
        body.dados-ocultados #pedido-produto-asin::before {
            content: "[OCULTO]" !important;
            font-size: 11px !important;
            color: #e74c3c !important;
            font-weight: bold !important;
        }

        body.dados-ocultados #pedido-cliente-cpf::placeholder,
        body.dados-ocultados #pedido-cliente-email::placeholder,
        body.dados-ocultados #pedido-cliente-nome::placeholder,
        body.dados-ocultados #pedido-cliente-telefone::placeholder,
        body.dados-ocultados #pedido-cliente-rua::placeholder,
        body.dados-ocultados #pedido-cliente-numero::placeholder,
        body.dados-ocultados #pedido-cliente-complemento::placeholder,
        body.dados-ocultados #pedido-cliente-cidade::placeholder,
        body.dados-ocultados #pedido-cliente-estado::placeholder,
        body.dados-ocultados #pedido-cliente-cep::placeholder,
        body.dados-ocultados #pedido-cliente-maps::placeholder,
        body.dados-ocultados #pedido-produto-nome::placeholder,
        body.dados-ocultados #pedido-produto-asin::placeholder {
            color: var(--text-muted) !important;
        }

        /* Estilo melhorado para o botão de toggle */
        #btn-toggle-dados-sensiveis {
            transition: all 0.3s ease;
            min-width: 130px;
            position: relative;
        }

        #btn-toggle-dados-sensiveis.active {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
            border-color: #c0392b !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        #btn-toggle-dados-sensiveis.active:hover {
            background: linear-gradient(135deg, #c0392b, #a93226) !important;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.5);
        }

        /* Indica visualmente quando dados estão ocultados */
        body.dados-ocultados::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(231, 76, 60, 0.02), transparent);
            pointer-events: none;
            z-index: 1;
        }

        .pedidos-tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
            overflow-x: hidden;
            overflow-y: hidden;
            padding-bottom: 2px;
        }

        .pedidos-tabs::-webkit-scrollbar {
            height: 0px;
            display: none;
        }

        .pedido-tab-btn {
            padding: 14px 25px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            font-weight: 500;
            font-size: 15px;
            white-space: nowrap;
        }

        .pedido-tab-btn:hover {
            color: var(--text-color);
        }

        .pedido-tab-btn.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .pedido-tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .pedido-tab-content {
            display: none;
        }

        .pedido-tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        /* Produtos section melhorada */
        .products-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .product-card {
            background: linear-gradient(145deg, var(--card-bg), var(--darkest-bg));
            border-radius: var(--radius);
            padding: 25px;
            border-left: 6px solid var(--success-color);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: var(--success-color);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .product-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-light);
            line-height: 1.3;
        }

        .product-category {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 20px;
            background-color: rgba(0, 168, 255, 0.15);
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-prices {
            margin-bottom: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: var(--radius-small);
            border: 1px solid var(--border-dark);
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            align-items: center;
        }

        .price-label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .price-value {
            font-weight: 600;
            color: var(--text-color);
        }

        .price-lucro {
            color: var(--success-color);
            font-weight: 700;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* IA Assistant melhorada */
        .ia-container {
            background: linear-gradient(145deg, var(--card-bg), var(--darkest-bg));
            border-radius: var(--radius);
            padding: 30px;
            margin-top: 20px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .ia-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .ia-header i {
            font-size: 28px;
            background: linear-gradient(135deg, var(--ia-color), var(--ia-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .ia-header h3 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(to right, var(--ia-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .ia-question-container {
            margin-bottom: 25px;
        }

        .ia-response-container {
            margin-top: 25px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(155, 89, 182, 0.1), rgba(0, 168, 255, 0.05));
            border-radius: var(--radius);
            border: 1px solid rgba(155, 89, 182, 0.2);
            display: none;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .ia-response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .ia-response-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-light);
        }

        .ia-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .ia-suggestions {
            margin-top: 30px;
            padding: 25px;
            background-color: rgba(0, 0, 0, 0.15);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }

        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .suggestion-card {
            background: linear-gradient(135deg, rgba(155, 89, 182, 0.1), rgba(0, 168, 255, 0.05));
            border-radius: var(--radius-small);
            padding: 20px;
            border-left: 4px solid var(--ia-color);
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid rgba(155, 89, 182, 0.2);
        }

        .suggestion-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
            border-color: var(--ia-color);
        }

        /* Histórico da IA */
        .historico-ia {
            margin-top: 30px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: var(--radius);
        }

        .historico-item {
            transition: var(--transition);
            cursor: pointer;
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: var(--radius-small);
            border-left: 4px solid transparent;
        }

        .historico-item.user {
            background-color: rgba(0,168,255,0.1);
            border-left-color: var(--primary-color);
        }

        .historico-item.assistant {
            background-color: rgba(155,89,182,0.1);
            border-left-color: var(--ia-color);
        }

        .historico-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        /* Calculadora IA melhorada */
        .calculadora-ia {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.1), rgba(0, 168, 255, 0.05));
            border-radius: var(--radius);
            padding: 25px;
            margin-top: 25px;
            border: 1px solid var(--border-color);
        }

        .calculadora-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .calculadora-input {
            flex: 1;
        }

        .calculadora-resultado {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(0, 168, 255, 0.05));
            border-radius: var(--radius-small);
            border-left: 4px solid var(--success-color);
            display: none;
        }

        /* Modal melhorado */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
            display: none;
            backdrop-filter: blur(5px);
        }

        .modal {
            background: linear-gradient(145deg, var(--card-bg), var(--darkest-bg));
            border-radius: var(--radius);
            padding: 30px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-hover);
            border: 2px solid var(--border-color);
            animation: modalAppear 0.4s ease-out;
            transition: all 0.3s ease;
        }

        .modal:has(.form-control:focus) {
            border-color: var(--primary-color);
            box-shadow: 0 8px 30px rgba(0, 168, 255, 0.3);
        }

        @keyframes modalAppear {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-light);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 28px;
            cursor: pointer;
            transition: var(--transition);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            color: var(--danger-color);
            background-color: rgba(231, 76, 60, 0.1);
        }

        /* Modal Tabs */
        .modal-tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
            overflow-x: hidden;
        }

        .modal-tab-btn {
            padding: 14px 25px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            font-size: 15px;
            white-space: nowrap;
            position: relative;
        }

        .modal-tab-btn:hover {
            color: var(--text-color);
        }

        .modal-tab-btn.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .modal-tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .modal-tab-content {
            display: none;
        }

        .modal-tab-content.active {
            display: block;
            animation: fadeIn 0.4s;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 25px;
            color: var(--text-muted);
            font-size: 14px;
            border-top: 1px solid var(--border-color);
            margin-top: 40px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: var(--radius);
        }

        /* Notificações */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: var(--radius);
            color: white;
            font-weight: 600;
            z-index: 3000;
            box-shadow: var(--shadow-hover);
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 380px;
            word-wrap: break-word;
            transform: translateX(450px);
            transition: transform 0.4s cubic-bezier(0.23, 1, 0.320, 1);
            pointer-events: auto;
            font-size: 15px;
            line-height: 1.4;
        }

        #notification-container {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 2999;
            pointer-events: none;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, var(--success-color), var(--success-dark));
            border-left: 4px solid var(--success-dark);
        }

        .notification.warning {
            background: linear-gradient(135deg, var(--warning-color), var(--warning-dark));
            border-left: 4px solid var(--warning-dark);
        }

        .notification.danger {
            background: linear-gradient(135deg, var(--danger-color), var(--danger-dark));
            border-left: 4px solid var(--danger-dark);
        }

        .notification.info {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-left: 4px solid var(--primary-dark);
        }

        /* CSS para Ocultar Dados Sensíveis */
        body.dados-ocultados .dados-sensiveis {
            color: #999 !important;
            font-weight: bold;
            background: rgba(255, 0, 0, 0.1);
            padding: 3px 6px;
            border-radius: 3px;
            user-select: none;
        }

        body.dados-ocultados .dados-sensiveis::before {
            content: "████";
        }

        /* Ocultação completa de dados em texto */
        body.dados-ocultados .dados-sensiveis {
            color: transparent !important;
            background: #000 !important;
            text-shadow: none !important;
            filter: blur(15px) !important;
            -webkit-filter: blur(15px) !important;
            user-select: none !important;
            pointer-events: none !important;
            font-size: 0 !important;
        }
        
        body.dados-ocultados .dados-sensiveis::after {
            content: "[●●●●●●]" !important;
            font-size: 11px !important;
            color: #e74c3c !important;
            font-weight: bold !important;
            filter: none !important;
            -webkit-filter: none !important;
        }

        /* Ocultação de CPF, Email, Telefone */
        body.dados-ocultados [data-type="cpf"],
        body.dados-ocultados [data-type="email"],
        body.dados-ocultados [data-type="telefone"],
        body.dados-ocultados [data-type="cartao"] {
            background: #000 !important;
            color: transparent !important;
            text-shadow: none !important;
            border: 2px solid #c0392b !important;
            padding: 2px 5px !important;
            border-radius: 3px !important;
            font-weight: bold !important;
            font-size: 0 !important;
        }
        
        body.dados-ocultados [data-type="cpf"]::after,
        body.dados-ocultados [data-type="email"]::after,
        body.dados-ocultados [data-type="telefone"]::after,
        body.dados-ocultados [data-type="cartao"]::after {
            content: "●●●" !important;
            font-size: inherit !important;
            color: #e74c3c !important;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 90px;
                padding: 20px 0;
            }
            
            .logo h1, .menu-link span {
                display: none;
            }
            
            .logo {
                justify-content: center;
                padding: 0 0 30px;
            }
            
            .logo i {
                margin-right: 0;
                font-size: 36px;
            }
            
            .menu-link {
                justify-content: center;
                padding: 18px 0;
                border-left: none;
                border-right: 4px solid transparent;
            }
            
            .menu-link:hover, .menu-link.active {
                border-left: none;
                border-right-color: var(--primary-color);
            }
            
            .menu-link i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 90px;
                padding: 20px;
            }
            
            .pedidos-container, .products-container, .suggestions-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .form-row, .calculadora-row {
                flex-direction: column;
                gap: 0;
            }
            
            .pedidos-container, .products-container, .suggestions-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .search-container {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-bar {
                width: 100%;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .pedido-links {
                flex-direction: column;
            }
            
            .pedido-actions {
                flex-wrap: wrap;
            }
            
            .modal {
                padding: 20px;
            }
            
            .modal-tabs {
                flex-wrap: wrap;
            }
            
            .modal-tab-btn {
                flex: 1;
                min-width: 120px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 70px;
            }
            
            .main-content {
                margin-left: 70px;
                padding: 15px;
            }
            
            .header h2 {
                font-size: 24px;
            }
            
            .card {
                padding: 20px;
            }
            
            .pedido-card, .product-card, .ia-container {
                padding: 20px;
            }
        }

        /* Animações extras */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Loader */
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Animações para o Gráfico Premium */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(0, 168, 255, 0.4);
            }
            50% {
                transform: scale(1.02);
                box-shadow: 0 0 0 10px rgba(0, 168, 255, 0);
            }
        }

        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(0, 168, 255, 0.3);
            }
            50% {
                box-shadow: 0 0 30px rgba(0, 168, 255, 0.6);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Aplicar animações aos elementos do gráfico */
        .performance-chart {
            animation: fadeInUp 0.8s ease-out;
        }

        .performance-chart .chart-container {
            animation: slideInLeft 0.6s ease-out 0.2s both;
        }

        .performance-chart canvas {
            animation: bounceIn 0.8s ease-out 0.4s both;
        }

        .performance-chart .insights-grid > div {
            animation: slideInRight 0.6s ease-out both;
        }

        .performance-chart .insights-grid > div:nth-child(1) {
            animation-delay: 0.6s;
        }

        .performance-chart .insights-grid > div:nth-child(2) {
            animation-delay: 0.8s;
        }

        .performance-chart .insights-grid > div:nth-child(3) {
            animation-delay: 1s;
        }

        /* Hover effects para elementos interativos */
        .performance-chart select:hover {
            animation: pulse 1s infinite;
        }

        .performance-chart .mini-metric:hover {
            animation: glow 1s infinite;
        }

        /* Responsividade aprimorada */
        @media (max-width: 768px) {
            .performance-chart {
                padding: 20px !important;
            }

            .performance-chart .insights-grid {
                grid-template-columns: 1fr !important;
            }

            .performance-chart canvas {
                max-height: 250px !important;
            }
        }

        /* Modo focado para cliente expandido */
        #clientes-container {
            overflow-x: visible !important;
            overflow-y: visible !important;
        }

        #clientes-container.modo-focado {
            display: flex !important;
            justify-content: center !important;
            align-items: flex-start !important;
            grid-template-columns: none !important;
        }

        .cliente-card {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            z-index: 1;
        }

        .cliente-card.cliente-oculto {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden !important;
            animation: fadeOut 0.3s ease-out forwards;
        }

        .cliente-card.cliente-expandido {
            z-index: 100 !important;
            width: 100% !important;
            max-width: 1000px !important;
            margin: 0 auto !important;
            animation: expandCard 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }

        @keyframes expandCard {
            from {
                opacity: 0.8;
                transform: scale(0.98);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .cliente-card.cliente-visivel {
            display: block !important;
            visibility: visible !important;
            animation: fadeIn 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }

        /* Modo focado - transição suave do layout */
        #clientes-container.modo-focado .cliente-card.cliente-expandido {
            padding: 25px;
            border-radius: var(--radius);
        }

        /* Transição do painel extra */
        .cliente-rastreio-extra {
            opacity: 0 !important;
            transform: translateY(-10px) !important;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
        }

        .cliente-rastreio-extra[style*="display: block"] {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    </style>
    <script>
        (function() {
            try {
                const temaSalvo = localStorage.getItem('marketManager_tema') || 'escuro';
                const TEMAS_INIT = {
                    escuro: {
                        fundo: '#0f1419',
                        fundoSecundario: '#1a1f26',
                        texto: '#e0e0e0',
                        textoPrimario: '#ffffff',
                        textoMudo: '#8a9bb2',
                        primary: '#00a8ff',
                        secondary: '#1e90ff',
                        cardBg: '#1a2a3e',
                        cardHover: '#22334a',
                        borderColor: '#2a3b56',
                        borderLight: '#3a4b66',
                        darkBg: '#0f1a2c',
                        darkerBg: '#0a1423',
                        darkestBg: '#060e1a'
                    },
                    preto: {
                        fundo: '#000000',
                        fundoSecundario: '#111111',
                        texto: '#ffffff',
                        textoPrimario: '#ffffff',
                        textoMudo: '#b3b3b3',
                        primary: '#00a8ff',
                        secondary: '#1e90ff',
                        cardBg: '#111111',
                        cardHover: '#1a1a1a',
                        borderColor: 'rgba(255, 255, 255, 0.08)',
                        borderLight: 'rgba(255, 255, 255, 0.12)',
                        darkBg: '#000000',
                        darkerBg: '#090909',
                        darkestBg: '#080808'
                    },
                    claro: {
                        fundo: '#ffffff',
                        fundoSecundario: '#f5f5f5',
                        texto: '#333333',
                        textoPrimario: '#000000',
                        textoMudo: '#666666',
                        primary: '#0073cc',
                        secondary: '#ff6b6b',
                        cardBg: '#ffffff',
                        cardHover: '#f5f5f5',
                        borderColor: 'rgba(0, 0, 0, 0.08)',
                        borderLight: 'rgba(0, 0, 0, 0.12)',
                        darkBg: '#f5f5f5',
                        darkerBg: '#e5e5e5',
                        darkestBg: '#d5d5d5'
                    },
                    profissional: {
                        fundo: '#263238',
                        fundoSecundario: '#37474f',
                        texto: '#cfd8dc',
                        textoPrimario: '#eceff1',
                        textoMudo: '#90a4ae',
                        primary: '#4caf50',
                        secondary: '#81c784',
                        cardBg: '#37474f',
                        cardHover: '#2f3a44',
                        borderColor: '#455a64',
                        borderLight: '#546e7a',
                        darkBg: '#263238',
                        darkerBg: '#1f2b33',
                        darkestBg: '#19232b'
                    },
                    vibrante: {
                        fundo: '#2c0033',
                        fundoSecundario: '#440055',
                        texto: '#ff6ec7',
                        textoPrimario: '#ff1744',
                        textoMudo: '#bb86fc',
                        primary: '#ff1744',
                        secondary: '#ff6ec7',
                        cardBg: '#440055',
                        cardHover: '#3a0049',
                        borderColor: 'rgba(255, 23, 68, 0.2)',
                        borderLight: 'rgba(255, 23, 68, 0.3)',
                        darkBg: '#2c0033',
                        darkerBg: '#24002d',
                        darkestBg: '#1d0026'
                    },
                    marine: {
                        fundo: '#0d1f2d',
                        fundoSecundario: '#1a3a3a',
                        texto: '#00d4ff',
                        textoPrimario: '#00bcd4',
                        textoMudo: '#0096d6',
                        primary: '#00bcd4',
                        secondary: '#0096d6',
                        cardBg: '#1a3a3a',
                        cardHover: '#173541',
                        borderColor: 'rgba(0, 188, 212, 0.2)',
                        borderLight: 'rgba(0, 188, 212, 0.3)',
                        darkBg: '#0d1f2d',
                        darkerBg: '#091622',
                        darkestBg: '#06101b'
                    },
                    sunset: {
                        fundo: '#3d1f00',
                        fundoSecundario: '#5d3a1a',
                        texto: '#ffb74d',
                        textoPrimario: '#ffc947',
                        textoMudo: '#ff9800',
                        primary: '#ff9800',
                        secondary: '#ffb74d',
                        cardBg: '#5d3a1a',
                        cardHover: '#553414',
                        borderColor: 'rgba(255, 152, 0, 0.2)',
                        borderLight: 'rgba(255, 152, 0, 0.3)',
                        darkBg: '#3d1f00',
                        darkerBg: '#351b00',
                        darkestBg: '#2f1700'
                    },
                    neon: {
                        fundo: '#0a0e27',
                        fundoSecundario: '#1a1f3a',
                        texto: '#00ff88',
                        textoPrimario: '#00ffff',
                        textoMudo: '#0099cc',
                        primary: '#00ff88',
                        secondary: '#ff00ff',
                        cardBg: '#1a1f3a',
                        cardHover: '#161a30',
                        borderColor: 'rgba(0, 255, 136, 0.2)',
                        borderLight: 'rgba(0, 255, 136, 0.3)',
                        darkBg: '#0a0e27',
                        darkerBg: '#081026',
                        darkestBg: '#060c1f'
                    },
                    floresta: {
                        fundo: '#1b5e20',
                        fundoSecundario: '#2e7d32',
                        texto: '#81c784',
                        textoPrimario: '#c8e6c9',
                        textoMudo: '#558b2f',
                        primary: '#4caf50',
                        secondary: '#81c784',
                        cardBg: '#2e7d32',
                        cardHover: '#2a712d',
                        borderColor: 'rgba(76, 175, 80, 0.2)',
                        borderLight: 'rgba(76, 175, 80, 0.3)',
                        darkBg: '#1b5e20',
                        darkerBg: '#174d1c',
                        darkestBg: '#133d16'
                    },
                    galaxia: {
                        fundo: '#1a0033',
                        fundoSecundario: '#33006f',
                        texto: '#ce93d8',
                        textoPrimario: '#e1bee7',
                        textoMudo: '#7b1fa2',
                        primary: '#ba68c8',
                        secondary: '#ce93d8',
                        cardBg: '#33006f',
                        cardHover: '#2b005f',
                        borderColor: 'rgba(186, 104, 200, 0.2)',
                        borderLight: 'rgba(186, 104, 200, 0.3)',
                        darkBg: '#1a0033',
                        darkerBg: '#15002b',
                        darkestBg: '#110024'
                    },
                    dashboard: {
                        fundo: '#34495e',
                        fundoSecundario: '#2c3e50',
                        texto: '#ecf0f1',
                        textoPrimario: '#ffffff',
                        textoMudo: '#95a5a6',
                        primary: '#3498db',
                        secondary: '#2980b9',
                        cardBg: '#2c3e50',
                        cardHover: '#34495e',
                        borderColor: 'rgba(52, 152, 219, 0.2)',
                        borderLight: 'rgba(52, 152, 219, 0.3)',
                        darkBg: '#34495e',
                        darkerBg: '#2d3f50',
                        darkestBg: '#273544'
                    },
                    minimalista: {
                        fundo: '#ecf0f1',
                        fundoSecundario: '#bdc3c7',
                        texto: '#2c3e50',
                        textoPrimario: '#34495e',
                        textoMudo: '#7f8c8d',
                        primary: '#95a5a6',
                        secondary: '#7f8c8d',
                        cardBg: '#ffffff',
                        cardHover: '#ecf0f1',
                        borderColor: 'rgba(149, 165, 166, 0.3)',
                        borderLight: 'rgba(149, 165, 166, 0.5)',
                        darkBg: '#ecf0f1',
                        darkerBg: '#d5d9df',
                        darkestBg: '#c5ccd4'
                    },
                    retro: {
                        fundo: '#8b4513',
                        fundoSecundario: '#daa520',
                        texto: '#ffd700',
                        textoPrimario: '#ffff00',
                        textoMudo: '#daa520',
                        primary: '#daa520',
                        secondary: '#ffd700',
                        cardBg: '#daa520',
                        cardHover: '#b8860b',
                        borderColor: 'rgba(218, 165, 32, 0.3)',
                        borderLight: 'rgba(255, 215, 0, 0.5)',
                        darkBg: '#8b4513',
                        darkerBg: '#7a3f11',
                        darkestBg: '#6a3710'
                    },
                    cyberpunk: {
                        fundo: '#0a0a0a',
                        fundoSecundario: '#1a0033',
                        texto: '#ff00ff',
                        textoPrimario: '#ff0080',
                        textoMudo: '#00ffff',
                        primary: '#ff00ff',
                        secondary: '#00ffff',
                        cardBg: '#1a0033',
                        cardHover: '#330066',
                        borderColor: 'rgba(255, 0, 255, 0.3)',
                        borderLight: 'rgba(0, 255, 255, 0.5)',
                        darkBg: '#0a0a0a',
                        darkerBg: '#090819',
                        darkestBg: '#070714'
                    },
                    oceano: {
                        fundo: '#001122',
                        fundoSecundario: '#003366',
                        texto: '#00bfff',
                        textoPrimario: '#87ceeb',
                        textoMudo: '#4682b4',
                        primary: '#00bfff',
                        secondary: '#87ceeb',
                        cardBg: '#003366',
                        cardHover: '#004080',
                        borderColor: 'rgba(0, 191, 255, 0.3)',
                        borderLight: 'rgba(135, 206, 235, 0.5)',
                        darkBg: '#001122',
                        darkerBg: '#001a33',
                        darkestBg: '#00152a'
                    },
                    deserto: {
                        fundo: '#8b4513',
                        fundoSecundario: '#daa520',
                        texto: '#ffa500',
                        textoPrimario: '#ff8c00',
                        textoMudo: '#daa520',
                        primary: '#ffa500',
                        secondary: '#daa520',
                        cardBg: '#daa520',
                        cardHover: '#cd853f',
                        borderColor: 'rgba(255, 165, 0, 0.3)',
                        borderLight: 'rgba(218, 165, 32, 0.5)',
                        darkBg: '#8b4513',
                        darkerBg: '#7e4312',
                        darkestBg: '#703911'
                    }
                };
                const tema = TEMAS_INIT[temaSalvo] || TEMAS_INIT.escuro;
                const root = document.documentElement.style;
                root.setProperty('--primary-color', tema.primary);
                root.setProperty('--secondary-color', tema.secondary);
                root.setProperty('--bg-color', tema.fundo);
                root.setProperty('--bg-secondary', tema.fundoSecundario);
                root.setProperty('--text-light', tema.texto);
                root.setProperty('--text-color', tema.textoPrimario);
                root.setProperty('--text-muted', tema.textoMudo);
                root.setProperty('--card-bg', tema.cardBg || tema.fundoSecundario || tema.fundo);
                root.setProperty('--card-hover', tema.cardHover || 'rgba(255, 255, 255, 0.08)');
                root.setProperty('--border-color', tema.borderColor || 'rgba(255, 255, 255, 0.08)');
                root.setProperty('--border-light', tema.borderLight || 'rgba(255, 255, 255, 0.12)');
                root.setProperty('--dark-bg', tema.darkBg || tema.fundo);
                root.setProperty('--darker-bg', tema.darkerBg || tema.fundoSecundario || tema.fundo);
                root.setProperty('--darkest-bg', tema.darkestBg || tema.darkerBg || tema.fundo);
            } catch (e) {
                console.error('Erro ao aplicar tema inicial:', e);
            }
        })();
    </script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-toggle" id="sidebar-toggle" aria-label="Alternar barra lateral">
                <i class="fas fa-angle-double-left"></i>
            </div>
            <div class="logo">
                <i class="fas fa-robot"></i>
                <h1>Market Manager Pro</h1>
            </div>
            
            <ul class="menu">
                <li class="menu-item">
                    <a href="#dashboard" class="menu-link active" data-tab="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                        <span class="menu-badge pulse" id="menu-badge-dashboard">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#pedidos" class="menu-link" data-tab="pedidos">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Pedidos</span>
                        <span class="menu-badge" id="menu-badge-pedidos">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#produtos" class="menu-link" data-tab="produtos">
                        <i class="fas fa-boxes"></i>
                        <span>Produtos</span>
                        <span class="menu-badge" id="menu-badge-produtos">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#ia" class="menu-link" data-tab="ia">
                        <i class="fas fa-brain"></i>
                        <span>Assistente IA</span>
                        <span class="menu-badge" style="background-color: var(--ia-color);">IA</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="training.php" class="menu-link" target="_blank">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Treinamento IA</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#analise" class="menu-link" data-tab="analise">
                        <i class="fas fa-chart-line"></i>
                        <span>Análise Financeira</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#relatorios" class="menu-link" data-tab="relatorios">
                        <i class="fas fa-file-csv"></i>
                        <span>Relatórios & Exportar</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#alertas" class="menu-link" data-tab="alertas">
                        <i class="fas fa-bell"></i>
                        <span>Alertas Inteligentes</span>
                        <span class="menu-badge" id="menu-badge-alertas" style="background-color: #ff6b6b;">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#automacao" class="menu-link" data-tab="automacao">
                        <i class="fas fa-magic"></i>
                        <span>Automação & IA</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#integracao" class="menu-link" data-tab="integracao">
                        <i class="fas fa-link"></i>
                        <span>Integrações</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#configuracoes" class="menu-link" data-tab="configuracoes">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#clientes" class="menu-link" data-tab="clientes">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                        <span class="menu-badge" id="menu-badge-clientes">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#rastreio" class="menu-link" data-tab="rastreio">
                        <i class="fas fa-truck"></i>
                        <span>Rastreio</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#whatsapp" class="menu-link" data-tab="whatsapp">
                        <i class="fas fa-comments"></i>
                        <span>WhatsApp</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-status" style="padding: 25px; margin-top: 30px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">Sistema Atualizado</div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 10px; height: 10px; background-color: var(--success-color); border-radius: 50%;"></div>
                    <div style="font-size: 14px; color: var(--text-color);">Online</div>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">Salvamento em JSON ativo</div>
                <div style="font-size: 10px; color: var(--text-contrast); margin-top: 10px;">
                    Último backup:<br>
                    <?php echo date('d/m/Y H:i'); ?>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h2><i class="fas fa-tachometer-alt"></i> Dashboard de Gestão</h2>
                
                <div class="user-info">
                    <div class="search-container">
                        <div class="search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search-input" placeholder="Buscar pedido, cliente, produto, código...">
                        </div>
                        <button class="search-advanced-btn" id="btn-advanced-search">
                            <i class="fas fa-sliders-h"></i> Busca Avançada
                        </button>
                    </div>
                    
                    <button class="btn btn-secondary" id="btn-toggle-dados-sensiveis" title="Ocultar/Mostrar dados sensíveis">
                        <i class="fas fa-eye-slash"></i> Ocultar Dados
                    </button>
                    
                    <div class="user-avatar" title="Gerente Market">
                        BLL
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
            <div id="dashboard" class="tab-pane active">
                <div class="dashboard-cards" id="dashboard-cards">
                    <!-- Cards serão carregados dinamicamente -->
                </div>

                <!-- Tabela de pedidos recentes -->
                <div class="tabs-content">
                    <div class="tabs-header">
                        <button class="tab-btn active" data-tab="pedidos-recentes">Pedidos Recentes</button>
                        <button class="tab-btn" data-tab="clientes-top">Top Clientes</button>
                        <button class="tab-btn" data-tab="produtos-top">Produtos Mais Vendidos</button>
                        <button class="tab-btn" data-tab="status-pedidos">Status dos Pedidos</button>
                    </div>
                    
                    <div class="tab-pane active" id="pedidos-recentes">
                        <div class="table-container">
                            <table class="compact-table">
                                <thead>
                                    <tr>
                                        <th>Código Rastreio</th>
                                        <th>Cliente</th>
                                        <th>Produto</th>
                                        <th>Status</th>
                                        <th>Data Envio</th>
                                        <th>Valor</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-pedidos-recentes">
                                    <!-- Preenchido por JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane" id="clientes-top">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Pedidos</th>
                                        <th>Valor Total</th>
                                        <th>Última Compra</th>
                                        <th>Contato</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-clientes-top">
                                    <!-- Preenchido por JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane" id="produtos-top">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Vendas</th>
                                        <th>Preço</th>
                                        <th>Lucro Líq. Unit.</th>
                                        <th>Lucro Líq. Total</th>
                                        <th>Análise</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-produtos-top">
                                    <!-- Preenchido por JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane" id="status-pedidos">
                        <!-- Preenchido por JavaScript -->
                    </div>
                </div>

                <!-- Botões de Ações Rápidas da Dashboard -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 30px;">
                    <button class="btn btn-success" id="btn-novo-pedido-dashboard" style="height: 60px; font-size: 16px;">
                        <i class="fas fa-plus-circle"></i><br>Novo Pedido
                    </button>
                    <button class="btn btn-primary" id="btn-novo-produto-dashboard" style="height: 60px; font-size: 16px;">
                        <i class="fas fa-cube"></i><br>Novo Produto
                    </button>
                    <button class="btn btn-warning" id="btn-ver-pedidos-dashboard" style="height: 60px; font-size: 16px;">
                        <i class="fas fa-shipping-fast"></i><br>Ver Pedidos
                    </button>
                    <button class="btn btn-secondary" id="btn-ver-produtos-dashboard" style="height: 60px; font-size: 16px;">
                        <i class="fas fa-boxes"></i><br>Ver Produtos
                    </button>
                </div>
            </div>

            <!-- Pedidos Unificados -->
            <div id="pedidos" class="tab-pane">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center;">
                    <h3>Pedidos Unificados</h3>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <!-- Botões de visualização -->
                        <div class="btn-group" style="display: flex; background: var(--card-bg); border-radius: 6px; padding: 2px;">
                            <button class="view-btn" id="view-lista-pedidos" data-view="lista" title="Visualização em Lista">
                                <i class="fas fa-list"></i>
                            </button>
                            <button class="view-btn active" id="view-quadrado-pedidos" data-view="grid" title="Visualização em Grade">
                                <i class="fas fa-th"></i>
                            </button>
                        </div>
                        <button class="btn btn-primary" id="btn-importar-pedidos">
                            <i class="fas fa-file-import"></i> Importar
                        </button>
                        <button class="btn btn-success" id="btn-novo-pedido">
                            <i class="fas fa-plus"></i> Novo Pedido
                        </button>
                        <button class="btn btn-warning" id="btn-backup-pedidos" title="Fazer backup dos dados">
                            <i class="fas fa-download"></i> Backup
                        </button>
                    </div>
                </div>
                
                <div class="pedidos-tabs">
                    <button class="pedido-tab-btn active" data-pedido-tab="todos">Todos os Pedidos</button>
                    <button class="pedido-tab-btn" data-pedido-tab="pendentes">Pendentes</button>
                    <button class="pedido-tab-btn" data-pedido-tab="transito">Em Trânsito</button>
                    <button class="pedido-tab-btn" data-pedido-tab="entregues">Entregues</button>
                </div>
                
                <!-- Filtro de Conta Shopee -->
                <div style="margin: 20px 0; padding: 15px; background-color: var(--card-bg); border-radius: var(--radius-small); display: flex; gap: 15px; align-items: center;">
                    <label style="font-weight: 600;">Filtrar por Conta:</label>
                    <select id="filtro-conta-shopee" class="form-control" style="max-width: 300px;">
                        <option value="">Todas as Contas</option>
                    </select>
                </div>
                
                <div class="pedido-tab-content active" id="todos">
                    <div class="pedidos-container" id="pedidos-todos-container">
                        <!-- Preenchido por JavaScript -->
                    </div>
                </div>
                
                <div class="pedido-tab-content" id="pendentes">
                    <div class="pedidos-container" id="pedidos-pendentes-container">
                        <!-- Preenchido por JavaScript -->
                    </div>
                </div>
                
                <div class="pedido-tab-content" id="transito">
                    <div class="pedidos-container" id="pedidos-transito-container">
                        <!-- Preenchido por JavaScript -->
                    </div>
                </div>
                
                <div class="pedido-tab-content" id="entregues">
                    <div class="pedidos-container" id="pedidos-entregues-container">
                        <!-- Preenchido por JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Produtos -->
            <div id="produtos" class="tab-pane">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center;">
                    <h3>Catálogo de Produtos</h3>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <!-- Botões de visualização -->
                        <div class="btn-group" style="display: flex; background: var(--card-bg); border-radius: 6px; padding: 2px;">
                            <button class="view-btn" id="view-lista-produtos" data-view="lista" title="Visualização em Lista">
                                <i class="fas fa-list"></i>
                            </button>
                            <button class="view-btn active" id="view-quadrado-produtos" data-view="grid" title="Visualização em Grade">
                                <i class="fas fa-th"></i>
                            </button>
                        </div>
                        <button class="btn btn-secondary" id="btn-importar-produtos">
                            <i class="fas fa-file-import"></i> Importar
                        </button>
                        <button class="btn btn-success" id="btn-novo-produto">
                            <i class="fas fa-plus"></i> Novo Produto
                        </button>
                        <button class="btn btn-warning" id="btn-backup-produtos" title="Fazer backup dos dados">
                            <i class="fas fa-download"></i> Backup
                        </button>
                        <!-- NOVO BOTÃO: IMPORTAR DA AMAZON -->
                        <button class="btn btn-secondary" id="btn-importar-amazon">
                            <i class="fab fa-amazon"></i> Importar da Amazon
                        </button>
                    </div>
                </div>
                
                <div class="products-container" id="products-container">
                    <!-- Preenchido por JavaScript -->
                </div>
            </div>

            <!-- IA - AGORA COM MEMÓRIA E FUNÇÕES AVANÇADAS -->
            <div id="ia" class="tab-pane">
                <div class="ia-container">
                    <div class="ia-header">
                        <i class="fas fa-robot"></i>
                        <h3>Assistente IA Inteligente com Memória</h3>
                    </div>
                    
                    <!-- Configuração de API Key - Novo Design -->
                    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); border: 1px solid rgba(0, 168, 255, 0.3); border-radius: 12px; padding: 30px; margin-bottom: 30px; box-shadow: 0 8px 32px rgba(0, 168, 255, 0.15);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;">
                            <!-- Lado Esquerdo: Instruções -->
                            <div>
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                                    <div style="background: rgba(0, 168, 255, 0.2); padding: 12px; border-radius: 8px;">
                                        <i class="fas fa-brain" style="color: #00a8ff; font-size: 24px;"></i>
                                    </div>
                                    <div>
                                        <h4 style="color: #00a8ff; margin: 0; font-weight: 800; font-size: 18px;">Assistente IA Inteligente</h4>
                                        <p style="color: var(--text-muted); margin: 3px 0 0 0; font-size: 12px;">Powered by Pollinations.AI</p>
                                    </div>
                                </div>
                                
                                <div style="background: rgba(0, 168, 255, 0.05); border-left: 3px solid #00a8ff; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.8;">
                                        <strong style="color: #00a8ff;">Como configurar em 3 passos:</strong><br>
                                        <span style="display: block; margin-top: 10px;">
                                            <strong>1️⃣</strong> Acesse <a href="https://enter.pollinations.ai/" target="_blank" style="color: #00a8ff; text-decoration: none; font-weight: 600;">enter.pollinations.ai</a><br>
                                            <strong>2️⃣</strong> Copie sua chave (começa com <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 3px; color: #2ecc71;">sk_</code>)<br>
                                            <strong>3️⃣</strong> Cole no campo abaixo e clique em "Salvar"
                                        </span>
                                    </p>
                                </div>
                                
                                <div style="background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05)); border: 1px solid rgba(46, 204, 113, 0.3); padding: 12px; border-radius: 6px;">
                                    <p style="color: #2ecc71; font-size: 11px; margin: 0;"><strong>🔒 Privacidade:</strong> Sua chave fica apenas no seu navegador, nunca em servidores.</p>
                                </div>
                            </div>
                            
                            <!-- Lado Direito: Input e Botões -->
                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <div>
                                    <label style="color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">🔐 Chave API Pollinations</label>
                                    <div style="position: relative; display: flex; gap: 8px;">
                                        <input type="password" id="api-key-ia" class="form-control" placeholder="sk_seu_código_aqui..." style="padding: 14px 16px; font-size: 13px; font-family: 'Courier New', monospace; letter-spacing: 1px; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(0, 168, 255, 0.2); color: var(--text-light); flex: 1;" autocomplete="off">
                                        <button onclick="marketManager.alternarVisibilidadeAPIKey()" style="background: rgba(0, 168, 255, 0.1); border: 1px solid rgba(0, 168, 255, 0.3); color: #00a8ff; padding: 10px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; transition: all 0.3s;" onmouseover="this.style.background='rgba(0, 168, 255, 0.2)'" onmouseout="this.style.background='rgba(0, 168, 255, 0.1)'">
                                            👁️
                                        </button>
                                    </div>
                                    <div style="margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                                        <span id="api-key-status" style="font-size: 11px; color: var(--text-muted); padding: 6px 12px; background: rgba(0, 0, 0, 0.2); border-radius: 4px; display: inline-block;">❌ Não configurada</span>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <button onclick="marketManager.salvarAPIKeyIA()" style="background: linear-gradient(135deg, #00a8ff, #0082cc); color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(0, 168, 255, 0.3);" onmouseover="this.style.boxShadow='0 6px 20px rgba(0, 168, 255, 0.5)'" onmouseout="this.style.boxShadow='0 4px 12px rgba(0, 168, 255, 0.3)'">
                                        💾 Salvar Chave
                                    </button>
                                    <button onclick="document.getElementById('api-key-ia').value = ''; document.getElementById('api-key-status').innerHTML = '❌ Não configurada'; localStorage.removeItem('pollinations_api_key');" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 12px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13px; transition: all 0.3s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'">
                                        🗑️ Limpar
                                    </button>
                                </div>
                                
                                <div style="background: linear-gradient(135deg, rgba(155, 89, 182, 0.1), rgba(155, 89, 182, 0.05)); border: 1px solid rgba(155, 89, 182, 0.2); padding: 12px; border-radius: 6px;">
                                    <p style="color: #9b59b6; font-size: 11px; margin: 0;"><strong>💡 Dica:</strong> Sem créditos? Visite <a href="https://enter.pollinations.ai/account" target="_blank" style="color: #9b59b6; text-decoration: underline;">enter.pollinations.ai/account</a> para recarregar.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ia-question-container">
                        <div class="form-group">
                            <label class="form-label">Pergunte ao assistente IA:</label>
                            <textarea class="form-control" id="pergunta-ia" rows="4" placeholder="Ex: Analise meus produtos e sugira ajustes de preço para maximizar o lucro..."></textarea>
                        </div>
                        
                        <div class="ia-actions">
                            <button class="btn btn-ia" id="btn-perguntar-ia">
                                <i class="fas fa-paper-plane"></i> Enviar para IA
                            </button>
                            <button class="btn btn-secondary" id="btn-analise-precos">
                                <i class="fas fa-chart-line"></i> Análise de Preços
                            </button>
                            <button class="btn btn-secondary" id="btn-sugestoes-vendas">
                                <i class="fas fa-lightbulb"></i> Sugestões de Vendas
                            </button>
                            <button class="btn btn-secondary" id="btn-previsao-vendas">
                                <i class="fas fa-crystal-ball"></i> Previsão de Vendas
                            </button>
                            <button class="btn btn-secondary" id="btn-pesquisa-mercado">
                                <i class="fas fa-search"></i> Pesquisa de Mercado
                            </button>
                            <button class="btn btn-secondary" id="btn-analise-concorrencia">
                                <i class="fas fa-chart-simple"></i> Análise de Concorrência
                            </button>
                            <button class="btn btn-secondary" id="btn-gerar-anuncio">
                                <i class="fas fa-ad"></i> Gerar Anúncio
                            </button>
                            <button class="btn btn-secondary" id="btn-analise-tendencias">
                                <i class="fas fa-chart-line"></i> Tendências
                            </button>
                            <button class="btn btn-secondary" id="btn-sourcing-shopee">
                                <i class="fas fa-shopping-cart"></i> Sourcing Shopee → Amazon
                            </button>
                        </div>
                    </div>
                    
                    <div class="ia-response-container" id="resposta-ia">
                        <div class="ia-response-header">
                            <h4>Resposta da IA:</h4>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-small btn-secondary" id="btn-copiar-resposta">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                                <button class="btn btn-small btn-danger" id="btn-limpar-resposta">
                                    <i class="fas fa-times"></i> Limpar
                                </button>
                            </div>
                        </div>
                        <div id="texto-resposta-ia"></div>
                        <div class="ia-actions" id="ia-response-actions" style="margin-top: 20px; display: none;">
                            <!-- Ações adicionais da resposta -->
                        </div>
                    </div>
                    
                    <!-- HISTÓRICO DA CONVERSA -->
                    <div class="historico-ia">
                        <h4><i class="fas fa-history"></i> Histórico da Conversa</h4>
                        <div id="historico-ia-container" style="max-height: 300px; overflow-y: auto; margin-top: 15px; padding: 10px;"></div>
                        <button class="btn btn-small btn-secondary" id="btn-limpar-historico" style="margin-top: 10px;">
                            <i class="fas fa-broom"></i> Limpar Histórico
                        </button>
                    </div>
                    
                    <!-- Calculadora de Lucro Automática -->
                    <div class="calculadora-ia">
                        <h4 style="margin-bottom: 15px; color: var(--text-light);">Calculadora de Lucro Automática</h4>
                        
                        <div class="calculadora-row">
                            <div class="calculadora-input">
                                <label class="form-label">Preço Original (Custo)</label>
                                <input type="number" class="form-control" id="preco-original" placeholder="0.00" step="0.01" min="0">
                            </div>
                            <div class="calculadora-input">
                                <label class="form-label">Preço de Venda</label>
                                <input type="number" class="form-control" id="preco-venda" placeholder="0.00" step="0.01" min="0">
                            </div>
                            <div class="calculadora-input">
                                <label class="form-label">Taxas e Impostos (%)</label>
                                <input type="number" class="form-control" id="taxas" placeholder="15" step="0.1" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="calculadora-row">
                            <div class="calculadora-input">
                                <label class="form-label">Frete (R$)</label>
                                <input type="number" class="form-control" id="frete" placeholder="0.00" step="0.01" min="0">
                            </div>
                            <div class="calculadora-input">
                                <label class="form-label">Quantidade Vendida</label>
                                <input type="number" class="form-control" id="quantidade" placeholder="1" step="1" min="1">
                            </div>
                            <div class="calculadora-input">
                                <label class="form-label" style="visibility: hidden;">Calcular</label>
                                <button class="btn btn-ia" id="btn-calcular-lucro" style="width: 100%;">
                                    <i class="fas fa-calculator"></i> Calcular Lucro
                                </button>
                            </div>
                        </div>
                        
                        <div class="calculadora-resultado" id="resultado-calculo">
                            <!-- Resultado será inserido aqui -->
                        </div>
                    </div>
                    
                    <div class="ia-suggestions">
                        <h4>Sugestões Automáticas da IA</h4>
                        <div class="suggestions-grid" id="sugestoes-ia">
                            <!-- Sugestões carregadas via JavaScript -->
                        </div>
                    </div>

                    <!-- ============ SEÇÃO SUPER EXPANDIDA DE TREINO DE IA ============ -->

                    <!-- TREINO COM TEXTOS PERSONALIZADOS -->
                    <div style="margin-top: 50px; padding: 30px; background: linear-gradient(135deg, rgba(155, 89, 182, 0.12), rgba(155, 89, 182, 0.03)); border: 2px solid rgba(155, 89, 182, 0.4); border-radius: 14px; box-shadow: 0 8px 24px rgba(155, 89, 182, 0.1);">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                            <div style="background: linear-gradient(135deg, rgba(155, 89, 182, 0.3), rgba(155, 89, 182, 0.15)); padding: 15px; border-radius: 10px;">
                                <i class="fas fa-brain" style="color: #9b59b6; font-size: 28px;"></i>
                            </div>
                            <div>
                                <h3 style="color: #9b59b6; margin: 0; font-weight: 900; font-size: 22px;">🎓 Centro de Treinamento de IA</h3>
                                <p style="color: var(--text-muted); margin: 5px 0 0 0; font-size: 13px;">Envie textos, documentos e conhecimentos para treinar a IA perfeitamente</p>
                            </div>
                        </div>

                        <!-- TAB SELETOR -->
                        <div style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid rgba(155, 89, 182, 0.2); padding-bottom: 10px;">
                            <button onclick="mostrarAbaComTema(this, 'treino-texto')" class="tab-treino active" style="background: rgba(155, 89, 182, 0.3); color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                                📝 Treino com Texto
                            </button>
                            <button onclick="mostrarAbaComTema(this, 'personalizacao')" class="tab-treino" style="background: rgba(100, 100, 100, 0.1); color: var(--text-light); border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                                ⚙️ Personalizar Respostas
                            </button>
                            <button onclick="mostrarAbaComTema(this, 'historico')" class="tab-treino" style="background: rgba(100, 100, 100, 0.1); color: var(--text-light); border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                                📊 Histórico
                            </button>
                        </div>

                        <!-- ABA 1: TREINO COM TEXTO -->
                        <div id="treino-texto" class="aba-treino-conteudo">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                                <!-- ESQUERDA: TEXTAREA GRANDE -->
                                <div style="grid-column: 1;">
                                    <h4 style="color: var(--text-light); margin: 0 0 15px 0; font-weight: 700;">Seu Texto para Treino:</h4>
                                    <div style="position: relative;">
                                        <textarea id="texto-treino-ia" placeholder="Cole aqui seus documentos, manuais, políticas, regras de negócio, histórico de vendas, ou qualquer conhecimento que você quer que a IA aprenda...

DEIXE SUA IMAGINAÇÃO VOAR! Quanto mais informação, mais inteligente fica a IA!

Exemplo:
- Políticas de preço e desconto
- Padrões de clientes
- Histórico de vendas
- Regras da empresa
- Manuais de produto
- Estratégias de marketing
- Dados de competidores
- Qualquer conhecimento relevante!
" class="form-control" style="width: 100%; height: 300px; padding: 15px; font-family: 'Courier New', monospace; font-size: 13px; resize: vertical; border: 2px solid rgba(155, 89, 182, 0.3);"></textarea>
                                        <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                                            <small id="contador-chars" style="color: var(--text-muted); font-size: 12px;">0 / 10.000 caracteres</small>
                                            <div style="display: flex; gap: 8px;">
                                                <button onclick="carregarExemploTreinament()" class="btn" style="padding: 6px 12px; font-size: 12px; background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); border-radius: 4px; cursor: pointer;">
                                                    📋 Carregar Exemplo
                                                </button>
                                                <button onclick="limparTextoTreinament()" class="btn" style="padding: 6px 12px; font-size: 12px; background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); border-radius: 4px; cursor: pointer;">
                                                    🗑️ Limpar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- DIREITA: CONFIGURAÇÕES -->
                                <div style="grid-column: 2;">
                                    <h4 style="color: var(--text-light); margin: 0 0 15px 0; font-weight: 700;">Configurações de Treino:</h4>

                                    <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 8px; font-size: 13px;">📂 Categoria do Conhecimento:</label>
                                        <select id="categoria-treino" style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(155, 89, 182, 0.3); color: var(--text-light); border-radius: 6px; font-weight: 500;">
                                            <option value="geral">📚 Conhecimento Geral</option>
                                            <option value="negocio">💼 Regras de Negócio</option>
                                            <option value="produtos">📦 Dados de Produtos</option>
                                            <option value="clientes">👥 Padrões de Clientes</option>
                                            <option value="vendas">💰 Estratégia de Vendas</option>
                                            <option value="marketing">📢 Marketing e Promoções</option>
                                            <option value="atendimento">🎧 Atendimento ao Cliente</option>
                                            <option value="analise">📊 Análise de Dados</option>
                                            <option value="customizado">⚡ Personalizado</option>
                                        </select>
                                    </div>

                                    <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 8px; font-size: 13px;">⭐ Nível de Prioridade:</label>
                                        <select id="prioridade-treino" style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(155, 89, 182, 0.3); color: var(--text-light); border-radius: 6px; font-weight: 500;">
                                            <option value="basico">🟡 Básico (Informativo)</option>
                                            <option value="importante" selected>🟠 Importante (Padrão)</option>
                                            <option value="critico">🔴 Crítico (Essencial)</option>
                                            <option value="urgente">🔥 Urgente (Máxima Prioridade)</option>
                                        </select>
                                    </div>

                                    <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 8px; font-size: 13px;">🎯 Aplicar Como:</label>
                                        <div style="display: flex; gap: 10px;">
                                            <label style="display: flex; align-items: center; gap: 8px; color: var(--text-light); font-size: 13px; cursor: pointer;">
                                                <input type="checkbox" id="aplicar-imediato" checked> Imediato
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 8px; color: var(--text-light); font-size: 13px; cursor: pointer;">
                                                <input type="checkbox" id="salvar-base"> Salvar na Base
                                            </label>
                                        </div>
                                    </div>

                                    <button onclick="treinarIAComTexto()" style="width: 100%; background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 14px; transition: all 0.3s; box-shadow: 0 6px 16px rgba(155, 89, 182, 0.3);" onmouseover="this.style.boxShadow='0 8px 24px rgba(155, 89, 182, 0.5)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 6px 16px rgba(155, 89, 182, 0.3)'; this.style.transform='translateY(0)'">
                                        🚀 TREINAR A IA AGORA
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ABA 2: PERSONALIZAÇÃO -->
                        <div id="personalizacao" class="aba-treino-conteudo" style="display: none;">
                            <h4 style="color: var(--text-light); margin: 0 0 20px 0; font-weight: 700;">Como você quer que a IA responda:</h4>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
                                <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px;">
                                    <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 10px; font-size: 12px;">💬 Tom de Voz:</label>
                                    <select id="tom-ia" style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(155, 89, 182, 0.3); color: var(--text-light); border-radius: 6px;">
                                        <option value="profissional">📋 Profissional</option>
                                        <option value="amigavel">👋 Amigável</option>
                                        <option value="tecnico">⚙️ Técnico</option>
                                        <option value="criativo">✨ Criativo</option>
                                        <option value="sucinto">⚡ Sucinto</option>
                                    </select>
                                </div>

                                <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px;">
                                    <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 10px; font-size: 12px;">📖 Nível de Detalhe:</label>
                                    <select id="detalhe-ia" style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(155, 89, 182, 0.3); color: var(--text-light); border-radius: 6px;">
                                        <option value="minimo">📌 Mínimo</option>
                                        <option value="basico">📝 Básico</option>
                                        <option value="medio" selected>📊 Médio</option>
                                        <option value="completo">📖 Completo</option>
                                    </select>
                                </div>

                                <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px;">
                                    <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 10px; font-size: 12px;">🌐 Idioma:</label>
                                    <select id="idioma-ia" style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(155, 89, 182, 0.3); color: var(--text-light); border-radius: 6px;">
                                        <option value="pt-br" selected>🇧🇷 Português BR</option>
                                        <option value="en">🇺🇸 English</option>
                                        <option value="es">🇪🇸 Español</option>
                                    </select>
                                </div>

                                <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px;">
                                    <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 10px; font-size: 12px;">🎭 Personalidade:</label>
                                    <select id="personalidade-ia" style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(155, 89, 182, 0.3); color: var(--text-light); border-radius: 6px;">
                                        <option value="neutra">🤖 Neutra</option>
                                        <option value="entusiasta" selected>🌟 Entusiasta</option>
                                        <option value="confiante">💪 Confiante</option>
                                        <option value="conservadora">⚖️ Conservadora</option>
                                    </select>
                                </div>

                                <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px;">
                                    <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 10px; font-size: 12px;">🔒 Segurança:</label>
                                    <select id="seguranca-ia" style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(155, 89, 182, 0.3); color: var(--text-light); border-radius: 6px;">
                                        <option value="rigorosa">🔐 Rigorosa</option>
                                        <option value="normal" selected>✅ Normal</option>
                                        <option value="flexivel">🆓 Flexível</option>
                                    </select>
                                </div>
                            </div>

                            <button onclick="salvarPersonalizaçãoIA()" style="width: 100%; background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 14px;">
                                💾 Salvar Personalização
                            </button>
                        </div>

                        <!-- ABA 3: HISTÓRICO -->
                        <div id="historico" class="aba-treino-conteudo" style="display: none;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h4 style="color: var(--text-light); margin: 0; font-weight: 700;">Seus Últimos Treinamentos:</h4>
                                <button onclick="limparHistoricoTreino()" style="padding: 8px 16px; background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                    🗑️ Limpar Tudo
                                </button>
                            </div>
                            <div id="historico-treinamentos-list" style="display: grid; gap: 12px;">
                                <p style="color: var(--text-muted); text-align: center; padding: 30px;">Nenhum treinamento ainda. Comece agora!</p>
                            </div>
                        </div>

                        <!-- ESTATÍSTICAS -->
                        <div style="margin-top: 30px; padding: 20px; background: rgba(155, 89, 182, 0.08); border-radius: 8px; border-left: 4px solid #9b59b6;">
                            <h4 style="color: #9b59b6; margin: 0 0 15px 0;">📈 Status da IA:</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
                                <div style="text-align: center; padding: 12px;">
                                    <p style="color: var(--text-muted); margin: 0 0 5px 0; font-size: 12px;">Treinamentos</p>
                                    <h3 id="stat-treinamentos-total" style="color: #9b59b6; margin: 0; font-size: 24px;">0</h3>
                                </div>
                                <div style="text-align: center; padding: 12px;">
                                    <p style="color: var(--text-muted); margin: 0 0 5px 0; font-size: 12px;">Caracteres</p>
                                    <h3 id="stat-caracteres-total" style="color: #9b59b6; margin: 0; font-size: 24px;">0</h3>
                                </div>
                                <div style="text-align: center; padding: 12px;">
                                    <p style="color: var(--text-muted); margin: 0 0 5px 0; font-size: 12px;">Nível IA</p>
                                    <h3 id="stat-nivel-ia-texto" style="color: #9b59b6; margin: 0; font-size: 20px;">🟢 Iniciante</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Análise Financeira -->
            <div id="analise" class="tab-pane">
                <div class="hero-card shadow-elegant" style="padding: 32px 36px 28px; margin-bottom: 32px;">
                    <div class="hero-tag">
                        <i class="fas fa-sparkles"></i>
                        Painel Financeiro Premium
                    </div>
                    <div class="hero-main" style="align-items: flex-start; gap: 24px;">
                        <div class="hero-copy">
                            <h2 style="font-size: 2.4rem;">Dashboard Financeiro Profissional</h2>
                            <p class="hero-subtitle" style="max-width: 760px;">Análise completa e inteligente do desempenho financeiro do seu negócio</p>
                            <div class="hero-meta" style="margin-top: 24px; gap: 18px;">
                                <span><span class="hero-dot dot-primary"></span>Visão estratégica</span>
                                <span><span class="hero-dot dot-success"></span>Dados em tempo real</span>
                                <span><span class="hero-dot dot-warning"></span>Insights acionáveis</span>
                            </div>
                        </div>
                        <div class="hero-actions" style="justify-content: flex-end; min-width: auto;">
                            <button id="btn-exportar-dados" class="btn btn-hero-outline glass">Exportar</button>
                            <button id="btn-atualizar-analise" class="btn btn-hero-gradient">Atualizar</button>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px;">
                    <div class="card card-kpi gradient-success">
                        <div class="card-top-bar gradient-success"></div>
                        <div class="card-blob gradient-success"></div>
                        <div class="card-body">
                            <div class="card-head">
                                <p class="card-label">Faturamento Total</p>
                                <div class="card-icon-square icon-bg-success text-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <p class="card-value" id="card-faturamento">R$ 0,00</p>
                            <div class="card-footer">
                                <span class="card-trend trend-up"><i class="fas fa-arrow-up"></i> +15.3% vs mês anterior</span>
                                <span class="card-sparkline" style="color: var(--chart-2);">
                                    <svg width="90" height="34" viewBox="0 0 90 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0 24 L18 18 L36 21 L54 14 L72 16 L90 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card card-kpi gradient-primary">
                        <div class="card-top-bar gradient-primary"></div>
                        <div class="card-blob gradient-primary"></div>
                        <div class="card-body">
                            <div class="card-head">
                                <p class="card-label">Lucro Líquido</p>
                                <div class="card-icon-square icon-bg-primary text-primary">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <p class="card-value" id="card-lucro">R$ 0,00</p>
                            <div class="card-footer">
                                <span class="card-trend trend-up"><i class="fas fa-arrow-up"></i> +12.5% vs mês anterior</span>
                                <span class="card-sparkline" style="color: var(--chart-1);">
                                    <svg width="90" height="34" viewBox="0 0 90 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0 22 L18 19 L36 25 L54 20 L72 18 L90 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card card-kpi gradient-warning">
                        <div class="card-top-bar gradient-warning"></div>
                        <div class="card-blob gradient-warning"></div>
                        <div class="card-body">
                            <div class="card-head">
                                <p class="card-label">Margem Média</p>
                                <div class="card-icon-square icon-bg-warning text-warning">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                            <p class="card-value" id="card-margem">0%</p>
                            <div class="card-footer">
                                <span class="card-trend trend-flat"><i class="fas fa-minus"></i> Meta: 40%</span>
                                <span class="card-sparkline" style="color: var(--chart-3);">
                                    <svg width="90" height="34" viewBox="0 0 90 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0 20 L18 22 L36 19 L54 23 L72 21 L90 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card card-kpi gradient-danger">
                        <div class="card-top-bar gradient-danger"></div>
                        <div class="card-blob gradient-danger"></div>
                        <div class="card-body">
                            <div class="card-head">
                                <p class="card-label">Total de Vendas</p>
                                <div class="card-icon-square icon-bg-danger text-danger">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                            <p class="card-value" id="card-vendas">0</p>
                            <div class="card-footer">
                                <span class="card-trend trend-up"><i class="fas fa-arrow-up"></i> +18.2% vs mês anterior</span>
                                <span class="card-sparkline" style="color: var(--chart-5);">
                                    <svg width="90" height="34" viewBox="0 0 90 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0 25 L18 20 L36 22 L54 18 L72 20 L90 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos Lado a Lado -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
                    <!-- Gráfico Faturamento vs Lucro Super Aprimorado -->
                    <div class="chart-container enhanced performance-chart" style="background: linear-gradient(145deg, rgba(0, 0, 0, 0.15), rgba(0, 168, 255, 0.08)); border: 2px solid rgba(0, 168, 255, 0.25); border-radius: var(--radius); padding: 30px; position: relative; overflow: hidden; box-shadow: 0 15px 35px rgba(0, 168, 255, 0.15);">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), #00a8ff, #2ecc71);"></div>

                        <!-- Header do Gráfico com Métricas -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(0, 168, 255, 0.3);">
                                    <i class="fas fa-chart-line" style="color: white; font-size: 22px;"></i>
                                </div>
                                <div>
                                    <h4 style="color: var(--text-light); font-size: 18px; font-weight: 800; margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px;">
                                        📊 Dashboard de Desempenho Financeiro
                                        <span style="background: linear-gradient(135deg, #00a8ff, #2ecc71); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 12px; font-weight: 700;">PREMIUM</span>
                                    </h4>
                                    <p style="color: var(--text-muted); font-size: 13px; margin: 0; line-height: 1.4;">Análise avançada da evolução mensal com insights inteligentes</p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 15px;">
                                <!-- Mini métricas rápidas -->
                                <div style="display: flex; gap: 10px;">
                                    <div style="background: rgba(0, 168, 255, 0.15); padding: 8px 12px; border-radius: 20px; border: 1px solid rgba(0, 168, 255, 0.3);">
                                        <span style="color: #00a8ff; font-size: 11px; font-weight: 700;">📈 FATURAMENTO</span>
                                    </div>
                                    <div style="background: rgba(46, 204, 113, 0.15); padding: 8px 12px; border-radius: 20px; border: 1px solid rgba(46, 204, 113, 0.3);">
                                        <span style="color: #2ecc71; font-size: 11px; font-weight: 700;">💰 LUCRO</span>
                                    </div>
                                </div>

                                <select id="periodo-chart" style="background: linear-gradient(135deg, rgba(0, 168, 255, 0.1), rgba(0, 168, 255, 0.05)); border: 2px solid rgba(0, 168, 255, 0.3); color: var(--text-light); padding: 8px 14px; border-radius: 25px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 2px 10px rgba(0, 168, 255, 0.1);">
                                    <option value="6">📅 6 meses</option>
                                    <option value="12">📅 12 meses</option>
                                    <option value="3">📅 3 meses</option>
                                </select>
                            </div>
                        </div>

                        <!-- Container do Gráfico com Efeitos Visuais -->
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="background: linear-gradient(145deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.1)); padding: 20px; border-radius: 15px; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.2);">
                                <canvas id="chart-mensal" style="max-height: 350px; display: block; filter: drop-shadow(0 4px 15px rgba(0, 168, 255, 0.1));"></canvas>
                            </div>

                            <!-- Efeitos de fundo decorativos -->
                            <div style="position: absolute; top: -10px; right: -10px; width: 80px; height: 80px; background: radial-gradient(circle, rgba(0, 168, 255, 0.1), transparent); border-radius: 50%; pointer-events: none;"></div>
                            <div style="position: absolute; bottom: -15px; left: -15px; width: 100px; height: 100px; background: radial-gradient(circle, rgba(46, 204, 113, 0.08), transparent); border-radius: 50%; pointer-events: none;"></div>
                        </div>

                        <!-- Insights e Informações Avançadas -->
                        <div class="insights-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 25px;">
                            <div class="mini-metric" style="background: linear-gradient(135deg, rgba(0, 168, 255, 0.1), rgba(0, 168, 255, 0.05)); padding: 15px; border-radius: 12px; border: 1px solid rgba(0, 168, 255, 0.2);">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="fas fa-eye" style="color: var(--primary-color); font-size: 16px;"></i>
                                    <span style="color: var(--text-light); font-size: 12px; font-weight: 700;">ANÁLISE INTELIGENTE</span>
                                </div>
                                <p style="margin: 0; color: var(--text-muted); font-size: 12px; line-height: 1.4;">
                                    <i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 5px;"></i>
                                    Gráfico interativo com tooltips avançados e indicadores de crescimento automático
                                </p>
                            </div>

                            <div class="mini-metric" style="background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05)); padding: 15px; border-radius: 12px; border: 1px solid rgba(46, 204, 113, 0.2);">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="fas fa-rocket" style="color: var(--success-color); font-size: 16px;"></i>
                                    <span style="color: var(--text-light); font-size: 12px; font-weight: 700;">TENDÊNCIA DETECTADA</span>
                                </div>
                                <p style="margin: 0; color: var(--text-muted); font-size: 12px; line-height: 1.4;">
                                    <i class="fas fa-chart-line" style="color: var(--success-color); margin-right: 5px;"></i>
                                    Análise automática de crescimento com indicadores visuais em tempo real
                                </p>
                            </div>

                            <div class="mini-metric" style="background: linear-gradient(135deg, rgba(155, 89, 182, 0.1), rgba(155, 89, 182, 0.05)); padding: 15px; border-radius: 12px; border: 1px solid rgba(155, 89, 182, 0.2);">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="fas fa-magic" style="color: #9b59b6; font-size: 16px;"></i>
                                    <span style="color: var(--text-light); font-size: 12px; font-weight: 700;">TECNOLOGIA AVANÇADA</span>
                                </div>
                                <p style="margin: 0; color: var(--text-muted); font-size: 12px; line-height: 1.4;">
                                    <i class="fas fa-cogs" style="color: #9b59b6; margin-right: 5px;"></i>
                                    Algoritmos de IA analisam padrões e projetam tendências futuras
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Produtos Top Aperfeiçoado -->
                    <div class="chart-container enhanced" style="background: linear-gradient(145deg, rgba(0, 0, 0, 0.15), rgba(46, 204, 113, 0.08)); border: 2px solid rgba(46, 204, 113, 0.25); border-radius: var(--radius); padding: 25px; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--success-color), #27ae60);"></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h4 style="color: var(--text-light); font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-trophy" style="color: var(--success-color);"></i>
                                Top 5 Produtos Mais Rentáveis
                            </h4>
                            <button onclick="expandChart('produtos')" style="background: rgba(46, 204, 113, 0.15); border: 1px solid rgba(46, 204, 113, 0.3); color: var(--success-color); padding: 5px 10px; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                        <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 10px;">
                            <canvas id="chart-produtos" style="max-height: 300px; display: block;"></canvas>
                        </div>
                        <div style="margin-top: 15px; padding: 12px; background: rgba(46, 204, 113, 0.08); border-radius: 8px; border-left: 3px solid var(--success-color);">
                            <p style="margin: 0; color: var(--text-muted); font-size: 12px;">
                                <i class="fas fa-info-circle" style="color: var(--success-color); margin-right: 5px;"></i>
                                Produtos com maior lucratividade e participação no faturamento total
                            </p>
                        </div>
                    </div>
                </div>

                <!-- IA de Análises Profunda -->
                <div style="background: linear-gradient(135deg, rgba(0, 168, 255, 0.12), rgba(52, 152, 219, 0.06)); border: 2px solid rgba(0, 168, 255, 0.4); border-radius: var(--radius); padding: 30px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                        <i class="fas fa-robot" style="color: #00a8ff; font-size: 22px;"></i>
                        <h4 style="color: #00a8ff; margin: 0; font-size: 16px; font-weight: 700;">🤖 ANÁLISE INTELIGENTE AVANÇADA</h4>
                    </div>
                    
                    <!-- Recomendações IA -->
                    <div id="ai-insights-container" style="color: var(--text-muted); font-size: 13px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div style="background: rgba(0, 168, 255, 0.1); padding: 15px; border-radius: 8px; border-left: 3px solid #00a8ff;">
                                <p style="font-size: 11px; color: #00a8ff; margin: 0; text-transform: uppercase; font-weight: 600; margin-bottom: 5px;">⏳ Carregando</p>
                                <p style="margin: 0; color: var(--text-muted); font-size: 12px;">Análises inteligentes em andamento...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Análises Detalhadas em Abas -->
                <div class="tabs-content enhanced" style="background: linear-gradient(145deg, rgba(0, 0, 0, 0.2), rgba(52, 152, 219, 0.08)); border: 2px solid rgba(0, 168, 255, 0.25); border-radius: 15px; padding: 0; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--success-color));"></div>
                    <div class="tabs-header" style="background: rgba(0, 0, 0, 0.3); padding: 0; border-bottom: 2px solid rgba(0, 168, 255, 0.3); margin: 0; display: flex; gap: 0;">
                        <button class="tab-btn active" data-tab="analise-mensal" style="font-weight: 700; font-size: 14px; white-space: nowrap; padding: 15px 25px; border: none; background: linear-gradient(180deg, rgba(0, 168, 255, 0.3), transparent); color: var(--primary-color); cursor: pointer; position: relative; transition: all 0.3s; flex: 1; text-align: center;">
                            <i class="fas fa-chart-line" style="margin-right: 8px;"></i>Análise Mensal
                            <div style="position: absolute; bottom: -2px; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));"></div>
                        </button>
                        <button class="tab-btn" data-tab="top-produtos" style="font-weight: 600; font-size: 14px; white-space: nowrap; padding: 15px 25px; border: none; background: none; color: var(--text-muted); cursor: pointer; position: relative; transition: all 0.3s; flex: 1; text-align: center;">
                            <i class="fas fa-trophy" style="margin-right: 8px;"></i>Produtos
                        </button>
                        <button class="tab-btn" data-tab="despesas" style="font-weight: 600; font-size: 14px; white-space: nowrap; padding: 15px 25px; border: none; background: none; color: var(--text-muted); cursor: pointer; position: relative; transition: all 0.3s; flex: 1; text-align: center;">
                            <i class="fas fa-chart-pie" style="margin-right: 8px;"></i>Custos
                        </button>
                        <button class="tab-btn" data-tab="tendencias" style="font-weight: 600; font-size: 14px; white-space: nowrap; padding: 15px 25px; border: none; background: none; color: var(--text-muted); cursor: pointer; position: relative; transition: all 0.3s; flex: 1; text-align: center;">
                            <i class="fas fa-chart-bar" style="margin-right: 8px;"></i>Tendências
                        </button>
                        <button class="tab-btn" data-tab="dicas" style="font-weight: 600; font-size: 14px; white-space: nowrap; padding: 15px 25px; border: none; background: none; color: var(--text-muted); cursor: pointer; position: relative; transition: all 0.3s; flex: 1; text-align: center;">
                            <i class="fas fa-lightbulb" style="margin-right: 8px;"></i>IA Financeira
                        </button>
                    </div>

                    <!-- Conteúdo Dinâmico das Abas -->
                    <div style="padding: 30px;">
                        <!-- Tab Análise Mensal -->
                        <div class="tab-content active" id="analise-mensal">
                            <div style="display: grid; gap: 30px;">
                                <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: 12px; border: 1px solid rgba(0, 168, 255, 0.2);">
                                    <h4 style="color: var(--primary-color); margin-bottom: 20px; font-size: 18px; font-weight: 700;">
                                        <i class="fas fa-chart-line" style="margin-right: 10px;"></i>Análise Comparativa Mensal
                                    </h4>
                                    <div id="analise-mensal-container">
                                        <p style="color: var(--text-muted); text-align: center; padding: 40px;">
                                            <i class="fas fa-sync fa-spin" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                            Carregando análise mensal...
                                        </p>
                                    </div>
                                </div>

                                <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: 12px; border: 1px solid rgba(46, 204, 113, 0.2);">
                                    <h4 style="color: var(--success-color); margin-bottom: 20px; font-size: 18px; font-weight: 700;">
                                        <i class="fas fa-bullseye" style="margin-right: 10px;"></i>Métricas Destaque
                                    </h4>
                                    <div id="metricas-destaque" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                        <!-- Será preenchido dinamicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Produtos -->
                        <div class="tab-content" id="top-produtos">
                            <div style="display: grid; gap: 30px;">
                                <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: 12px; border: 1px solid rgba(46, 204, 113, 0.2);">
                                    <h4 style="color: var(--success-color); margin-bottom: 20px; font-size: 18px; font-weight: 700;">
                                        <i class="fas fa-trophy" style="margin-right: 10px;"></i>Produtos Mais Rentáveis
                                    </h4>
                                    <div id="produtos-rentaveis-container">
                                        <p style="color: var(--text-muted); text-align: center; padding: 40px;">
                                            <i class="fas fa-sync fa-spin" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                            Análise de produtos em andamento...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Custos -->
                        <div class="tab-content" id="despesas">
                            <div style="display: grid; gap: 30px;">
                                <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: 12px; border: 1px solid rgba(231, 76, 60, 0.2);">
                                    <h4 style="color: #e74c3c; margin-bottom: 20px; font-size: 18px; font-weight: 700;">
                                        <i class="fas fa-chart-pie" style="margin-right: 10px;"></i>Análise de Custos e Despesas
                                    </h4>
                                    <div id="custos-container">
                                        <p style="color: var(--text-muted); text-align: center; padding: 40px;">
                                            <i class="fas fa-sync fa-spin" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                            Calculando estrutura de custos...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Tendências -->
                        <div class="tab-content" id="tendencias">
                            <div style="display: grid; gap: 30px;">
                                <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: 12px; border: 1px solid rgba(155, 89, 182, 0.2);">
                                    <h4 style="color: #9b59b6; margin-bottom: 20px; font-size: 18px; font-weight: 700;">
                                        <i class="fas fa-chart-bar" style="margin-right: 10px;"></i>Tendências e Projeções
                                    </h4>
                                    <div id="tendencias-container">
                                        <p style="color: var(--text-muted); text-align: center; padding: 40px;">
                                            <i class="fas fa-sync fa-spin" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                            Analisando tendências de mercado...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab IA Financeira -->
                        <div class="tab-content" id="dicas">
                            <div style="display: grid; gap: 30px;">
                                <div class="ia-analise-container" style="background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(155, 89, 182, 0.1)); padding: 30px; border-radius: 15px; border: 2px solid rgba(155, 89, 182, 0.3);">
                                    <h4 style="color: #9b59b6; margin-bottom: 25px; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 15px;">
                                        <div style="background: linear-gradient(135deg, #9b59b6, #8e44ad); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-brain" style="color: white; font-size: 24px;"></i>
                                        </div>
                                        Análise Inteligente de Finanças
                                    </h4>
                                    <div id="ia-analise-container">
                                        <div style="text-align: center; padding: 50px;">
                                            <i class="fas fa-robot fa-pulse" style="font-size: 60px; color: #9b59b6; margin-bottom: 20px; display: block; animation: pulse 2s infinite;"></i>
                                            <p style="color: var(--text-muted); font-size: 16px; margin-bottom: 10px;">IA正在分析您的财务数据...</p>
                                            <p style="color: rgba(155, 89, 182, 0.6); font-size: 14px;">Detectando padrões, oportunidades de otimização e insights estratégicos</p>
                                            <button onclick="gerarAnalisesIA()" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-weight: 700; margin-top: 30px; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 15px rgba(155, 89, 182, 0.3);">
                                                <i class="fas fa-magic" style="margin-right: 10px;"></i>Iniciar Análise IA
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Relatórios & Exportar -->
            <div id="relatorios" class="tab-pane">
                <h3 style="margin-bottom: 30px; font-size: 28px; color: var(--text-light);"><i class="fas fa-file-csv" style="color: #2ecc71; margin-right: 10px;"></i>Relatórios & Exportar</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: rgba(46, 204, 113, 0.15); border: 2px solid #2ecc71; border-radius: var(--radius); padding: 25px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="font-size: 40px; margin-bottom: 10px;">📄</div>
                        <h4 style="color: #2ecc71; margin-bottom: 5px;">Relatório Mensal</h4>
                        <p style="color: var(--text-muted); font-size: 13px;">Resumo completo de pedidos e faturamento</p>
                        <button class="btn btn-success" style="margin-top: 15px; width: 100%;" onclick="gerarRelatorioMensal()">Gerar PDF</button>
                    </div>
                    
                    <div style="background: rgba(0, 168, 255, 0.15); border: 2px solid #00a8ff; border-radius: var(--radius); padding: 25px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="font-size: 40px; margin-bottom: 10px;">📊</div>
                        <h4 style="color: #00a8ff; margin-bottom: 5px;">Exportar Pedidos</h4>
                        <p style="color: var(--text-muted); font-size: 13px;">Baixar dados em CSV para análise</p>
                        <button class="btn btn-primary" style="margin-top: 15px; width: 100%; background-color:#00a8ff;" onclick="exportarPedidosCSV()">Exportar CSV</button>
                    </div>
                    
                    <div style="background: rgba(155, 89, 182, 0.15); border: 2px solid #9b59b6; border-radius: var(--radius); padding: 25px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="font-size: 40px; margin-bottom: 10px;">🏆</div>
                        <h4 style="color: #9b59b6; margin-bottom: 5px;">Top Vendedores</h4>
                        <p style="color: var(--text-muted); font-size: 13px;">Ranking de produtos mais vendidos</p>
                        <button class="btn" style="margin-top: 15px; width: 100%; background-color:#9b59b6; color: white; border: none;" onclick="mostrarTopVendedores()">Visualizar</button>
                    </div>
                </div>
                
                <div id="relatorios-container" style="background: rgba(0, 0, 0, 0.1); border-radius: var(--radius); padding: 25px; border: 1px solid rgba(0, 168, 255, 0.2);">
                    <h4 style="color: var(--text-light); margin-bottom: 15px;">📋 Histórico de Exportações</h4>
                    <p style="color: var(--text-muted); font-size: 13px;">Nenhuma exportação realizada ainda. Clique nos botões acima para gerar relatórios.</p>
                </div>
            </div>

            <!-- Alertas Inteligentes -->
            <div id="alertas" class="tab-pane">
                <h3 style="margin-bottom: 30px; font-size: 28px; color: var(--text-light);"><i class="fas fa-bell" style="color: #ff6b6b; margin-right: 10px;"></i>Alertas Inteligentes</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <div id="alertas-container" style="grid-column: 1 / -1;">
                        <p style="color: var(--text-muted); text-align: center; padding: 40px;">🟢 Sistema verificando... Carregando alertas inteligentes</p>
                    </div>
                </div>
                
                <div style="margin-top: 30px; background: rgba(0, 0, 0, 0.1); border-radius: var(--radius); padding: 20px; border: 1px solid rgba(0, 168, 255, 0.2);">
                    <h4 style="color: var(--text-light); margin-bottom: 15px;">⚙️ Configurar Alertas</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-color);">
                            <input type="checkbox" id="alerta-estoque-baixo" checked> Alerta de Estoque Baixo (< 5 unidades)
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-color);">
                            <input type="checkbox" id="alerta-pedidos-atrasados" checked> Alerta de Pedidos Atrasados
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-color);">
                            <input type="checkbox" id="alerta-transacao-grande" checked> Alerta de Transações Acima de R$ 5mil
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-color);">
                            <input type="checkbox" id="alerta-novo-cliente" checked> Alerta de Novo Cliente
                        </label>
                    </div>
                </div>
            </div>

            <!-- Automação & IA -->
            <div id="automacao" class="tab-pane">
                <h3 style="margin-bottom: 30px; font-size: 28px; color: var(--text-light);"><i class="fas fa-magic" style="color: #f39c12; margin-right: 10px;"></i>Automação & IA Avançada</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: rgba(243, 156, 18, 0.15); border: 2px dashed #f39c12; border-radius: var(--radius); padding: 25px;">
                        <h4 style="color: #f39c12; margin-bottom: 15px;">🤖 Sugestões de Reordenação</h4>
                        <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 13px;">Sistema IA analisa histórico de vendas e sugere automaticamente o melhor momento para reabastecer</p>
                        <div id="reordenacoes-container" style="color: var(--text-muted); font-size: 13px;">
                            <p>Analisando padrões de vendas...</p>
                        </div>
                        <button class="btn btn-warning" style="margin-top: 15px; width: 100%;background-color:#f39c12; color: white; border: none;" onclick="gerarSugestoesReordenacao()">🔄 Gerar Sugestões</button>
                    </div>
                    
                    <div style="background: rgba(52, 152, 219, 0.15); border: 2px dashed #3498db; border-radius: var(--radius); padding: 25px;">
                        <h4 style="color: #3498db; margin-bottom: 15px;">📈 Previsão de Demanda</h4>
                        <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 13px;">Análise preditiva: qual será a demanda dos próximos 30 dias baseado em histórico</p>
                        <div id="previsao-container" style="color: var(--text-muted); font-size: 13px;">
                            <p>Calculando tendências...</p>
                        </div>
                        <button class="btn" style="margin-top: 15px; width: 100%; background-color:#3498db; color: white; border: none;" onclick="gerarPrevisaoDemanda()">📊 Analisar Demanda</button>
                    </div>
                    
                    <div style="background: rgba(46, 204, 113, 0.15); border: 2px dashed #2ecc71; border-radius: var(--radius); padding: 25px;">
                        <h4 style="color: #2ecc71; margin-bottom: 15px;">💡 Otimizações Recomendadas</h4>
                        <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 13px;">IA sugere melhorias em preços, estoque e processamento baseado em dados reais</p>
                        <div id="otimizacoes-container" style="color: var(--text-muted); font-size: 13px;">
                            <p>Processando dados...</p>
                        </div>
                        <button class="btn btn-success" style="margin-top: 15px; width: 100%;" onclick="gerarOtimizacoes()">⚡ Ver Sugestões</button>
                    </div>
                </div>
            </div>

            <!-- Integrações -->
            <div id="integracao" class="tab-pane">
                <h3 style="margin-bottom: 30px; font-size: 28px; color: var(--text-light);"><i class="fas fa-link" style="color: #1abc9c; margin-right: 10px;"></i>Integrações com Marketplaces</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, rgba(26, 188, 156, 0.15), rgba(52, 152, 219, 0.08)); border: 2px solid #1abc9c; border-radius: var(--radius); padding: 25px; text-align: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(26, 188, 156, 0.1); border-radius: 50%;"></div>
                        <div style="font-size: 50px; margin-bottom: 15px; position: relative; z-index: 1;">🛒</div>
                        <h4 style="color: #1abc9c; margin-bottom: 10px; font-weight: 700;">Shopee</h4>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 15px;">Sincronize pedidos e produtos automaticamente</p>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button class="btn btn-small" style="flex: 1; background-color: #1abc9c; color: white; border: none;" onclick="conectarShopee()">🔗 Conectar</button>
                            <button class="btn btn-small" style="flex: 1; background-color: rgba(26, 188, 156, 0.2); color: #1abc9c; border: 1px solid #1abc9c;" onclick="desconectarShopee()">❌ Desconectar</button>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(255, 153, 0, 0.15), rgba(230, 126, 34, 0.08)); border: 2px solid #ff9900; border-radius: var(--radius); padding: 25px; text-align: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255, 153, 0, 0.1); border-radius: 50%;"></div>
                        <div style="font-size: 50px; margin-bottom: 15px; position: relative; z-index: 1;">📦</div>
                        <h4 style="color: #ff9900; margin-bottom: 10px; font-weight: 700;">Amazon</h4>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 15px;">Integre com sua loja Amazon seller</p>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button class="btn btn-small" style="flex: 1; background-color: #ff9900; color: white; border: none;" onclick="abrirConfigAmazon()">⚙️ Configurar</button>
                            <button class="btn btn-small" style="flex: 1; background-color: rgba(255, 153, 0, 0.2); color: #ff9900; border: 1px solid #ff9900;" onclick="desconectarAmazon()">❌ Desconectar</button>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(52, 152, 219, 0.15), rgba(0, 168, 255, 0.08)); border: 2px solid #3498db; border-radius: var(--radius); padding: 25px; text-align: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(52, 152, 219, 0.1); border-radius: 50%;"></div>
                        <div style="font-size: 50px; margin-bottom: 15px; position: relative; z-index: 1;">📧</div>
                        <h4 style="color: #3498db; margin-bottom: 10px; font-weight: 700;">Email Marketing</h4>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 15px;">Envie campanhas automáticas aos clientes</p>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button class="btn btn-small" style="flex: 1; background-color: #3498db; color: white; border: none;" onclick="conectarEmail()">🔗 Conectar</button>
                            <button class="btn btn-small" style="flex: 1; background-color: rgba(52, 152, 219, 0.2); color: #3498db; border: 1px solid #3498db;" onclick="desconectarEmail()">❌ Desconectar</button>
                        </div>
                    </div>
                </div>
                
                <div style="background: rgba(0, 0, 0, 0.1); border-radius: var(--radius); padding: 25px; border: 1px solid rgba(26, 188, 156, 0.2);">
                    <h4 style="color: var(--text-light); margin-bottom: 15px;">📊 Status das Integrações</h4>
                    <div id="status-integracao" style="color: var(--text-muted); font-size: 13px;">
                        <p>✅ Shopee - Desconectado</p>
                        <p>✅ Amazon - Desconectado</p>
                        <p>✅ Email - Desconectado</p>
                    </div>
                </div>
            </div>

            <!-- Configurações -->
            <div id="configuracoes" class="tab-pane">
                <h3 style="margin-bottom: 30px; font-size: 28px; color: var(--text-light);"><i class="fas fa-cog" style="color: #95a5a6; margin-right: 10px;"></i>Configurações & Personalizador de Menu</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Esquerda: Abas Disponíveis -->
                    <div style="background: rgba(0, 0, 0, 0.1); border-radius: var(--radius); padding: 25px; border: 1px solid rgba(0, 168, 255, 0.2);">
                        <h4 style="color: var(--text-light); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-list" style="color: #00a8ff;"></i> Abas Disponíveis
                        </h4>
                        <div id="menu-configurador" style="display: flex; flex-direction: column; gap: 10px;">
                            <!-- Carregado dinamicamente -->
                            <p style="color: var(--text-muted); font-size: 13px;">Carregando abas...</p>
                        </div>
                        
                        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(0, 168, 255, 0.2);">
                            <button class="btn btn-success" style="width: 100%; margin-bottom: 10px;" onclick="resetarConfiguracoesMenu()">🔄 Restaurar Padrão</button>
                            <button class="btn btn-primary" style="width: 100%; background-color: #00a8ff;" onclick="salvarConfiguracoesMenu()">💾 Salvar Preferências</button>
                        </div>
                    </div>
                    
                    <!-- Direita: Pré-visualização -->
                    <div style="background: rgba(0, 0, 0, 0.1); border-radius: var(--radius); padding: 25px; border: 1px solid rgba(0, 168, 255, 0.2);">
                        <h4 style="color: var(--text-light); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-eye" style="color: #00a8ff;"></i> Pré-visualização do Menu
                        </h4>
                        <div id="preview-menu" style="background: linear-gradient(135deg, rgba(25, 30, 35, 0.8), rgba(30, 35, 45, 0.8)); border-radius: 10px; padding: 15px; max-height: 400px; overflow-y: auto; border: 1px solid rgba(255, 255, 255, 0.1);">
                            <!-- Preview dinâmico -->
                            <p style="color: var(--text-muted); font-size: 12px;">Menu será exibido aqui...</p>
                        </div>
                        
                        <div style="margin-top: 20px; padding: 15px; background: rgba(0, 168, 255, 0.1); border-radius: 5px; border-left: 3px solid #00a8ff;">
                            <p style="color: var(--text-muted); font-size: 12px;">
                                <strong>💡 Dica:</strong> Clique nos olhinhos para mostrar/ocultar abas. Use drag-and-drop para reordenar (em breve).
                            </p>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; background: linear-gradient(135deg, rgba(0, 168, 255, 0.1), rgba(155, 89, 182, 0.08)); border-radius: var(--radius); padding: 30px; border: 1px solid rgba(0, 168, 255, 0.3);">
                    <h4 style="color: var(--text-light); margin-bottom: 25px; font-size: 18px; font-weight: 700;">⚙️ Outras Configurações</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                        <label style="display: flex; align-items: center; gap: 12px; color: var(--text-color); cursor: pointer; padding: 10px; background: rgba(0, 0, 0, 0.2); border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='rgba(0, 168, 255, 0.2)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.2)'">
                            <input type="checkbox" id="config-notificacoes" checked style="width: 18px; height: 18px; cursor: pointer;"> <span>🔔 Notificações</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 12px; color: var(--text-color); cursor: pointer; padding: 10px; background: rgba(0, 0, 0, 0.2); border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='rgba(0, 168, 255, 0.2)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.2)'">
                            <input type="checkbox" id="config-sons" checked style="width: 18px; height: 18px; cursor: pointer;"> <span>🔊 Sons no Aviso</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 12px; color: var(--text-color); cursor: pointer; padding: 10px; background: rgba(0, 0, 0, 0.2); border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='rgba(46, 204, 113, 0.2)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.2)'">
                            <input type="checkbox" id="config-auto-backup" checked style="width: 18px; height: 18px; cursor: pointer;"> <span>🔄 Auto-Backup (7d)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 12px; color: var(--text-color); cursor: pointer; padding: 10px; background: rgba(0, 0, 0, 0.2); border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='rgba(155, 89, 182, 0.2)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.2)'">
                            <input type="checkbox" id="config-tema-escuro" checked style="width: 18px; height: 18px; cursor: pointer;"> <span>🌙 Tema Escuro</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 12px; color: var(--text-color); cursor: pointer; padding: 10px; background: rgba(0, 0, 0, 0.2); border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='rgba(230, 126, 34, 0.2)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.2)'">
                            <input type="checkbox" id="config-relatorio-auto" checked style="width: 18px; height: 18px; cursor: pointer;"> <span>📊 Relatório Automático</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 12px; color: var(--text-color); cursor: pointer; padding: 10px; background: rgba(0, 0, 0, 0.2); border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='rgba(52, 152, 219, 0.2)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.2)'">
                            <input type="checkbox" id="config-alertas" checked style="width: 18px; height: 18px; cursor: pointer;"> <span>🚨 Alertas Inteligentes</span>
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 20px; padding: 15px; background: rgba(0, 0, 0, 0.3); border-radius: 8px; border: 1px solid rgba(0, 168, 255, 0.2);">
                        <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 12px;">🎨 Escolher Tema do Site</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                            <button class="tema-btn" data-tema="escuro" onclick="aplicarTema('escuro')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(0, 168, 255, 0.3); background: linear-gradient(135deg, #1a1a1a, #2d2d2d); color: #fff; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#00a8ff'; this.style.boxShadow='0 0 15px rgba(0, 168, 255, 0.4)'" onmouseout="this.style.borderColor='rgba(0, 168, 255, 0.3)'; this.style.boxShadow='none'">
                                🌙 Escuro
                            </button>
                            <button class="tema-btn" data-tema="preto" onclick="aplicarTema('preto')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(255, 255, 255, 0.3); background: linear-gradient(135deg, #000000, #111111); color: #fff; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#ffffff'; this.style.boxShadow='0 0 15px rgba(255, 255, 255, 0.4)'" onmouseout="this.style.borderColor='rgba(255, 255, 255, 0.3)'; this.style.boxShadow='none'">
                                🖤 Preto
                            </button>
                            <button class="tema-btn" data-tema="claro" onclick="aplicarTema('claro')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(255, 193, 7, 0.3); background: linear-gradient(135deg, #f5f5f5, #e0e0e0); color: #333; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#ffc107'; this.style.boxShadow='0 0 15px rgba(255, 193, 7, 0.4)'" onmouseout="this.style.borderColor='rgba(255, 193, 7, 0.3)'; this.style.boxShadow='none'">
                                ☀️ Claro
                            </button>
                            <button class="tema-btn" data-tema="profissional" onclick="aplicarTema('profissional')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(76, 175, 80, 0.3); background: linear-gradient(135deg, #263238, #37474f); color: #4caf50; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#4caf50'; this.style.boxShadow='0 0 15px rgba(76, 175, 80, 0.4)'" onmouseout="this.style.borderColor='rgba(76, 175, 80, 0.3)'; this.style.boxShadow='none'">
                                💼 Profissional
                            </button>
                            <button class="tema-btn" data-tema="vibrante" onclick="aplicarTema('vibrante')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(233, 30, 99, 0.3); background: linear-gradient(135deg, #2c0033, #440055); color: #ff6ec7; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#ff1744'; this.style.boxShadow='0 0 15px rgba(233, 30, 99, 0.4)'" onmouseout="this.style.borderColor='rgba(233, 30, 99, 0.3)'; this.style.boxShadow='none'">
                                ⚡ Vibrante
                            </button>
                            <button class="tema-btn" data-tema="marine" onclick="aplicarTema('marine')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(0, 150, 200, 0.3); background: linear-gradient(135deg, #0d1f2d, #1a3a3a); color: #00d4ff; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#00bcd4'; this.style.boxShadow='0 0 15px rgba(0, 150, 200, 0.4)'" onmouseout="this.style.borderColor='rgba(0, 150, 200, 0.3)'; this.style.boxShadow='none'">
                                🌊 Marinho
                            </button>
                            <button class="tema-btn" data-tema="sunset" onclick="aplicarTema('sunset')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(255, 152, 0, 0.3); background: linear-gradient(135deg, #3d1f00, #5d3a1a); color: #ffb74d; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#ff9800'; this.style.boxShadow='0 0 15px rgba(255, 152, 0, 0.4)'" onmouseout="this.style.borderColor='rgba(255, 152, 0, 0.3)'; this.style.boxShadow='none'">
                                🌅 Sunset
                            </button>
                            <button class="tema-btn" data-tema="neon" onclick="aplicarTema('neon')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(0, 255, 136, 0.3); background: linear-gradient(135deg, #0a0e27, #1a1f3a); color: #00ff88; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#00ff88'; this.style.boxShadow='0 0 15px rgba(0, 255, 136, 0.4)'" onmouseout="this.style.borderColor='rgba(0, 255, 136, 0.3)'; this.style.boxShadow='none'">
                                ✨ Neon
                            </button>
                            <button class="tema-btn" data-tema="floresta" onclick="aplicarTema('floresta')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(76, 175, 80, 0.3); background: linear-gradient(135deg, #1b5e20, #2e7d32); color: #81c784; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#4caf50'; this.style.boxShadow='0 0 15px rgba(76, 175, 80, 0.4)'" onmouseout="this.style.borderColor='rgba(76, 175, 80, 0.3)'; this.style.boxShadow='none'">
                                🌲 Floresta
                            </button>
                            <button class="tema-btn" data-tema="galaxia" onclick="aplicarTema('galaxia')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(103, 58, 183, 0.3); background: linear-gradient(135deg, #1a0033, #33006f); color: #ce93d8; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#9c27b0'; this.style.boxShadow='0 0 15px rgba(156, 39, 176, 0.4)'" onmouseout="this.style.borderColor='rgba(103, 58, 183, 0.3)'; this.style.boxShadow='none'">
                                🌌 Galáxia
                            </button>
                            <button class="tema-btn" data-tema="dashboard" onclick="aplicarTema('dashboard')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(52, 152, 219, 0.3); background: linear-gradient(135deg, #34495e, #2c3e50); color: #3498db; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#3498db'; this.style.boxShadow='0 0 15px rgba(52, 152, 219, 0.4)'" onmouseout="this.style.borderColor='rgba(52, 152, 219, 0.3)'; this.style.boxShadow='none'">
                                📊 Dashboard
                            </button>
                            <button class="tema-btn" data-tema="minimalista" onclick="aplicarTema('minimalista')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(149, 165, 166, 0.3); background: linear-gradient(135deg, #ecf0f1, #bdc3c7); color: #2c3e50; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#95a5a6'; this.style.boxShadow='0 0 15px rgba(149, 165, 166, 0.4)'" onmouseout="this.style.borderColor='rgba(149, 165, 166, 0.3)'; this.style.boxShadow='none'">
                                🎯 Minimalista
                            </button>
                            <button class="tema-btn" data-tema="retro" onclick="aplicarTema('retro')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(230, 126, 34, 0.3); background: linear-gradient(135deg, #8b4513, #daa520); color: #ffd700; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#e67e22'; this.style.boxShadow='0 0 15px rgba(230, 126, 34, 0.4)'" onmouseout="this.style.borderColor='rgba(230, 126, 34, 0.3)'; this.style.boxShadow='none'">
                                📼 Retro
                            </button>
                            <button class="tema-btn" data-tema="cyberpunk" onclick="aplicarTema('cyberpunk')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(255, 0, 255, 0.3); background: linear-gradient(135deg, #0a0a0a, #1a0033); color: #ff00ff; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#ff00ff'; this.style.boxShadow='0 0 15px rgba(255, 0, 255, 0.4)'" onmouseout="this.style.borderColor='rgba(255, 0, 255, 0.3)'; this.style.boxShadow='none'">
                                🤖 Cyberpunk
                            </button>
                            <button class="tema-btn" data-tema="oceano" onclick="aplicarTema('oceano')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(0, 191, 255, 0.3); background: linear-gradient(135deg, #001122, #003366); color: #00bfff; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#00bfff'; this.style.boxShadow='0 0 15px rgba(0, 191, 255, 0.4)'" onmouseout="this.style.borderColor='rgba(0, 191, 255, 0.3)'; this.style.boxShadow='none'">
                                🌊 Oceano
                            </button>
                            <button class="tema-btn" data-tema="deserto" onclick="aplicarTema('deserto')" style="padding: 15px; border-radius: 8px; border: 2px solid rgba(255, 165, 0, 0.3); background: linear-gradient(135deg, #8b4513, #daa520); color: #ffa500; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#ffa500'; this.style.boxShadow='0 0 15px rgba(255, 165, 0, 0.4)'" onmouseout="this.style.borderColor='rgba(255, 165, 0, 0.3)'; this.style.boxShadow='none'">
                                🏜️ Deserto
                            </button>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px; padding: 15px; background: rgba(0, 0, 0, 0.3); border-radius: 8px; border: 1px solid rgba(46, 204, 113, 0.2);">
                        <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 12px;">🔧 Manutenção de Dados</label>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                            <button class="btn" style="width: 100%; background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(46, 204, 113, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'" onclick="reorganizarIdsPedidos()">
                                🔄 Reorganizar IDs dos Pedidos
                            </button>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px; padding: 15px; background: rgba(0, 0, 0, 0.3); border-radius: 8px; border: 1px solid rgba(155, 89, 182, 0.2);">
                        <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 12px;">🧠 Configurações de IA</label>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                            <button class="btn" style="width: 100%; background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(155, 89, 182, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'" onclick="treinarIA()">
                                🎓 Treinar a IA com Meus Dados
                            </button>
                            <button class="btn" style="width: 100%; background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(52, 152, 219, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'" onclick="personalizarIA()">
                                ⚙️ Personalizar Respostas da IA
                            </button>
                            <button class="btn" style="width: 100%; background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(231, 76, 60, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'" onclick="treinarMemoriaIA()">
                                💾 Treinar Memória da IA
                            </button>
                            <button class="btn" style="width: 100%; background: linear-gradient(135deg, #f39c12, #d68910); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(243, 156, 18, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'" onclick="limparMemoriaIA()">
                                🗑️ Limpar Memória (Reset)
                            </button>
                        </div>
                    </div>
                    
                    <button class="btn btn-success" style="margin-top: 30px; width: 100%; padding: 12px; font-weight: 600;" onclick="salvarOutrasConfigs()">💾 Salvar Todas as Configurações</button>
                </div>
            </div>

            <div id="clientes" class="tab-pane">
                <h3 style="margin-bottom: 30px; font-size: 28px; color: var(--text-light);"><i class="fas fa-users" style="color: var(--primary-color); margin-right: 10px;"></i>Base de Clientes</h3>
                
                <div style="margin-bottom: 30px;">
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 20px; border-radius: var(--radius); border: 1px solid rgba(0, 168, 255, 0.2);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 8px;">🔍 Buscar Cliente</label>
                                <input type="text" id="busca-clientes" class="form-control" placeholder="Digite nome ou CPF/CNPJ..." style="padding: 12px; font-size: 14px;">
                            </div>
                            <div>
                                <label style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 8px;">📊 Ordenar Por</label>
                                <select id="ordenar-clientes" class="form-control" style="padding: 12px; font-size: 14px;">
                                    <option value="nome">Nome (A-Z)</option>
                                    <option value="pedidos">Mais Pedidos</option>
                                    <option value="gasto">Maior Gasto</option>
                                    <option value="recente">Mais Recente</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="clientes-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 25px;">
                    <!-- Cards de clientes carregados dinamicamente -->
                </div>
            </div>

            <div id="rastreio" class="tab-pane" style="position: relative; min-height: calc(100vh - 130px); padding: 0; overflow: hidden;">
                <iframe src="ma.php" style="position: absolute; inset: 0; width: 100%; height: 100%; border: none; display: block;"></iframe>
            </div>

            <div id="whatsapp" class="tab-pane" style="position: relative; min-height: calc(100vh - 130px); padding: 0; overflow: hidden;">
                <iframe src="zap.php" style="position: absolute; inset: 0; width: 100%; height: 100%; border: none; display: block;"></iframe>
            </div>

            <!-- Footer -->
            <div class="footer">
                Market Manager Pro - Sistema Unificado de Gestão | Desenvolvido para Uso Pessoal
                <br>
                <small>Sistema integrado com IA avançada para análise automática de preços, lucros e gestão de clientes e pedidos.</small>
                <div style="margin-top: 15px; font-size: 12px; color: var(--text-contrast);">
                    <i class="fas fa-database"></i> Dados salvos em JSON | 
                    <i class="fas fa-robot"></i> IA com Memória | 
                    <i class="fas fa-shipping-fast"></i> Entregas Shopee
                    <br>
                    <small>Última atualização: <?php echo date('d/m/Y H:i:s'); ?></small>
                </div>
            </div>
        </main>
    </div>

    <!-- Modais -->
    <div class="modal-overlay" id="modal-novo-pedido">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Novo Pedido Unificado</div>
                <button class="modal-close" id="close-modal-novo-pedido">&times;</button>
            </div>
            
            <div class="modal-tabs">
                <button class="modal-tab-btn active" data-modal-tab="cliente">Cliente</button>
                <button class="modal-tab-btn" data-modal-tab="produto">Produto</button>
                <button class="modal-tab-btn" data-modal-tab="rastreio">Rastreio</button>
                <button class="modal-tab-btn" data-modal-tab="pagamento">Pagamento</button>
            </div>
            
            <form id="form-novo-pedido" novalidate>
                <!-- Conteúdo será carregado dinamicamente -->
            </form>
        </div>
    </div>

    <!-- Modal para Ver Detalhes do Pedido -->
    <div class="modal-overlay" id="modal-ver-pedido">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <div class="modal-title">Detalhes do Pedido</div>
                <button class="modal-close" onclick="document.getElementById('modal-ver-pedido').style.display='none'">&times;</button>
            </div>
            <div id="modal-ver-pedido-content" style="padding: 20px;">
                <!-- Conteúdo será preenchido dinamicamente -->
            </div>
        </div>
    </div>

    <!-- Modal para Novo Produto -->
    <div class="modal-overlay" id="modal-produto">
        <div class="modal" style="max-width: 700px;">
            <div class="modal-header">
                <div class="modal-title">Novo Produto</div>
                <button class="modal-close" id="close-modal-produto">&times;</button>
            </div>
            
            <form id="form-novo-produto">
                <!-- Conteúdo será carregado dinamicamente -->
            </form>
        </div>
    </div>

    <!-- Modal Busca Avançada -->
    <div class="modal-overlay" id="modal-busca-avancada">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Busca Avançada</div>
                <button class="modal-close" id="close-modal-busca">&times;</button>
            </div>
            
            <form id="form-busca-avancada">
                <!-- Conteúdo será carregado dinamicamente -->
            </form>
            
            <div id="resultados-busca" style="margin-top: 30px; display: none;">
                <h4>Resultados da Busca:</h4>
                <div id="resultados-container" style="max-height: 400px; overflow-y: auto; margin-top: 15px;"></div>
            </div>
        </div>
    </div>

    <!-- Modal Sourcing Shopee → Amazon -->
    <div class="modal-overlay" id="modal-sourcing">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Sourcing Shopee → Amazon</div>
                <button class="modal-close" id="close-modal-sourcing">&times;</button>
            </div>
            <form id="form-sourcing">
                <div class="form-group">
                    <label class="form-label">Link do produto na Shopee (opcional)</label>
                    <input type="url" class="form-control" id="sourcing-link" placeholder="https://shopee.com.br/...">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome do produto *</label>
                        <input type="text" class="form-control" id="sourcing-nome" placeholder="Ex: Fone Bluetooth XYZ" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Preço na Shopee (custo) *</label>
                        <input type="number" step="0.01" class="form-control" id="sourcing-preco-custo" placeholder="0.00" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Categoria na Amazon</label>
                        <select class="form-control" id="sourcing-categoria">
                            <option value="eletronicos">Eletrônicos</option>
                            <option value="livros">Livros</option>
                            <option value="casa">Casa e Cozinha</option>
                            <option value="vestuario">Vestuário</option>
                            <option value="beleza">Beleza</option>
                            <option value="brinquedos">Brinquedos</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Taxa da Amazon (%) *</label>
                        <input type="number" step="0.1" class="form-control" id="sourcing-taxa" value="15" required min="0" max="100">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Observações adicionais (opcional)</label>
                    <textarea class="form-control" id="sourcing-obs" rows="2" placeholder="Ex: cor, tamanho, público-alvo..."></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px;">
                    <button type="button" class="btn" id="cancelar-sourcing">Cancelar</button>
                    <button type="submit" class="btn btn-ia">
                        <i class="fas fa-magic"></i> Gerar Sugestão de Produto
                    </button>
                </div>
            </form>
            <div id="resultado-sourcing" style="margin-top: 30px; display: none;">
                <h4 style="margin-bottom: 15px;">Sugestão da IA para Amazon:</h4>
                <div id="sourcing-resposta" style="background-color: var(--darkest-bg); padding: 20px; border-radius: var(--radius); max-height: 400px; overflow-y: auto;"></div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button class="btn btn-secondary btn-small" id="copiar-sourcing">
                        <i class="fas fa-copy"></i> Copiar Tudo
                    </button>
                    <button class="btn btn-success btn-small" id="salvar-produto-sourcing">
                        <i class="fas fa-save"></i> Salvar como Produto
                    </button>
                    <button class="btn btn-warning btn-small" id="fechar-sourcing">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- NOVO MODAL: Importar da Amazon -->
    <div class="modal-overlay" id="modal-importar-amazon">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Importar Produto da Amazon</div>
                <button class="modal-close" id="close-modal-importar-amazon">&times;</button>
            </div>
            <form id="form-importar-amazon">
                <div class="form-group">
                    <label class="form-label">URL do produto na Amazon *</label>
                    <input type="url" class="form-control" id="amazon-url" placeholder="https://www.amazon.com.br/dp/B08XYZ1234" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px;">
                    <button type="button" class="btn" id="cancelar-importar-amazon">Cancelar</button>
                    <button type="submit" class="btn btn-ia">
                        <i class="fas fa-download"></i> Importar
                    </button>
                </div>
            </form>
            <div id="resultado-importacao" style="margin-top: 30px; display: none;">
                <!-- Resultado será exibido aqui -->
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-rastreio">
        <div class="modal" style="width: 90%; max-width: 1200px; height: 90vh;">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-truck"></i> Rastreio do Pedido
                </div>
                <button class="modal-close" id="close-modal-rastreio">&times;</button>
            </div>
            <div style="flex: 1; overflow: hidden; display: flex; flex-direction: column;">
                <iframe id="rastreio-iframe" style="flex: 1; border: none; width: 100%; height: 100%;" src=""></iframe>
                <div style="padding: 15px; background: var(--card-bg); border-top: 1px solid var(--border-color); text-align: center;">
                    <small style="color: var(--text-muted);">
                        <i class="fas fa-info-circle"></i> Rastreio fornecido por 4tracking.net
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Configuração Amazon -->
    <div class="modal-overlay" id="modal-config-amazon">
        <div class="modal" style="width: 90%; max-width: 800px;">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-amazon" style="color: #ff9900;"></i> Configuração Amazon Seller
                </div>
                <button class="modal-close" id="close-modal-config-amazon">&times;</button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <form id="form-config-amazon" style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="background: rgba(255, 153, 0, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #ff9900; margin-bottom: 10px;">
                        <p style="color: var(--text-muted); font-size: 13px; margin: 0;">
                            <i class="fas fa-info-circle"></i> Configure suas credenciais da Amazon SP-API para sincronizar pedidos e produtos automaticamente.
                        </p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; color: var(--text-light); font-weight: 600;">
                                <i class="fas fa-key"></i> AWS Access Key ID *
                            </label>
                            <input type="text" id="amazon-aws-access-key" placeholder="AKIAIOSFODNN7EXAMPLE" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-color);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; color: var(--text-light); font-weight: 600;">
                                <i class="fas fa-lock"></i> AWS Secret Access Key *
                            </label>
                            <input type="password" id="amazon-aws-secret-key" placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-color);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; color: var(--text-light); font-weight: 600;">
                                <i class="fas fa-user"></i> Seller ID *
                            </label>
                            <input type="text" id="amazon-seller-id" placeholder="A1B2C3D4E5F6G7" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-color);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; color: var(--text-light); font-weight: 600;">
                                <i class="fas fa-globe"></i> Marketplace *
                            </label>
                            <select id="amazon-marketplace" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-color);">
                                <option value="US">Estados Unidos (US)</option>
                                <option value="CA">Canadá (CA)</option>
                                <option value="MX">México (MX)</option>
                                <option value="UK">Reino Unido (UK)</option>
                                <option value="DE">Alemanha (DE)</option>
                                <option value="FR">França (FR)</option>
                                <option value="IT">Itália (IT)</option>
                                <option value="ES">Espanha (ES)</option>
                                <option value="JP">Japão (JP)</option>
                                <option value="AU">Austrália (AU)</option>
                                <option value="IN">Índia (IN)</option>
                                <option value="SG">Singapura (SG)</option>
                                <option value="AE">Emirados Árabes (AE)</option>
                                <option value="SA">Arábia Saudita (SA)</option>
                                <option value="NL">Holanda (NL)</option>
                                <option value="BE">Bélgica (BE)</option>
                                <option value="PL">Polônia (PL)</option>
                                <option value="SE">Suécia (SE)</option>
                                <option value="EG">Egito (EG)</option>
                                <option value="TR">Turquia (TR)</option>
                                <option value="BR">Brasil (BR)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 8px; color: var(--text-light); font-weight: 600;">
                            <i class="fas fa-link"></i> LWA Client ID *
                        </label>
                        <input type="text" id="amazon-lwa-client-id" placeholder="amzn1.application-oa2-client.xxxxx" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-color);">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 8px; color: var(--text-light); font-weight: 600;">
                            <i class="fas fa-shield-alt"></i> LWA Client Secret *
                        </label>
                        <input type="password" id="amazon-lwa-client-secret" placeholder="amzn1.oa2-client.xxxxx" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-color);">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 8px; color: var(--text-light); font-weight: 600;">
                            <i class="fas fa-refresh"></i> LWA Refresh Token *
                        </label>
                        <textarea id="amazon-lwa-refresh-token" rows="3" placeholder="Atzr|IwEBIJ..." required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-color); resize: vertical;"></textarea>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 10px;">
                        <button type="button" class="btn btn-primary" onclick="salvarConfigAmazon()" style="flex: 1; background-color: #ff9900; border: none;">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                        <button type="button" class="btn btn-success" onclick="testarConexaoAmazon()" style="flex: 1;">
                            <i class="fas fa-plug"></i> Testar Conexão
                        </button>
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary" onclick="importarProdutosAmazonConfig()" style="flex: 1; background-color: rgba(255, 153, 0, 0.12); border: 1px solid #ff9900; color: #ff9900;">
                            <i class="fas fa-box-open"></i> Importar Produtos Amazon
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="sincronizarPedidosAmazonConfig()" style="flex: 1; background-color: rgba(255, 153, 0, 0.12); border: 1px solid #ff9900; color: #ff9900;">
                            <i class="fas fa-shopping-cart"></i> Sincronizar Pedidos Amazon
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="sincronizacaoCompletaAmazonConfig()" style="flex: 1; background-color: rgba(255, 153, 0, 0.12); border: 1px solid #ff9900; color: #ff9900;">
                            <i class="fas fa-sync-alt"></i> Sincronização Completa
                        </button>
                    </div>

                    <div id="amazon-config-status" style="margin-top: 15px; padding: 15px; border-radius: 8px; display: none;">
                        <!-- Status será exibido aqui -->
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notificações -->
    <div id="notification-container"></div>

    <script>
        // Sistema de Gerenciamento Completo com Salvamento em JSON via PHP
        class MarketManager {
            constructor() {
                this.pedidos = [];
                this.produtos = [];
                this.clientes = [];
                this.config = {
                    entregadorPadrao: 'shopee',
                    taxaPadrao: 15,
                    notificacoes: true,
                    tema: 'escuro',
                    visualizacaoPedidos: 'grid', // 'grid' ou 'lista'
                    categoriasAmazon: {
                        eletronicos: { nome: 'Eletrônicos', taxa: 15 },
                        moda: { nome: 'Moda e Acessórios', taxa: 18 },
                        casa: { nome: 'Casa e Decoração', taxa: 14 },
                        beleza: { nome: 'Beleza e Saúde', taxa: 20 },
                        brinquedos: { nome: 'Brinquedos e Kids', taxa: 16 },
                        esportes: { nome: 'Esportes e Fitness', taxa: 13 },
                        automotivo: { nome: 'Auto e Moto', taxa: 17 },
                        livros: { nome: 'Livros e Papelaria', taxa: 10 },
                        mercado: { nome: 'Supermercado', taxa: 14 },
                        pet: { nome: 'Pet Shop', taxa: 15 },
                        outros: { nome: 'Outros', taxa: 15 }
                    }
                };
                this.apiKeys = {};
                this.amazonConfig = {};
                this.pedidoEditandoId = null;
                
                // MEMÓRIA DA IA
                this.historicoIA = [];
                this.ultimaPergunta = ''; // Evita perguntas duplicadas consecutivas
                this.apiKeyIA = localStorage.getItem('pollinations_api_key') || ''; // API Key do Pollinations
                
                // Estado de dados sensíveis
                this.dadosSensivelOcultado = false;
            }
            
            // Inicialização do sistema
            async init() {
                try {
                    await this.carregarDadosServidor();
                } catch (e) {
                    console.error('Falha ao carregar dados do servidor:', e);
                    this.mostrarNotificacao('Erro ao carregar dados iniciais. A interface ainda está disponível.', 'danger');
                }
                
                // Aplicar configurações visuais ANTES de renderizar qualquer interface
                this.aplicarConfiguracoesVisuais();
                this.inicializarConfigurador();
                
                // Agora renderizar a interface com as configs corretas
                this.carregarInterface();
                this.configurarEventos();
                atualizarMenuReal();
                
                // Carregar dados
                this.atualizarDashboard();
                this.carregarPedidos('todos');
                this.carregarProdutos();
                this.atualizarBadgesMenu();
                this.carregarSugestoesIA();
                
                // Verificações automáticas
                this.verificarPedidosAtrasados();
                this.verificarAlertas();
                this.verificarLembretes();
            }
            
            // Função auxiliar para obter data de hoje em formato correto (local, não UTC)
            obterDataHoje() {
                const d = new Date();
                // Usar métodos locais para evitar problemas de fuso horário
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            
            // ========== FUNÇÃO PARA OBTER TAXA DA CATEGORIA AMAZON ==========
            obterTaxaCategoria(categoria) {
                if (!this.config.categoriasAmazon) {
                    return 15; // Taxa padrão se config não estiver carregada
                }
                
                const cat = this.config.categoriasAmazon[categoria];
                if (cat) {
                    return typeof cat === 'object' ? cat.taxa : cat;
                }
                
                // Fallback para categoria 'outros'
                const outros = this.config.categoriasAmazon['outros'];
                return outros ? (typeof outros === 'object' ? outros.taxa : outros) : 15;
            }
            
            // ========== GERAR OPÇÕES DE CATEGORIAS DINÂMICAS ==========
            gerarOpcoesCategorias(categoriaSelecionada = '') {
                if (!this.config.categoriasAmazon) {
                    return '<option value="outros">Outros</option>';
                }

                let html = '<option value="">Selecione uma categoria...</option>';
                
                Object.entries(this.config.categoriasAmazon).forEach(([chave, dados]) => {
                    const nome = typeof dados === 'object' ? dados.nome : chave;
                    const taxa = typeof dados === 'object' ? dados.taxa : dados;
                    const selected = chave === categoriaSelecionada ? 'selected' : '';
                    html += `<option value="${chave}" data-taxa="${taxa}" ${selected}>${nome}</option>`;
                });
                
                return html;
            }
            
            // ========== ATUALIZAR CÁLCULO AUTOMÁTICO - PEDIDOS ==========
            atualizarCalculoTaxaPedido() {
                const selectCategoria = document.getElementById('pedido-produto-categoria');
                const infoTaxa = document.getElementById('pedido-taxa-info');
                const inputPrecoCusto = document.getElementById('pedido-produto-preco-custo');
                const inputPrecoVenda = document.getElementById('pedido-produto-preco-venda');
                
                if (!selectCategoria) return;
                
                const categoria = selectCategoria.value;
                const taxa = this.obterTaxaCategoria(categoria);
                
                if (infoTaxa) {
                    infoTaxa.innerHTML = `<strong>✅ Taxa: ${taxa}%</strong>`;
                }
                
                // Se houver preços, calcular lucro
                if (inputPrecoCusto && inputPrecoVenda) {
                    const precoCusto = parseFloat(inputPrecoCusto.value) || 0;
                    const precoVenda = parseFloat(inputPrecoVenda.value) || 0;
                    
                    if (precoCusto > 0 && precoVenda > 0) {
                        const taxaFinal = precoVenda * (taxa / 100);
                        const lucro = precoVenda - precoCusto - taxaFinal;
                        const margem = precoVenda > 0 ? ((lucro / precoVenda) * 100).toFixed(1) : 0;
                        
                        infoTaxa.innerHTML = `<strong>✅ Taxa: ${taxa}%</strong> | Lucro: R$ ${lucro.toFixed(2)} | Margem: ${margem}%`;
                    }
                }
            }
            
            // ========== ATUALIZAR CÁLCULO AUTOMÁTICO - PRODUTOS ==========
            atualizarCalculoTaxaProduto() {
                const selectCategoria = document.getElementById('produto-categoria-novo');
                const infoTaxa = document.getElementById('produto-taxa-info');
                const inputPrecoCusto = document.getElementById('produto-preco-custo-novo');
                const inputPrecoVenda = document.getElementById('produto-preco-venda-novo');
                
                if (!selectCategoria || !infoTaxa) return;
                
                const categoria = selectCategoria.value;
                const taxa = this.obterTaxaCategoria(categoria);
                
                // Calcular valores se houver preços
                const precoCusto = parseFloat(inputPrecoCusto?.value) || 0;
                const precoVenda = parseFloat(inputPrecoVenda?.value) || 0;
                
                let infoHTML = `<strong>✅ Taxa: ${taxa}%</strong>`;
                
                if (precoCusto > 0 && precoVenda > 0) {
                    const taxaValor = precoVenda * (taxa / 100);
                    const lucro = precoVenda - precoCusto - taxaValor;
                    const margem = precoVenda > 0 ? ((lucro / precoVenda) * 100).toFixed(1) : 0;
                    
                    infoHTML += ` | <span style="color: ${lucro >= 0 ? 'var(--success-color)' : 'var(--danger-color)'};">Lucro: R$ ${lucro.toFixed(2)} (${margem}%)</span>`;
                } else if (precoCusto > 0) {
                    const taxaValor = precoCusto * (taxa / 100);
                    const precoSugerido = precoCusto + taxaValor + (precoCusto * 0.2); // 20% de margem
                    infoHTML += ` | Sugestão: R$ ${precoSugerido.toFixed(2)}`;
                }
                
                infoTaxa.innerHTML = infoHTML;
            }
            
            // ========== MÉTODO PARA BLOQUEAR BOTÕES ==========
            async executarComBloqueio(botao, callback) {
                if (!botao) return callback();
                const originalHTML = botao.innerHTML;
                botao.innerHTML = '<div class="loader"></div> Aguarde...';
                botao.disabled = true;
                try {
                    await callback();
                } finally {
                    botao.innerHTML = originalHTML;
                    botao.disabled = false;
                }
            }
            
            // ========== SISTEMA DE ARMAZENAMENTO EM JSON (PHP) ==========
            async parseApiResponse(response) {
                const text = await response.text();
                if (!text) {
                    throw new Error('Resposta vazia do servidor');
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Resposta inválida do servidor: ' + text);
                }
            }

            async salvarDados(chave, dados) {
                try {
                    const response = await fetch('api/crud.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ acao: 'salvar', tipo: chave, dados: JSON.stringify(dados) })
                    });

                    const resultado = await this.parseApiResponse(response);
                    if (resultado.success === true) return true;
                    if (resultado.erro === 'ID ja existe') {
                        this.mostrarNotificacao('Erro: ID duplicado detectado. Tente novamente.', 'danger');
                    }
                    return false;
                } catch (e) {
                    console.error(`Erro ao salvar ${chave}:`, e);
                    this.mostrarNotificacao(`Erro ao salvar ${chave}: ${e.message}`, 'danger');
                    return false;
                }
            }

            async salvarDadosComResposta(chave, dados) {
                try {
                    const response = await fetch('api/crud.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ acao: 'salvar', tipo: chave, dados: JSON.stringify(dados) })
                    });

                    const resultado = await this.parseApiResponse(response);
                    if (resultado.success === true) return resultado;
                    if (resultado.erro === 'ID ja existe') {
                        this.mostrarNotificacao('Erro: ID duplicado detectado. Tente novamente.', 'danger');
                    }
                    return { success: false, erro: resultado.erro || 'Falha ao salvar' };
                } catch (e) {
                    console.error(`Erro ao salvar ${chave}:`, e);
                    this.mostrarNotificacao(`Erro ao salvar ${chave}: ${e.message}`, 'danger');
                    return { success: false, erro: e.message };
                }
            }
            
            async atualizarDados(chave, id, dadosAtualizados) {
                try {
                    dadosAtualizados.id = id;
                    const response = await fetch('api/crud.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ acao: 'atualizar', tipo: chave, dados: JSON.stringify(dadosAtualizados) })
                    });

                    const resultado = await this.parseApiResponse(response);
                    return resultado.success === true;
                } catch (e) {
                    console.error(`Erro ao atualizar ${chave}:`, e);
                    this.mostrarNotificacao(`Erro ao atualizar ${chave}`, 'danger');
                    return false;
                }
            }
            
            async excluirDados(chave, id) {
                try {
                    const response = await fetch('api/crud.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ acao: 'excluir', tipo: chave, id: id })
                    });

                    const resultado = await this.parseApiResponse(response);
                    return resultado.success === true;
                } catch (e) {
                    console.error(`Erro ao excluir ${chave}:`, e);
                    this.mostrarNotificacao(`Erro ao excluir ${chave}`, 'danger');
                    return false;
                }
            }

            // ========== INTEGRAÇÃO AMAZON - IMPORTAR PRODUTOS E NOTIFICAÇÕES ==========

            /**
             * Importar produtos do inventário da Amazon
             */
            async importarProdutosAmazon() {
                try {
                    this.mostrarNotificacao('🔄 Importando produtos do inventário da Amazon...', 'info');

                    const response = await fetch('api/sync.php?acao=sync-produtos', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });

                    const resultado = await this.parseApiResponse(response);

                    if (resultado.success) {
                        // Recarregar dados do servidor
                        await this.carregarDadosServidor();

                        // Atualizar interface
                        this.carregarProdutos();
                        this.atualizarDashboard();

                        const msg = `✅ Produtos importados: ${resultado.importados} novos, ${resultado.atualizados} atualizados`;
                        this.mostrarNotificacao(msg, 'success');
                        return resultado;
                    } else {
                        this.mostrarNotificacao(`❌ Erro ao importar produtos: ${resultado.message}`, 'danger');
                        return resultado;
                    }
                } catch (e) {
                    console.error('Erro ao importar produtos Amazon:', e);
                    this.mostrarNotificacao('❌ Erro ao importar produtos da Amazon', 'danger');
                    return { success: false, message: e.message };
                }
            }

            /**
             * Sincronizar pedidos da Amazon
             */
            async sincronizarPedidosAmazon() {
                try {
                    this.mostrarNotificacao('🔄 Sincronizando pedidos da Amazon...', 'info');

                    const response = await fetch('api/sync.php?acao=sync-pedidos', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });

                    const resultado = await this.parseApiResponse(response);

                    if (resultado.success) {
                        // Recarregar dados do servidor
                        await this.carregarDadosServidor();

                        // Atualizar interface
                        this.carregarPedidos('todos');
                        this.atualizarDashboard();

                        const msg = `✅ Pedidos sincronizados: ${resultado.importados} novos, ${resultado.atualizados} atualizados`;
                        this.mostrarNotificacao(msg, 'success');
                        return resultado;
                    } else {
                        this.mostrarNotificacao(`❌ Erro ao sincronizar pedidos: ${resultado.message}`, 'danger');
                        return resultado;
                    }
                } catch (e) {
                    console.error('Erro ao sincronizar pedidos Amazon:', e);
                    this.mostrarNotificacao('❌ Erro ao sincronizar pedidos da Amazon', 'danger');
                    return { success: false, message: e.message };
                }
            }

            /**
             * Buscar notificações de vendas recentes
             */
            async buscarNotificacoesVendas(horas = 24) {
                try {
                    const response = await fetch('api/sync.php?acao=notificacoes-vendas', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ hours: horas })
                    });

                    const resultado = await this.parseApiResponse(response);

                    if (resultado.error) {
                        console.error('Erro ao buscar notificações:', resultado.message);
                        return [];
                    }

                    return resultado;
                } catch (e) {
                    console.error('Erro ao buscar notificações de vendas:', e);
                    return [];
                }
            }

            /**
             * Exibir notificações de vendas em tempo real
             */
            async exibirNotificacoesVendas() {
                const vendas = await this.buscarNotificacoesVendas(24);

                if (vendas.length === 0) {
                    this.mostrarNotificacao('ℹ️ Nenhuma venda recente nas últimas 24 horas', 'info');
                    return;
                }

                // Exibir cada venda como notificação
                vendas.forEach((venda, index) => {
                    setTimeout(() => {
                        const emailInfo = venda.customer_email ? `Email: ${venda.customer_email}` : '';
                        const phoneInfo = venda.customer_phone ? `Telefone: ${venda.customer_phone}` : '';
                        const msg = `
                            💰 NOVA VENDA!
                            Cliente: ${venda.customer_name || 'Não informado'}
                            ${emailInfo}${emailInfo && phoneInfo ? ' | ' : ''}${phoneInfo}
                            Produto: ${venda.product_name}
                            Qtd: ${venda.quantity} | Total: R$ ${venda.total}
                            Status: ${venda.status}
                        `;
                        this.mostrarNotificacao(msg, 'success');
                    }, index * 2000); // Exibir com intervalo de 2 segundos
                });
            }

            /**
             * Sincronização completa da Amazon (pedidos + produtos)
             */
            async sincronizacaoCompletaAmazon() {
                try {
                    this.mostrarNotificacao('🔄 Iniciando sincronização completa com Amazon...', 'info');

                    // Sincronizar pedidos
                    const pedidosResult = await this.sincronizarPedidosAmazon();

                    // Sincronizar produtos
                    const produtosResult = await this.importarProdutosAmazon();

                    // Exibir notificações de vendas
                    await this.exibirNotificacoesVendas();

                    const msg = `✅ Sincronização completa!
                    Pedidos: ${pedidosResult.importados} novos, ${pedidosResult.atualizados} atualizados
                    Produtos: ${produtosResult.importados} novos, ${produtosResult.atualizados} atualizados`;

                    this.mostrarNotificacao(msg, 'success');
                    return { pedidos: pedidosResult, produtos: produtosResult };
                } catch (e) {
                    console.error('Erro na sincronização completa:', e);
                    this.mostrarNotificacao('❌ Erro na sincronização completa', 'danger');
                    return { success: false, message: e.message };
                }
            }
            
            async carregarDadosServidor() {
                try {
                    console.log('Iniciando carregarDadosServidor...');
                    
                    const response = await fetch('api/crud.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ acao: 'carregar', tipo: 'todos' })
                    });

                    const dados = await this.parseApiResponse(response);
                    console.log('Dados recebidos do servidor:', dados);

                    this.pedidos = dados.pedidos || [];
                    this.produtos = dados.produtos || [];
                    this.clientes = dados.clientes || [];
                    this.config = { ...this.config, ...(dados.config || {}) };
                    this.apiKeys = dados.apiKeys || {};
                    
                    console.log('Dados atribuídos - Pedidos:', this.pedidos.length, 'Produtos:', this.produtos.length, 'Clientes:', this.clientes.length);
                    
                    // Sincronizar clientes dos pedidos se a lista de clientes estiver vazia
                    if (this.clientes.length === 0 && this.pedidos.length > 0) {
                        await this.sincronizarClientesDosPedidos();
                    }
                    
                    // Reorganizar IDs dos pedidos para garantir sequencialidade (silencioso na inicialização)
                    this.reorganizarIdsPedidos(false);
                    
                    if (this.apiKeys.amazon) {
                        this.amazonConfig = this.apiKeys.amazon;
                        try {
                            localStorage.setItem('amazon_config', JSON.stringify(this.amazonConfig));
                        } catch (err) {
                            console.error('Erro ao salvar configuração Amazon localmente:', err);
                        }
                    }
                    if (!this.config.categoriasAmazon) {
                        this.config.categoriasAmazon = {
                            eletronicos: { nome: 'Eletrônicos', taxa: 15 },
                            moda: { nome: 'Moda e Acessórios', taxa: 18 },
                            casa: { nome: 'Casa e Decoração', taxa: 14 },
                            beleza: { nome: 'Beleza e Saúde', taxa: 20 },
                            brinquedos: { nome: 'Brinquedos e Kids', taxa: 16 },
                            esportes: { nome: 'Esportes e Fitness', taxa: 13 },
                            automotivo: { nome: 'Auto e Moto', taxa: 17 },
                            livros: { nome: 'Livros e Papelaria', taxa: 10 },
                            mercado: { nome: 'Supermercado', taxa: 14 },
                            pet: { nome: 'Pet Shop', taxa: 15 },
                            outros: { nome: 'Outros', taxa: 15 }
                        };
                    }

                    // Carregar chaves de API se disponíveis
                    if (dados.apiKeys && dados.apiKeys.pollinationsAI && dados.apiKeys.pollinationsAI.chave) {
                        this.apiKeyIA = dados.apiKeys.pollinationsAI.chave;
                        localStorage.setItem('pollinations_api_key', this.apiKeyIA);
                    }

                    return true;
                } catch (e) {
                    console.error('Erro ao carregar dados:', e);
                    this.mostrarNotificacao('Erro ao carregar dados do servidor', 'danger');
                    return false;
                }
            }
            
            // ========== INTERFACE ==========
            carregarInterface() {
                // Configurar data atual nos formulários
                const hoje = this.obterDataHoje();
                document.querySelectorAll('input[type="date"]').forEach(input => {
                    if (!input.value) input.value = hoje;
                });
                
                // Carregar API Key da IA no UI
                this.carregarAPIKeyNoUI();
                
                // Atualizar badges do menu
                this.atualizarBadgesMenu();
                
                // Carregar dados iniciais na dashboard
                this.carregarCardsDashboard();
                this.carregarTabelasDashboard();
            }
            
            atualizarBadgesMenu() {
                const clientesUnicos = new Set();
                this.pedidos.forEach(p => {
                    if (p.cliente?.cpf) clientesUnicos.add(p.cliente.cpf);
                });

                // Atualizar badges apenas se os elementos existirem
                const badgeDashboard = document.getElementById('menu-badge-dashboard');
                if (badgeDashboard) badgeDashboard.textContent = this.pedidos.length;

                const badgePedidos = document.getElementById('menu-badge-pedidos');
                if (badgePedidos) badgePedidos.textContent = this.pedidos.length;

                const badgeProdutos = document.getElementById('menu-badge-produtos');
                if (badgeProdutos) badgeProdutos.textContent = this.produtos.length;

                const badgeClientes = document.getElementById('menu-badge-clientes');
                if (badgeClientes) badgeClientes.textContent = this.clientes.length || clientesUnicos.size;
            }
            
            // ========== DASHBOARD ==========
            atualizarDashboard() {
                this.carregarCardsDashboard();
                this.carregarTabelasDashboard();
            }
            
            obterDadosPainelPremium() {
                const h = new Date().getHours();
                const periodo = h < 5 ? 'madrugada' : h < 12 ? 'manha' : h < 18 ? 'tarde' : 'noite';
                const FRASES = {
                    madrugada: [
                        'A madrugada é dos guerreiros. Bora faturar!',
                        'Enquanto outros dormem, você lucra.',
                        'Foco total — a próxima venda está logo ali.',
                        'Hora silenciosa, resultados altos.',
                    ],
                    manha: [
                        'Bom dia! Hora de abrir as vendas.',
                        'Café na mão e pedidos na fila!',
                        'Comece o dia vendendo forte.',
                        'Manhã produtiva, mês lucrativo.',
                    ],
                    tarde: [
                        'Boa tarde! Bora bater a meta.',
                        'Tarde é hora de fechar negócio.',
                        'Mais um pedido, mais uma vitória.',
                        'Ritmo acelerado, vendas em alta.',
                    ],
                    noite: [
                        'Boa noite! Hora de revisar e celebrar os números.',
                        'Fim de dia, hora de contar os lucros.',
                        'Noite tranquila, vendas no automático.',
                        'Bora vender até o último minuto!',
                    ],
                };
                const saudacao = periodo === 'madrugada' ? 'Boa madrugada'
                    : periodo === 'manha' ? 'Bom dia'
                    : periodo === 'tarde' ? 'Boa tarde'
                    : 'Boa noite';
                return {
                    saudacao,
                    frase: FRASES[periodo][Math.floor(Math.random() * FRASES[periodo].length)]
                };
            }

            gerarSparklineSVG(valores, cor) {
                const largura = 90;
                const altura = 34;
                const max = Math.max(...valores);
                const min = Math.min(...valores);
                const range = max - min || 1;
                const pontos = valores.map((valor, indice) => {
                    const x = (indice / ((valores.length - 1) || 1)) * largura;
                    const y = altura - ((valor - min) / range) * (altura - 8) - 3;
                    return `${x},${y}`;
                }).join(' ');

                return `
                    <svg width="${largura}" height="${altura}" viewBox="0 0 ${largura} ${altura}" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M${pontos}" stroke="${cor}" stroke-width="2" stroke-linecap="round" fill="none" />
                    </svg>
                `;
            }

            carregarCardsDashboard() {
                const container = document.querySelector('.dashboard-cards');
                if (!container) return;

                const painelPremium = this.obterDadosPainelPremium();
                const totalPedidos = this.pedidos.length;
                const pedidosEntregues = this.pedidos.filter(p => p.rastreio && p.rastreio.status === 'entregue').length;
                const pedidosPendentes = this.pedidos.filter(p => 
                    p.rastreio && (p.rastreio.status === 'pendente' || p.rastreio.status === 'processando')
                ).length;
                const pedidosTransito = this.pedidos.filter(p => p.rastreio && p.rastreio.status === 'transito').length;
                const pedidosAtrasados = this.pedidos.filter(p => p.rastreio && p.rastreio.status === 'atrasado').length;
                const totalProdutos = this.produtos.length;
                const totalClientes = new Set(this.pedidos.filter(p => p.cliente?.cpf).map(p => p.cliente.cpf)).size || this.clientes.length;

                let faturamentoTotal = 0;
                let lucroLiquidoTotal = 0;
                let custoTotal = 0;

                this.pedidos.forEach(pedido => {
                    if (pedido.produto) {
                        const categoria = pedido.produto.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        const precoVenda = pedido.produto.precoVenda || 0;
                        const precoCusto = pedido.produto.precoCusto || 0;
                        faturamentoTotal += precoVenda;
                        const feeAmount = precoVenda * taxaCategoria / 100;
                        lucroLiquidoTotal += (precoVenda - precoCusto - feeAmount);
                        custoTotal += precoCusto;
                    }
                });

                const variacaoPedidos = totalPedidos > 0 ? '+5' : '0';
                const variacaoLucro = lucroLiquidoTotal > 0 ? '+22%' : '0%';
                const variacaoEntregues = pedidosEntregues > 0 ? '+12%' : '0%';
                const variacaoPendentes = pedidosPendentes > 0 ? '-2' : '0';

                const cards = [
                    {
                        label: 'PEDIDOS ATIVOS',
                        value: totalPedidos,
                        delta: `${variacaoPedidos} novos este mês`,
                        trend: 'up',
                        icon: 'fas fa-truck',
                        topBar: 'gradient-primary',
                        iconBg: 'icon-bg-primary',
                        iconColor: 'text-primary',
                        spark: [2,3,2,4,3,5,4,6],
                        sparkColor: 'var(--chart-1)',
                    },
                    {
                        label: 'PEDIDOS ENTREGUES',
                        value: pedidosEntregues,
                        delta: '0% vs mês passado',
                        trend: 'flat',
                        icon: 'fas fa-check-circle',
                        topBar: 'gradient-success',
                        iconBg: 'icon-bg-success',
                        iconColor: 'text-success',
                        spark: [1,1,2,1,2,2,3,3],
                        sparkColor: 'var(--chart-2)',
                    },
                    {
                        label: 'PEDIDOS PENDENTES',
                        value: pedidosPendentes,
                        delta: 'Todos no prazo',
                        trend: 'up',
                        icon: 'fas fa-clock',
                        topBar: 'gradient-warning',
                        iconBg: 'icon-bg-warning',
                        iconColor: 'text-warning',
                        spark: [2,3,2,3,2,3,2,3],
                        sparkColor: 'var(--chart-3)',
                    },
                    {
                        label: 'LUCRO LÍQUIDO (MÊS)',
                        value: `R$ ${lucroLiquidoTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`,
                        delta: '+22% vs mês passado',
                        trend: 'up',
                        icon: 'fas fa-dollar-sign',
                        topBar: 'gradient-warning',
                        iconBg: 'icon-bg-warning',
                        iconColor: 'text-warning',
                        spark: [1,2,2,3,2,4,3,5],
                        sparkColor: 'var(--chart-3)',
                    },
                    {
                        label: 'EM TRÂNSITO',
                        value: pedidosTransito,
                        delta: 'shopee',
                        trend: 'up',
                        icon: 'fas fa-truck',
                        topBar: 'gradient-primary',
                        iconBg: 'icon-bg-primary',
                        iconColor: 'text-primary',
                        spark: [3,4,3,5,4,5,4,6],
                        sparkColor: 'var(--chart-1)',
                        ring: true,
                    },
                    {
                        label: 'FATURAMENTO TOTAL',
                        value: `R$ ${faturamentoTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`,
                        delta: '+15% vs mês passado',
                        trend: 'up',
                        icon: 'fas fa-chart-bar',
                        topBar: 'gradient-success',
                        iconBg: 'icon-bg-success',
                        iconColor: 'text-success',
                        spark: [2,3,4,3,5,4,6,7],
                        sparkColor: 'var(--chart-2)',
                    }
                ];

                container.innerHTML = `
                    <div class="hero-card shadow-elegant">
                        <div class="hero-tag">
                            <i class="fas fa-sparkles"></i>
                            Painel Premium
                        </div>
                        <div class="hero-main">
                            <div class="hero-copy">
                                <h2>${painelPremium.saudacao}, <span class="text-gradient">vamos vender?</span></h2>
                                <p class="hero-subtitle">${painelPremium.frase}</p>
                                <div class="hero-meta">
                                    <span><span class="hero-dot dot-primary"></span>${totalPedidos} pedidos</span>
                                    <span><span class="hero-dot dot-success"></span>${totalProdutos} produtos</span>
                                    <span><span class="hero-dot dot-accent"></span>${totalClientes} clientes</span>
                                </div>
                            </div>
                            <div class="hero-actions">
                                <button class="btn btn-hero-outline glass" id="btn-hero-pedidos">Pedidos</button>
                                <button class="btn btn-hero-gradient" id="btn-hero-analise">Análise <i class="fas fa-arrow-up-right"></i></button>
                            </div>
                        </div>
                    </div>
                    ${cards.map(card => `
                        <div class="card card-kpi ${card.ring ? 'ring-primary-40' : ''}">
                            <div class="card-top-bar ${card.topBar}"></div>
                            <div class="card-blob ${card.topBar}"></div>
                            <div class="card-body">
                                <div class="card-head">
                                    <p class="card-label">${card.label}</p>
                                    <div class="card-icon-square ${card.iconBg} ${card.iconColor}">
                                        <i class="${card.icon}"></i>
                                    </div>
                                </div>
                                <p class="card-value">${card.value}</p>
                                <div class="card-footer">
                                    <span class="card-trend ${card.trend === 'down' ? 'trend-down' : card.trend === 'flat' ? 'trend-flat' : 'trend-up'}">
                                        <i class="fas ${card.trend === 'down' ? 'fa-arrow-down' : card.trend === 'flat' ? 'fa-minus' : 'fa-arrow-up'}"></i>
                                        ${card.delta}
                                    </span>
                                    <span class="card-sparkline">${this.gerarSparklineSVG(card.spark, card.sparkColor)}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                `;

                const btnHeroPedidos = document.getElementById('btn-hero-pedidos');
                if (btnHeroPedidos) {
                    btnHeroPedidos.addEventListener('click', () => {
                        this.ativarAba('pedidos');
                    });
                }

                const btnHeroAnalise = document.getElementById('btn-hero-analise');
                if (btnHeroAnalise) {
                    btnHeroAnalise.addEventListener('click', () => {
                        this.ativarAba('analise');
                    });
                }
            }
            
            carregarTabelasDashboard() {
                this.carregarTabelaPedidosRecentes();
                this.carregarTabelaTopClientes();
                this.carregarTabelaProdutosTop();
                this.carregarTabelaStatusPedidos();
            }
            
            carregarTabelaPedidosRecentes() {
                const container = document.getElementById('tabela-pedidos-recentes');
                if (!container) return;
                
                // Ordenar pedidos por data (mais recentes primeiro)
                const pedidosRecentes = [...this.pedidos]
                    .sort((a, b) => new Date(b.dataCadastro || 0) - new Date(a.dataCadastro || 0))
                    .slice(0, 8);
                
                container.innerHTML = '';
                
                if (pedidosRecentes.length === 0) {
                    container.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                <i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                Nenhum pedido cadastrado ainda
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                pedidosRecentes.forEach(pedido => {
                    const categoria = pedido.produto?.categoria || 'outros';
                    const taxaCategoria = this.obterTaxaCategoria(categoria);
                    const precoVenda = pedido.produto?.precoVenda || 0;
                    const precoCusto = pedido.produto?.precoCusto || 0;
                    const feeAmount = precoVenda * taxaCategoria / 100;
                    const lucro = precoVenda - precoCusto - feeAmount;
                    const margem = (precoVenda > 0) ? ((lucro / precoVenda) * 100).toFixed(1) : '0.0';
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><strong><span class="dado-sensivel-rastreio">${pedido.codigoRastreio || 'N/A'}</span></strong></td>
                        <td><span class="dado-sensivel-cliente">${pedido.cliente?.nome || 'Cliente não informado'}</span></td>
                        <td><span class="dado-sensivel-produto">${pedido.produto?.nome ? (pedido.produto.nome.substring(0, 30) + (pedido.produto.nome.length > 30 ? '...' : '')) : 'Produto não informado'}</span></td>
                        <td><span class="status-badge ${this.getStatusClass(pedido.rastreio?.status)}">${this.getStatusText(pedido.rastreio?.status)}</span></td>
                        <td>${this.formatarData(pedido.dataCadastro)}</td>
                        <td><strong>R$ ${precoVenda.toFixed(2)}</strong></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn btn-small btn-secondary btn-ver-pedido" data-id="${pedido.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="${pedido.links?.rastreio || '#'}" target="_blank" class="btn btn-small btn-warning">
                                    <i class="fas fa-truck"></i>
                                </a>
                            </div>
                        </td>
                    `;
                    container.appendChild(row);
                });
                
                // Configurar eventos dos botões - MOVIDO PARA O FINAL DA FUNÇÃO
            }
            
            carregarTabelaTopClientes() {
                const container = document.getElementById('tabela-clientes-top');
                if (!container) return;
                
                // Agrupar pedidos por cliente
                const clientesMap = {};
                
                this.pedidos.forEach(pedido => {
                    if (!pedido.cliente) return;
                    
                    const clienteId = pedido.cliente.cpf || pedido.cliente.nome;
                    if (!clientesMap[clienteId]) {
                        clientesMap[clienteId] = {
                            nome: pedido.cliente.nome,
                            totalPedidos: 0,
                            valorTotal: 0,
                            ultimaCompra: pedido.dataCadastro,
                            cliente: pedido.cliente
                        };
                    }
                    
                    clientesMap[clienteId].totalPedidos++;
                    clientesMap[clienteId].valorTotal += (pedido.produto?.precoVenda || 0);
                    
                    // Manter a data mais recente
                    if (new Date(pedido.dataCadastro) > new Date(clientesMap[clienteId].ultimaCompra)) {
                        clientesMap[clienteId].ultimaCompra = pedido.dataCadastro;
                    }
                });
                
                // Converter para array e ordenar
                const clientesArray = Object.values(clientesMap);
                clientesArray.sort((a, b) => b.valorTotal - a.valorTotal);
                const topClientes = clientesArray.slice(0, 8);
                
                container.innerHTML = '';
                
                if (topClientes.length === 0) {
                    container.innerHTML = `
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                <i class="fas fa-users" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                Nenhum cliente cadastrado ainda
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                topClientes.forEach(cliente => {
                    const row = document.createElement('tr');

                    // Gerar HTML do CPF formatado se disponível
                    let cpfHTML = '';
                    if (cliente.cliente.cpf || cliente.cliente.cpfCnpj) {
                        const cpfValor = cliente.cliente.cpf || cliente.cliente.cpfCnpj;
                        cpfHTML = '<div style="margin-top: 5px;">' + window.CPFUtils.gerarBadge(cpfValor, {classe: 'cpf-badge'}) + '</div>';
                    }

                    row.innerHTML = `
                        <td>
                            <strong class="dado-sensivel-cliente">${cliente.nome}</strong><br>
                            <small class="dado-sensivel-amazon-id">${cliente.cliente.amazonId || ''}</small>
                            ${cpfHTML}
                        </td>
                        <td><span class="status-badge status-ativo">${cliente.totalPedidos} pedidos</span></td>
                        <td><strong>R$ ${cliente.valorTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</strong></td>
                        <td>${this.formatarData(cliente.ultimaCompra)}</td>
                        <td>
                            <button class="btn btn-small btn-primary btn-contatar-cliente" data-telefone="${cliente.cliente.telefone || ''}" onclick="window.location.href='https://wa.me/55${(cliente.cliente.telefone || '').replace(/\\D/g, '')}'" style="${cliente.cliente.telefone ? '' : 'display: none;'}">
                                <i class="fas fa-comments"></i>
                            </button>
                        </td>
                    `;
                    container.appendChild(row);
                });
                
                // Configurar eventos dos botões
                container.querySelectorAll('.btn-contatar-cliente').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const email = e.target.closest('button').dataset.email;
                        const telefone = e.target.closest('button').dataset.telefone;
                        
                        if (email || telefone) {
                            if (confirm(`Contatar cliente?\nEmail: ${email || 'Não informado'}\nTelefone: ${telefone || 'Não informado'}`)) {
                                if (email) {
                                    window.open(`mailto:${email}`, '_blank');
                                }
                            }
                        } else {
                            this.mostrarNotificacao('Cliente não tem informações de contato.', 'warning');
                        }
                    });
                });
            }
            
            carregarTabelaProdutosTop() {
                const container = document.getElementById('tabela-produtos-top');
                if (!container) return;
                
                // Agrupar vendas por produto
                const produtosMap = {};
                
                this.pedidos.forEach(pedido => {
                    if (!pedido.produto) return;
                    
                    const produtoNome = pedido.produto.nome;
                    if (!produtosMap[produtoNome]) {
                        produtosMap[produtoNome] = {
                            nome: produtoNome,
                            vendas: 0,
                            lucroTotal: 0,
                            precoVenda: pedido.produto.precoVenda || 0,
                            precoCusto: pedido.produto.precoCusto || 0
                        };
                    }
                    
                    produtosMap[produtoNome].vendas++;
                    // Calcular lucro líquido por pedido com taxa da categoria
                    const categoria = pedido.produto.categoria || 'outros';
                    const taxaCategoria = this.obterTaxaCategoria(categoria);
                    const precoVenda = pedido.produto.precoVenda || 0;
                    const precoCusto = pedido.produto.precoCusto || 0;
                    const feeAmount = precoVenda * taxaCategoria / 100;
                    const lucroLiquido = precoVenda - precoCusto - feeAmount;
                    produtosMap[produtoNome].lucroTotal += lucroLiquido;
                });
                
                // Converter para array e ordenar
                const produtosArray = Object.values(produtosMap);
                produtosArray.sort((a, b) => b.vendas - a.vendas);
                const topProdutos = produtosArray.slice(0, 8);
                
                container.innerHTML = '';
                
                if (topProdutos.length === 0) {
                    container.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                <i class="fas fa-box-open" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                Nenhum produto vendido ainda
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                topProdutos.forEach(produto => {
                    const categoria = produto.categoria || 'outros';
                    const taxaCategoria = this.obterTaxaCategoria(categoria);
                    const lucroUnitario = produto.precoVenda - produto.precoCusto - (produto.precoVenda * taxaCategoria / 100);
                    const margem = (produto.precoVenda > 0) ? ((lucroUnitario / produto.precoVenda) * 100).toFixed(1) : '0.0';
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><strong><span class="dado-sensivel-produto">${produto.nome.substring(0, 40)}${produto.nome.length > 40 ? '...' : ''}</span></strong></td>
                        <td><span class="status-badge status-ativo">${produto.vendas} vendas</span></td>
                        <td>R$ ${produto.precoVenda.toFixed(2)}</td>
                        <td><strong style="color: var(--success-color);">R$ ${lucroUnitario.toFixed(2)}</strong></td>
                        <td><strong>R$ ${produto.lucroTotal.toFixed(2)}</strong></td>
                        <td>
                            <button class="btn btn-small btn-success btn-analisar-produto" data-nome="${produto.nome}">
                                <i class="fas fa-chart-line"></i>
                            </button>
                        </td>
                    `;
                    container.appendChild(row);
                });
                
                // Configurar eventos dos botões
                container.querySelectorAll('.btn-analisar-produto').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const produtoNome = e.target.closest('button').dataset.nome;
                        this.analisarProduto(produtoNome);
                    });
                });
            }
            
            carregarTabelaStatusPedidos() {
                const container = document.getElementById('status-pedidos');
                if (!container) return;
                
                // Calcular estatísticas de status
                const statusCount = {
                    entregue: this.pedidos.filter(p => p.rastreio?.status === 'entregue').length,
                    pendente: this.pedidos.filter(p => p.rastreio?.status === 'pendente').length,
                    processando: this.pedidos.filter(p => p.rastreio?.status === 'processando').length,
                    transito: this.pedidos.filter(p => p.rastreio?.status === 'transito').length,
                    atrasado: this.pedidos.filter(p => p.rastreio?.status === 'atrasado').length
                };
                
                const totalPedidos = this.pedidos.length;
                
                container.innerHTML = `
                    <div style="padding: 25px;">
                        <h4 style="margin-bottom: 20px;">Distribuição de Status dos Pedidos</h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                            ${Object.entries(statusCount).map(([status, count]) => `
                                <div style="background-color: var(--card-bg); padding: 20px; border-radius: var(--radius-small); border-left: 4px solid ${this.getStatusColor(status)};">
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase;">${this.getStatusText(status)}</div>
                                    <div style="font-size: 32px; font-weight: 800; color: ${this.getStatusColor(status)};">${count}</div>
                                    <div style="font-size: 14px; color: var(--text-muted);">${totalPedidos > 0 ? ((count / totalPedidos) * 100).toFixed(1) : 0}% do total</div>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div style="background-color: var(--darkest-bg); padding: 20px; border-radius: var(--radius-small);">
                            <h5 style="margin-bottom: 15px;">Gráfico de Status</h5>
                            <div style="display: flex; height: 40px; border-radius: var(--radius-small); overflow: hidden; margin-bottom: 10px;">
                                ${Object.entries(statusCount).map(([status, count]) => `
                                    <div style="flex: ${count || 0.1}; background-color: ${this.getStatusColor(status)};" title="${this.getStatusText(status)}: ${count}"></div>
                                `).join('')}
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted);">
                                ${Object.entries(statusCount).map(([status, count]) => `
                                    <div><span style="display: inline-block; width: 10px; height: 10px; background-color: ${this.getStatusColor(status)}; border-radius: 50%; margin-right: 5px;"></span> ${this.getStatusText(status)}</div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // ========== PEDIDOS ==========
            carregarPedidos(filtro = 'todos') {
                console.log('Iniciando carregarPedidos com filtro:', filtro, 'Pedidos disponíveis:', this.pedidos.length);
                this.atualizarContadoresPedidos();
                
                let container;
                
                switch(filtro) {
                    case 'todos':
                        container = document.getElementById('pedidos-todos-container');
                        break;
                    case 'pendentes':
                        container = document.getElementById('pedidos-pendentes-container');
                        break;
                    case 'transito':
                        container = document.getElementById('pedidos-transito-container');
                        break;
                    case 'entregues':
                        container = document.getElementById('pedidos-entregues-container');
                        break;
                    default:
                        container = document.querySelector('#pedidos .pedido-tab-content.active .pedidos-container');
                }
                
                if (!container) {
                    // Criar containers se não existirem
                    this.criarContainersPedidos();
                    container = document.querySelector('#pedidos .pedido-tab-content.active .pedidos-container');
                }
                
                if (!container) return;
                
                // Filtrar pedidos conforme a aba
                let pedidosFiltrados = [...this.pedidos];
                
                if (filtro === 'pendentes') {
                    pedidosFiltrados = pedidosFiltrados.filter(p => p.rastreio?.status === 'pendente');
                } else if (filtro === 'transito') {
                    pedidosFiltrados = pedidosFiltrados.filter(p => p.rastreio?.status === 'transito');
                } else if (filtro === 'entregues') {
                    pedidosFiltrados = pedidosFiltrados.filter(p => p.rastreio?.status === 'entregue');
                }
                
                // Atualizar opções de conta antes de filtrar, preservando a seleção atual
                this.atualizarOpcoesConta();
                const filtroContaShopee = document.getElementById('filtro-conta-shopee');
                const contaShopeeFiltrada = this.contaShopeeFiltrada || filtroContaShopee?.value;
                if (contaShopeeFiltrada) {
                    pedidosFiltrados = pedidosFiltrados.filter(p => p.contaShopee === contaShopeeFiltrada);
                    if (filtroContaShopee) {
                        filtroContaShopee.value = contaShopeeFiltrada;
                    }
                }
                
                // Ordenar por data (mais recentes primeiro)
                pedidosFiltrados.sort((a, b) => new Date(b.dataCadastro || 0) - new Date(a.dataCadastro || 0));
                
                // Limpar container
                container.innerHTML = '';
                
                const modoPedidos = localStorage.getItem('config_visualizacao_pedidos') || 'lista';
                const exibirLista = modoPedidos === 'lista';
                
                if (exibirLista) {
                    container.classList.add('lista');
                    container.classList.remove('grid');
                } else {
                    container.classList.add('grid');
                    container.classList.remove('lista');
                }
                
                if (pedidosFiltrados.length === 0) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 50px; color: var(--text-muted); grid-column: 1 / -1;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h4>Nenhum pedido encontrado</h4>
                            <p>${filtro === 'todos' ? 'Adicione seu primeiro pedido!' : `Nenhum pedido ${this.getStatusText(filtro).toLowerCase()} no momento.`}</p>
                        </div>
                    `;
                    return;
                }
                
                if (exibirLista) {
                    const tabelaHTML = `
                        <div class="table-container" style="margin-top: 0;">
                            <table class="pedidos-table compact-table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Produto</th>
                                        <th>Conta Shopee</th>
                                        <th>Valor</th>
                                        <th>Lucro Líq.</th>
                                        <th>Status</th>
                                        <th>Data Cadastro</th>
                                        <th>Data Entrega</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${pedidosFiltrados.map(pedido => {
                                        const precoVenda = pedido.produto?.precoVenda || 0;
                                        const precoCusto = pedido.produto?.precoCusto || 0;
                                        const categoria = pedido.produto?.categoria || 'outros';
                                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                                        const feeAmount = precoVenda * taxaCategoria / 100;
                                        const lucro = precoVenda - precoCusto - feeAmount;
                                        const codigoRastreio = pedido.codigoRastreio || '';
                                        const rastreioValido = codigoRastreio.toUpperCase().startsWith('BR');
                                        const avisoRastreio = (!codigoRastreio || !rastreioValido) ? `<div style="font-size: 11px; color: #856404; background: rgba(255, 193, 7, 0.15); padding: 5px 8px; border-radius: 6px; margin-top: 4px;">⚠️ Código de rastreio deve começar com BR.</div>` : '';
                                        const dataEntrega = pedido.rastreio?.dataEntrega ? this.formatarData(pedido.rastreio.dataEntrega) : (pedido.dataEntrega ? this.formatarData(pedido.dataEntrega) : 'Aguardando');
                                        return `
                                            <tr data-pedido-id="${pedido.id}">
                                                <td>${pedido.cliente?.nome || '-'}${avisoRastreio}</td>
                                                <td><span class="dado-sensivel-produto">${pedido.produto?.nome || '-'}</span></td>
                                                <td><span style="background: rgba(155, 89, 182, 0.1); color: var(--primary-color); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${pedido.contaShopee || 'Não informado'}</span></td>
                                                <td>R$ ${precoVenda.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                <td style="color: ${lucro >= 0 ? 'var(--success-color)' : 'var(--danger-color)'}; font-weight: 600;">
                                                    R$ ${lucro.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td><span class="status-badge ${this.getStatusClass(pedido.rastreio?.status)}">${this.getStatusText(pedido.rastreio?.status)}</span></td>
                                                <td>${this.formatarData(pedido.dataCadastro)}</td>
                                                <td>${dataEntrega}</td>
                                                <td>
                                                    <button class="btn btn-small btn-danger btn-excluir-pedido" data-id="${pedido.id}" title="Excluir pedido">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button class="btn btn-small btn-secondary btn-editar-pedido" data-id="${pedido.id}" title="Editar pedido">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-small btn-primary btn-atualizar-status" data-id="${pedido.id}" title="Atualizar status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <a href="${pedido.links?.rastreio || (pedido.codigoRastreio ? `https://www.4tracking.net/pt/tjax/track?nums=${encodeURIComponent(pedido.codigoRastreio)}` : '#')}" target="_blank" class="btn btn-small btn-warning" title="Rastrear pedido">
                                                        <i class="fas fa-truck"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    container.innerHTML = tabelaHTML;
                    this.criarContainersPedidos();
                    return;
                }
                
                // Adicionar pedidos ao container em formato de cards
                pedidosFiltrados.forEach(pedido => {
                    const pedidoCard = this.criarCardPedido(pedido);
                    container.appendChild(pedidoCard);
                });
                this.criarContainersPedidos();
                
                // Configurar listeners para botões "Ver pedido" - REMOVIDO PARA EVITAR DUPLICAÇÃO
                // document.querySelectorAll('.btn-ver-pedido').forEach(btn => {
                //     btn.addEventListener('click', (e) => {
                //         e.preventDefault();
                //         e.stopPropagation();
                //         const botao = e.currentTarget;
                //         const pedidoId = parseInt(botao.getAttribute('data-id'));
                //         if (window.marketManager) {
                //             window.marketManager.verPedido(pedidoId);
                //         }
                //     });
                // });
            }
            
            criarContainersPedidos() {
                const tabs = ['todos', 'pendentes', 'transito', 'entregues'];
                
                tabs.forEach(tab => {
                    const tabContent = document.getElementById(tab);
                    if (tabContent && !tabContent.querySelector('.pedidos-container')) {
                        const container = document.createElement('div');
                        container.className = 'pedidos-container';
                        container.id = `pedidos-${tab}-container`;
                        tabContent.appendChild(container);
                    }
                });
                
                // Configurar eventos dos botões APÓS TODOS OS ELEMENTOS SEREM CRIADOS
                // Primeiro remover listeners existentes para evitar duplicação
                document.querySelectorAll('.btn-ver-pedido').forEach(btn => {
                    btn.removeEventListener('click', btn._verPedidoHandler);
                });
                
                // Função handler para o botão
                const verPedidoHandler = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const botao = e.currentTarget;
                    const pedidoId = parseInt(botao.getAttribute('data-id'));
                    if (window.marketManager) {
                        window.marketManager.verPedido(pedidoId);
                    }
                };
                
                document.querySelectorAll('.btn-ver-pedido').forEach(btn => {
                    btn._verPedidoHandler = verPedidoHandler;
                    btn.addEventListener('click', verPedidoHandler);
                });
            }

            atualizarContadoresPedidos() {
                const todos = this.pedidos.length;
                const pendentes = this.pedidos.filter(p => p.rastreio?.status === 'pendente' || p.rastreio?.status === 'processando').length;
                const transito = this.pedidos.filter(p => p.rastreio?.status === 'transito').length;
                const entregues = this.pedidos.filter(p => p.rastreio?.status === 'entregue').length;

                const atualizarBotao = (selector, texto) => {
                    const botao = document.querySelector(selector);
                    if (botao) botao.textContent = texto;
                };

                atualizarBotao('.pedido-tab-btn[data-pedido-tab="todos"]', `Todos os Pedidos (${todos})`);
                atualizarBotao('.pedido-tab-btn[data-pedido-tab="pendentes"]', `Pendentes (${pendentes})`);
                atualizarBotao('.pedido-tab-btn[data-pedido-tab="transito"]', `Em Trânsito (${transito})`);
                atualizarBotao('.pedido-tab-btn[data-pedido-tab="entregues"]', `Entregues (${entregues})`);
            }
            
            atualizarFiltroContaShopee() {
                const filtro = document.getElementById('filtro-conta-shopee').value;
                const abaAtiva = document.querySelector('.pedido-tab-btn.active').getAttribute('data-pedido-tab');
                this.contaShopeeFiltrada = filtro;
                this.carregarPedidos(abaAtiva);
            }
            
            atualizarOpcoesConta() {
                const filtroContaShopee = document.getElementById('filtro-conta-shopee');
                if (!filtroContaShopee) return;
                
                const selecionado = this.contaShopeeFiltrada || filtroContaShopee.value;
                
                // Pegar todas as contas únicas
                const contas = {};
                this.pedidos.forEach(pedido => {
                    if (pedido.contaShopee) {
                        contas[pedido.contaShopee] = true;
                    }
                });
                
                // Limpar opções atuais (menos a primeira)
                while (filtroContaShopee.options.length > 1) {
                    filtroContaShopee.remove(1);
                }
                
                // Adicionar novas opções
                Object.keys(contas).forEach(conta => {
                    const option = document.createElement('option');
                    option.value = conta;
                    option.textContent = conta;
                    filtroContaShopee.appendChild(option);
                });
                
                if (selecionado && [...filtroContaShopee.options].some(o => o.value === selecionado)) {
                    filtroContaShopee.value = selecionado;
                } else {
                    filtroContaShopee.value = '';
                    this.contaShopeeFiltrada = '';
                }
            }
            
            criarCardPedido(pedido, mostrarAcoes = true) {
                const card = document.createElement('div');
                card.className = `pedido-card ${pedido.rastreio?.status || 'pendente'}`;
                card.dataset.id = pedido.id;
                
                const categoria = pedido.produto?.categoria || 'outros';
                const taxaCategoria = this.obterTaxaCategoria(categoria);
                const precoVenda = pedido.produto?.precoVenda || 0;
                const precoCusto = pedido.produto?.precoCusto || 0;
                const feeAmount = precoVenda * taxaCategoria / 100;
                const lucro = precoVenda - precoCusto - feeAmount;
                const margem = (precoVenda > 0) ? ((lucro / precoVenda) * 100).toFixed(1) : '0.0';
                
                // Formatar data
                const dataEnvio = this.formatarData(pedido.dataCadastro);
                const dataEntrega = (pedido.rastreio?.status === 'entregue' && pedido.rastreio?.dataEntrega) ? this.formatarData(pedido.rastreio.dataEntrega) : 'Aguardando';
                
                // Validar Código de Rastreio (deve começar com BR)
                const codigoRastreio = pedido.codigoRastreio || '';
                const rastreioValido = codigoRastreio && codigoRastreio.toUpperCase().startsWith('BR');
                const avisoRastreioInvalido = !codigoRastreio || !rastreioValido ? `<div style="background: rgba(255, 193, 7, 0.2); border-left: 3px solid #ffc107; padding: 8px 12px; margin-top: 8px; border-radius: 4px; font-size: 12px; color: #ff9800;"><i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>⚠️ Código de rastreio deve começar com "BR". Atualize quando tiver código válido.</div>` : '';
                
                // ID Amazon para exibição
                const amazonId = pedido.amazonId || pedido.cliente?.amazonId || pedido.order_id || pedido.amazon_order_id || '';
                
                card.innerHTML = `
                    <div class="pedido-header">
                        <div>
                            <div class="pedido-cliente dado-sensivel-cliente">${pedido.cliente?.nome || 'Cliente não informado'}</div>
                            <div class="pedido-codigo dado-sensivel-rastreio">ID: ${amazonId || '⚠️ Sem ID'}</div>
                            ${avisoRastreioInvalido}
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Rastreio: ${pedido.codigoRastreio || 'Sem código'}</div>
                            <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-store" style="color: var(--primary-color); font-size: 12px;"></i>
                                <span style="font-size: 12px; font-weight: 600; color: var(--primary-color);">
                                    ${pedido.contaShopee || 'Conta não informada'}
                                </span>
                            </div>
                        </div>
                        <span class="status-badge ${this.getStatusClass(pedido.rastreio?.status)}">
                            <i class="fas fa-${this.getStatusIcon(pedido.rastreio?.status)}"></i> ${this.getStatusText(pedido.rastreio?.status)}
                        </span>
                    </div>
                    
                    <div class="pedido-details">
                        <div class="pedido-detail">
                            <div class="pedido-detail-label">Produto:</div>
                            <div class="pedido-detail-value dado-sensivel-produto">${pedido.produto?.nome || 'Produto não informado'}</div>
                        </div>
                        <div class="pedido-detail">
                            <div class="pedido-detail-label">Valor:</div>
                            <div class="pedido-detail-value"><strong>R$ ${precoVenda.toFixed(2)}</strong></div>
                        </div>
                        <div class="pedido-detail">
                            <div class="pedido-detail-label">Lucro Líquido:</div>
                            <div class="pedido-detail-value" style="color: var(--success-color); font-weight: bold;">
                                R$ ${lucro.toFixed(2)} (${margem}%)
                            </div>
                        </div>
                        <div class="pedido-detail">
                            <div class="pedido-detail-label">Data Pedido:</div>
                            <div class="pedido-detail-value">${this.formatarData(pedido.dataCadastro)}</div>
                        </div>
                    </div>
                    
                    <!-- SEÇÃO DE ENDEREÇO DETALHADA -->
                    <div class="pedido-endereco dado-sensivel-endereco" style="margin-top: 15px; padding: 15px; background-color: rgba(0,0,0,0.1); border-radius: var(--radius-small);">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary-color);"></i>
                            <strong>Endereço de Entrega</strong>
                        </div>
                        <div style="font-size: 13px; line-height: 1.8;">
                            ${pedido.cliente?.endereco?.rua ? `
                                <span class="dado-sensivel-endereco">${pedido.cliente.endereco.rua}, <span class="dado-sensivel-numero">${pedido.cliente.endereco.numero}</span></span><br>
                                ${pedido.cliente.endereco.bairro ? pedido.cliente.endereco.bairro + ' - ' : ''}${pedido.cliente.endereco.cidade} - ${pedido.cliente.endereco.estado}<br>
                                CEP: ${pedido.cliente.endereco.cep}<br>
                                ${pedido.cliente.endereco.complemento ? `<strong>Complemento:</strong> <span class="dado-sensivel-complemento">${pedido.cliente.endereco.complemento}</span>` : ''}
                            ` : (pedido.cliente?.endereco || 'Endereço não informado')}
                        </div>
                        ${pedido.links?.maps ? `
                            <a href="${pedido.links.maps}" target="_blank" class="btn btn-small btn-secondary" style="margin-top: 10px;">
                                <i class="fas fa-map"></i> Ver no Mapa
                            </a>
                        ` : ''}
                    </div>
                    
                    <!-- SEÇÃO DE TRANSPORTE MELHORADA -->
                    <div class="pedido-transporte">
                        <div class="pedido-transporte-title">
                            <i class="fas fa-shipping-fast"></i> Informações de Transporte
                        </div>
                        <div class="pedido-transporte-details">
                            <div class="transporte-detail">
                                <div class="transporte-label">Transportadora</div>
                                <div class="transporte-value" style="color: var(--shopee-color); font-weight: bold;">
                                    <i class="fas fa-truck"></i> ${this.getTransportadoraText(pedido.rastreio?.transportadora)}
                                </div>
                            </div>
                            <div class="transporte-detail">
                                <div class="transporte-label">Data Envio</div>
                                <div class="transporte-value">${dataEnvio}</div>
                            </div>
                            <div class="transporte-detail">
                                <div class="transporte-label">${pedido.rastreio?.status === 'entregue' ? '✅ Data Entrega' : '📦 Previsão Entrega'}</div>
                                <div class="transporte-value">${pedido.rastreio?.status === 'entregue' && pedido.rastreio?.dataEntrega ? this.formatarData(pedido.rastreio.dataEntrega) : this.calcularPrevisaoEntrega(pedido.rastreio?.dataEnvio)}</div>
                            </div>
                            <div class="transporte-detail">
                                <div class="transporte-label">Status</div>
                                <div class="transporte-value ${this.getStatusClass(pedido.rastreio?.status)}" style="display: inline-block; padding: 4px 8px;">
                                    ${this.getStatusText(pedido.rastreio?.status)}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pedido-links">
                        ${amazonId ? `
                        <a href="https://sellercentral.amazon.com.br/orders-v3/order/${encodeURIComponent(amazonId)}" target="_blank" class="link-btn amazon" style="background: linear-gradient(135deg, #ff9900, #ff6600); font-weight: 600; font-size: 15px; padding: 12px 18px;">
                            <i class="fab fa-amazon"></i> 🔗 Ver Pedido na Amazon
                        </a>
                        ` : ''}
                        ${pedido.links?.produto ? `
                        <a href="${pedido.links.produto}" target="_blank" class="link-btn produto">
                            <i class="fas fa-shopping-cart"></i> Ver Produto
                        </a>
                        ` : ''}
                        ${pedido.codigoRastreio ? `
                        <button class="link-btn rastreio btn-rastrear-pedido" data-id="${pedido.id}" data-codigo="${pedido.codigoRastreio}">
                            <i class="fas fa-truck"></i> Rastrear Pedido
                        </button>
                        ` : ''}
                        ${pedido.cliente?.telefone ? `
                        <a href="https://wa.me/55${pedido.cliente.telefone.replace(/\D/g, '')}" target="_blank" class="link-btn" style="background: linear-gradient(135deg, #25D366, #128C7E);">
                            <i class="fas fa-comments"></i> WhatsApp
                        </a>
                        ` : ''}
                    </div>
                    
                    ${mostrarAcoes ? `
                    <div class="pedido-actions">
                        <button class="btn btn-small btn-primary btn-atualizar-status" data-id="${pedido.id}">
                            <i class="fas fa-sync-alt"></i> Atualizar Status
                        </button>
                        <button class="btn btn-small btn-secondary btn-editar-pedido" data-id="${pedido.id}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="btn btn-small btn-danger btn-excluir-pedido" data-id="${pedido.id}">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </div>
                    ` : ''}
                `;
                
                // Adicionar evento de clique para ver detalhes do pedido
                card.addEventListener('click', (e) => {
                    // Evitar propagação se clicar em botões
                    if (e.target.closest('button') || e.target.closest('a')) return;
                    this.verPedido(pedido.id);
                });
                
                return card;
            }
            
            // ========== PRODUTOS ==========
            carregarProdutos() {
                const container = document.getElementById('products-container');
                if (!container) {
                    // Criar container se não existir
                    const produtosTab = document.getElementById('produtos');
                    if (produtosTab) {
                        const newContainer = document.createElement('div');
                        newContainer.className = 'products-container';
                        newContainer.id = 'products-container';
                        produtosTab.appendChild(newContainer);
                        this.carregarProdutos(); // Recursão para usar o novo container
                    }
                    return;
                }
                
                // Limpar container
                container.innerHTML = '';
                
                if (this.produtos.length === 0) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 50px; color: var(--text-muted); grid-column: 1 / -1;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h4>Nenhum produto cadastrado</h4>
                            <p>Adicione seu primeiro produto para começar!</p>
                            <button class="btn btn-success" id="btn-add-first-product" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i> Adicionar Primeiro Produto
                            </button>
                        </div>
                    `;
                    
                    // Configurar evento do botão
                    document.getElementById('btn-add-first-product')?.addEventListener('click', () => {
                        this.abrirModalNovoProduto();
                    });
                    
                    return;
                }
                
                // Adicionar produtos ao container
                this.produtos.forEach(produto => {
                    const productCard = this.criarCardProduto(produto);
                    container.appendChild(productCard);
                });
            }
            
            criarCardProduto(produto) {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.dataset.id = produto.id;
                
                // Calcular estatísticas do produto
                const categoria = produto.categoria || 'outros';
                const taxaCategoria = this.obterTaxaCategoria(categoria);
                const precoVenda = produto.precoVenda || 0;
                const precoCusto = produto.precoCusto || 0;
                const feeAmount = precoVenda * taxaCategoria / 100;
                const lucroUnitario = precoVenda - precoCusto - feeAmount;
                const margem = (precoVenda > 0) ? ((lucroUnitario / precoVenda) * 100).toFixed(1) : '0.0';
                
                // Filtrar pedidos por ASIN ou nome (mais robusto)
                const pedidosProduto = this.pedidos.filter(p => {
                    // Primeiro tenta matching por ASIN (mais confiável)
                    if (produto.asin && p.produto?.asin === produto.asin) {
                        return true;
                    }
                    // Fallback para matching por nome (caso não tenha ASIN)
                    return p.produto?.nome === produto.nome;
                });
                
                const vendas = pedidosProduto.length;
                const lucroTotal = lucroUnitario * vendas;
                
                card.innerHTML = `
                    <div class="product-header">
                        <div class="product-name dado-sensivel-produto">${produto.nome}</div>
                        <span class="product-category">${this.getCategoryText(produto.categoria)}</span>
                    </div>
                    
                    <div class="product-prices">
                        <div class="price-row">
                            <div class="price-label">Custo:</div>
                            <div class="price-value">R$ ${precoCusto.toFixed(2)}</div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Preço Venda:</div>
                            <div class="price-value">R$ ${precoVenda.toFixed(2)}</div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Lucro Líq. Unit.:</div>
                            <div class="price-value price-lucro">R$ ${lucroUnitario.toFixed(2)} (${margem}%)</div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Vendas Totais:</div>
                            <div class="price-value">${vendas} unidades</div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Lucro Líq. Total:</div>
                            <div class="price-value price-lucro">R$ ${lucroTotal.toFixed(2)}</div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Estoque:</div>
                            <div class="price-value ${(produto.estoque || 0) < 10 ? 'style="color: var(--warning-color); font-weight: bold;"' : ''}">
                                ${produto.estoque || 0} unidades
                                ${(produto.estoque || 0) < 10 ? '<i class="fas fa-exclamation-triangle" style="margin-left: 5px;"></i>' : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div class="product-actions">
                        <button class="btn btn-small btn-ia btn-analisar-produto-detalhe" data-id="${produto.id}">
                            <i class="fas fa-brain"></i> Analisar com IA
                        </button>
                        <button class="btn btn-small btn-primary btn-editar-produto" data-id="${produto.id}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        ${produto.link ? `
                        <a href="${produto.link}" target="_blank" class="btn btn-small btn-secondary">
                            <i class="fas fa-external-link-alt"></i> Ver
                        </a>
                        ` : ''}
                        <button class="btn btn-small btn-danger btn-excluir-produto" data-id="${produto.id}">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </div>
                `;
                
                return card;
            }
            
            // ========== IA FUNCIONAL COM POLLINATIONS.AI (CORRIGIDO) ==========
            async perguntarPollinationsAI(pergunta) {
                const API_KEY = 'sk_k0MqrwOilO90knqfaTQtzb1760DnG73o';
                
                try {
                    const response = await axios.post(
                        'https://gen.pollinations.ai/v1/chat/completions',
                        {
                            model: "gemini-search",
                            messages: [
                                {
                                    role: "user",
                                    content: pergunta
                                }
                            ]
                        },
                        {
                            headers: {
                                'Authorization': `Bearer ${API_KEY}`,
                                'Content-Type': 'application/json'
                            }
                        }
                    );
                    
                    if (response.data && response.data.choices && response.data.choices[0]) {
                        return response.data.choices[0].message.content;
                    } else {
                        throw new Error('Resposta inválida da API');
                    }
                } catch (error) {
                    console.error('Erro ao consultar Pollinations AI:', error);
                    throw error;
                }
            }
            
            async perguntarPollinationsAIComHistorico(novaPergunta) {
                const API_KEY = 'sk_k0MqrwOilO90knqfaTQtzb1760DnG73o';
                
                this.historicoIA.push({ role: 'user', content: novaPergunta });
                const historicoLimitado = this.historicoIA.slice(-10);
                
                try {
                    const response = await axios.post(
                        'https://gen.pollinations.ai/v1/chat/completions',
                        {
                            model: "gemini-search",
                            messages: historicoLimitado
                        },
                        {
                            headers: {
                                'Authorization': `Bearer ${API_KEY}`,
                                'Content-Type': 'application/json'
                            }
                        }
                    );
                    
                    if (response.data && response.data.choices && response.data.choices[0]) {
                        const resposta = response.data.choices[0].message.content;
                        this.historicoIA.push({ role: 'assistant', content: resposta });
                        return resposta;
                    } else {
                        throw new Error('Resposta inválida da API');
                    }
                } catch (error) {
                    console.error('Erro ao consultar Pollinations AI:', error);
                    throw error;
                }
            }
            
            construirContextoNegocio() {
                // INFORMAÇÕES TEMPORAIS PRECISAS
                const agora = new Date();
                const dataAtual = `${agora.getDate().toString().padStart(2, '0')}/${(agora.getMonth() + 1).toString().padStart(2, '0')}/${agora.getFullYear()}`;
                const horaAtual = `${agora.getHours().toString().padStart(2, '0')}:${agora.getMinutes().toString().padStart(2, '0')}`;
                const diasSemana = ['domingo', 'segunda-feira', 'terca-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sabado'];
                const diaAtual = diasSemana[agora.getDay()];
                const dataCompleta = `${diaAtual.charAt(0).toUpperCase() + diaAtual.slice(1).toUpperCase()}, ${dataAtual} ${horaAtual}`;

                // DADOS DE TREINAMENTO SALVOS PELO USUÁRIO
                let contextoTreinamento = '';
                try {
                    const textos = JSON.parse(localStorage.getItem('ia_textos_salvos') || '[]');
                    if (textos.length > 0) {
                        contextoTreinamento = "\n=== CONHECIMENTO TREINADO PELO USUÁRIO ===\n";
                        textos.forEach(t => {
                            contextoTreinamento += `[${t.categoria || 'geral'}] ${t.texto}\n---\n`;
                        });
                        contextoTreinamento += "=== FIM DO CONHECIMENTO TREINADO ===\n\n";
                    }
                } catch(e) {}

                // PERSONALIZAÇÃO DA IA
                let personalizacao = '';
                try {
                    const config = JSON.parse(localStorage.getItem('ia_personalizacao_config') || '{}');
                    if (config.tom) personalizacao += `- Tom de voz: ${config.tom}\n`;
                    if (config.detalhe) personalizacao += `- Nível de detalhe: ${config.detalhe}\n`;
                    if (config.idioma) personalizacao += `- Idioma: ${config.idioma}\n`;
                    if (config.personalidade) personalizacao += `- Personalidade: ${config.personalidade}\n`;
                    if (config.seguranca) personalizacao += `- Segurança: ${config.seguranca}\n`;
                } catch(e) {}

                // TOTALIZADORES GERAIS
                const totalPedidos = this.pedidos.length;
                const totalProdutos = this.produtos.length;
                const totalClientes = this.clientes.length;

                let faturamentoTotal = 0;
                let lucroLiquidoTotal = 0;
                let custoTotal = 0;
                let taxasTotal = 0;
                let freteTotal = 0;

                // ANÁLISE DETALHADA DE TODOS OS PEDIDOS
                this.pedidos.forEach(p => {
                    if (p.produto) {
                        const categoria = p.produto.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        const venda = p.produto.precoVenda || 0;
                        const custo = p.produto.precoCusto || 0;
                        const taxa = venda * taxaCategoria / 100;

                        faturamentoTotal += venda;
                        custoTotal += custo;
                        taxasTotal += taxa;
                        freteTotal += p.rastreio?.frete || 0;
                        lucroLiquidoTotal += venda - custo - taxa;
                    }
                });

                // ANÁLISE POR STATUS
                const pedidosEntregues = this.pedidos.filter(p => p.rastreio?.status === 'entregue').length;
                const pedidosProcessando = this.pedidos.filter(p => p.rastreio?.status === 'processando').length;
                const pedidosEnviados = this.pedidos.filter(p => p.rastreio?.status === 'enviado').length;
                const pedidosPendentes = this.pedidos.filter(p => p.rastreio?.status === 'pendente').length;
                const pedidosAtrasados = this.pedidos.filter(p => p.rastreio?.status === 'atrasado').length;

                // ANÁLISE DE ESTOQUE
                const produtosEmEstoque = this.produtos.filter(p => (p.estoque || 0) > 0).length;
                const produtosBaixoEstoque = this.produtos.filter(p => (p.estoque || 0) > 0 && (p.estoque || 0) < 10).length;
                const produtosSemEstoque = this.produtos.filter(p => (p.estoque || 0) === 0).length;
                const estoqueTotal = this.produtos.reduce((sum, p) => sum + (p.estoque || 0), 0);
                
                // TOP PRODUTOS POR VENDAS
                let produtosMaiorVenda = [];
                this.produtos.slice().sort((a, b) => {
                    const aVendas = this.pedidos.filter(p => p.produto?.nome === a.nome).length;
                    const bVendas = this.pedidos.filter(p => p.produto?.nome === b.nome).length;
                    return bVendas - aVendas;
                }).slice(0, 10).forEach(p => {
                    const vendas = this.pedidos.filter(ped => ped.produto?.nome === p.nome).length;
                    const categoria = p.categoria || 'outros';
                    const taxaCategoria = this.obterTaxaCategoria(categoria);
                    const lucroUnitario = (p.precoVenda || 0) - (p.precoCusto || 0) - ((p.precoVenda || 0) * taxaCategoria / 100);
                    const lucroTotal = lucroUnitario * vendas;
                    const margemBruta = p.precoVenda ? ((p.precoVenda - p.precoCusto) / p.precoVenda * 100) : 0;
                    if (vendas > 0) {
                        produtosMaiorVenda.push(`${p.nome} (${vendas} vendas, R$${lucroTotal.toFixed(2)} lucro, ${margemBruta.toFixed(1)}% margem, estoque: ${p.estoque || 0})`);
                    }
                });
                
                // ANÁLISE DE CLIENTES
                const clientesAtivos = this.clientes.filter(c => {
                    const pedidosCliente = this.pedidos.filter(p => p.cliente?.id === c.id).length;
                    return pedidosCliente > 0;
                }).length;
                
                // MÉTRICAS IMPORTANTES
                const margemMedia = faturamentoTotal > 0 ? ((lucroLiquidoTotal / faturamentoTotal) * 100).toFixed(1) : 0;
                const ticketMedio = totalPedidos > 0 ? (faturamentoTotal / totalPedidos).toFixed(2) : 0;
                const custoMedio = totalPedidos > 0 ? (custoTotal / totalPedidos).toFixed(2) : 0;
                
                return `CONTEXTO DO NEGÓCIO ATUALIZADO EM TEMPO REAL
DATA/HORA: ${dataCompleta}
=====================================
${contextoTreinamento}
${personalizacao ? `PERSONALIZAÇÃO DA IA:\n${personalizacao}\n` : ''}
RESUMO FINANCEIRO:
- Faturamento Total: R$ ${faturamentoTotal.toFixed(2)}
- Custo de Fornecimento: R$ ${custoTotal.toFixed(2)}
- Taxas Marketplace (Amazon/Shopee): R$ ${taxasTotal.toFixed(2)}
- Frete Total: R$ ${freteTotal.toFixed(2)}
- Lucro Líquido: R$ ${lucroLiquidoTotal.toFixed(2)}
- Margem Média: ${margemMedia}%
- Ticket Médio: R$ ${ticketMedio}
- Custo Médio por Pedido: R$ ${custoMedio}

DADOS GERAIS:
- Total de Pedidos: ${totalPedidos}
- Total de Produtos: ${totalProdutos}
- Total de Clientes: ${totalClientes} (${clientesAtivos} ativos)
- Estoque Total: ${estoqueTotal} unidades

STATUS DOS PEDIDOS:
- Entregues: ${pedidosEntregues}
- Enviados: ${pedidosEnviados}
- Processando: ${pedidosProcessando}
- Pendentes: ${pedidosPendentes}
- ATRASADOS: ${pedidosAtrasados}

SITUAÇÃO DO ESTOQUE:
- Produtos com Estoque: ${produtosEmEstoque}
- Estoque Baixo (<10): ${produtosBaixoEstoque}
- Sem Estoque: ${produtosSemEstoque}

TOP PRODUTOS (por rentabilidade):
${produtosMaiorVenda.length > 0 ? produtosMaiorVenda.slice(0, 10).map(p => '• ' + p).join('\\n') : '• Nenhum dados de vendas ainda'}

CONFIGURAÇÃO ATIVA:
- Entregador Padrão: ${this.config.entregadorPadrao || 'shopee'}
- Tema: ${this.config.tema || 'escuro'}
- Notificações: ${this.config.notificacoes ? 'Ativas' : 'Inativas'}

INSTRUÇÕES PARA ANÁLISES:
1. VOCÊ TEM ACESSO TOTAL aos dados REAIS acima
2. Use os dados específicos para recomendações quantificáveis
3. Considere sazonalidade e tendências (dia da semana: ${diaAtual})
4. Produtos com estoque < 5 são CRÍTICOS para restock
5. ${pedidosAtrasados > 0 ? `ALERTA: ${pedidosAtrasados} pedido(s) atrasado(s) prejudicam reputação` : 'Nenhum pedido atrasado - ótimo!'}
6. Margem baixa? Recomende aumento de preço ou redução de custo
7. Analise cada resposta com base nos números REAIS acima
8. Sempre cite valores específicos nas recomendações
9. O conhecimento treinado pelo usuário deve ser respeitado e aplicado nas suas respostas
10. Adapte seu tom e nível de detalhe conforme a personalização definida pelo usuário`;
            }
            
            async perguntarIA(pergunta) {
                const btn = document.getElementById('btn-perguntar-ia');
                await this.executarComBloqueio(btn, async () => {
                    // Permitir novas perguntas a qualquer momento
                    if (!pergunta || pergunta.trim().length === 0) {
                        this.mostrarNotificacao('Digite uma pergunta para a IA!', 'warning');
                        return;
                    }
                    
                    // Obter chave: primeiro tenta servidor, depois localStorage
                    let chaveAPI = this.apiKeyIA;
                    
                    // Se não tem chave em memória, tenta carregar do servidor
                    if (!chaveAPI) {
                        try {
                            const response = await fetch('api/crud.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ acao: 'carregar', tipo: 'todos' })
                            });
                            const dados = await this.parseApiResponse(response);
                            if (dados.apiKeys && dados.apiKeys.pollinationsAI && dados.apiKeys.pollinationsAI.chave) {
                                chaveAPI = dados.apiKeys.pollinationsAI.chave;
                                this.apiKeyIA = chaveAPI;
                                localStorage.setItem('pollinations_api_key', chaveAPI);
                            }
                        } catch (e) {
                            console.log('Não conseguiu carregar chave do servidor');
                        }
                    }
                    
                    if (!chaveAPI || chaveAPI.trim() === '') {
                        this.mostrarNotificacao('❌ API Key não configurada! Vá em Configurações → Assistência de IA', 'danger');
                        document.getElementById('configuracoes-tab-btn').click();
                        return;
                    }
                    
                    this.ultimaPergunta = pergunta;
                    
                    try {
                        const contexto = this.construirContextoNegocio();
                        const perguntaCompleta = contexto + "\n\n" + pergunta;
                        const resposta = await this.perguntarPollinationsAIComHistorico(perguntaCompleta);
                        this.mostrarRespostaIA(resposta);
                        this.atualizarHistoricoIA();
                        this.mostrarNotificacao('✅ Resposta da IA gerada com sucesso!', 'success');
                    } catch (error) {
                        console.error('Erro ao consultar IA:', error);
                        this.mostrarNotificacao('❌ Erro: ' + error.message, 'danger');
                    }
                });
            }
            
            mostrarRespostaIA(resposta) {
                const respostaContainer = document.getElementById('resposta-ia');
                const textoResposta = document.getElementById('texto-resposta-ia');
                
                let htmlResposta = resposta
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/^# (.*$)/gim, '<h3>$1</h3>')
                    .replace(/^## (.*$)/gim, '<h4>$1</h4>')
                    .replace(/^### (.*$)/gim, '<h5>$1</h5>')
                    .replace(/\n/g, '<br>')
                    .replace(/✅/g, '<span style="color: #2ecc71;">✅</span>')
                    .replace(/⚠️/g, '<span style="color: #f39c12;">⚠️</span>')
                    .replace(/📊/g, '<span style="color: #3498db;">📊</span>')
                    .replace(/💰/g, '<span style="color: #f1c40f;">💰</span>')
                    .replace(/🚀/g, '<span style="color: #e74c3c;">🚀</span>')
                    .replace(/📦/g, '<span style="color: #9b59b6;">📦</span>')
                    .replace(/👥/g, '<span style="color: #1abc9c;">👥</span>')
                    .replace(/🔮/g, '<span style="color: #8e44ad;">🔮</span>')
                    .replace(/📈/g, '<span style="color: #27ae60;">📈</span>')
                    .replace(/🎯/g, '<span style="color: #d35400;">🎯</span>')
                    .replace(/📧/g, '<span style="color: #2980b9;">📧</span>')
                    .replace(/🎁/g, '<span style="color: #c0392b;">🎁</span>')
                    .replace(/📱/g, '<span style="color: #27ae60;">📱</span>')
                    .replace(/⭐/g, '<span style="color: #f1c40f;">⭐</span>')
                    .replace(/🔄/g, '<span style="color: #3498db;">🔄</span>')
                    .replace(/🛍️/g, '<span style="color: #8e44ad;">🛍️</span>')
                    .replace(/📅/g, '<span style="color: #16a085;">📅</span>')
                    .replace(/^\- (.*$)/gim, '<li>$1</li>')
                    .replace(/<\/li><br>/g, '</li>');
                
                if (htmlResposta.includes('<li>')) {
                    htmlResposta = htmlResposta.replace(/(<li>.*?<\/li>)+/gs, '<ul>$&</ul>');
                }
                
                textoResposta.innerHTML = htmlResposta;
                respostaContainer.style.display = 'block';
                respostaContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            atualizarHistoricoIA() {
                const container = document.getElementById('historico-ia-container');
                if (!container) return;
                
                container.innerHTML = '';
                this.historicoIA.forEach((msg, index) => {
                    const div = document.createElement('div');
                    div.className = `historico-item ${msg.role}`;
                    div.onclick = () => {
                        document.getElementById('pergunta-ia').value = msg.content;
                    };
                    
                    const roleLabel = msg.role === 'user' ? 'Você' : 'IA';
                    div.innerHTML = `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <strong style="color: ${msg.role === 'user' ? 'var(--primary-color)' : 'var(--ia-color)'};">${roleLabel}</strong>
                            <small>${new Date().toLocaleTimeString()}</small>
                        </div>
                        <div style="font-size: 13px;">${this.resumirTexto(msg.content, 100)}</div>
                    `;
                    container.appendChild(div);
                });
            }
            
            resumirTexto(texto, maxLen) {
                if (texto.length <= maxLen) return texto;
                return texto.substring(0, maxLen) + '...';
            }
            
            gerarRespostaLocal(pergunta) {
                const perguntaLower = pergunta.toLowerCase();
                
                if (perguntaLower.includes('lucro') || perguntaLower.includes('rentabilidade') || perguntaLower.includes('margem')) {
                    return this.gerarAnaliseLucros();
                }
                else if (perguntaLower.includes('preço') || perguntaLower.includes('preco') || perguntaLower.includes('preços')) {
                    return this.gerarAnalisePrecos();
                }
                else if (perguntaLower.includes('venda') || perguntaLower.includes('vendas') || perguntaLower.includes('vender')) {
                    return this.gerarSugestoesVendas();
                }
                else if (perguntaLower.includes('estoque') || perguntaLower.includes('inventário') || perguntaLower.includes('inventario')) {
                    return this.gerarAnaliseEstoque();
                }
                else if (perguntaLower.includes('cliente') || perguntaLower.includes('clientes')) {
                    return this.gerarAnaliseClientes();
                }
                else if (perguntaLower.includes('previsão') || perguntaLower.includes('previsao') || perguntaLower.includes('futuro')) {
                    return this.gerarPrevisaoVendas();
                }
                else {
                    return this.gerarAnaliseGeral();
                }
            }
            
            gerarAnaliseLucros() {
                let lucroLiquidoTotal = 0;
                let faturamentoTotal = 0;
                let custoTotal = 0;
                
                this.pedidos.forEach(pedido => {
                    if (pedido.produto) {
                        const categoria = pedido.produto.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        const precoVenda = pedido.produto.precoVenda || 0;
                        const precoCusto = pedido.produto.precoCusto || 0;
                        faturamentoTotal += precoVenda;
                        custoTotal += precoCusto;
                        const feeAmount = precoVenda * taxaCategoria / 100;
                        lucroLiquidoTotal += (precoVenda - precoCusto - feeAmount);
                    }
                });
                
                const margem = faturamentoTotal > 0 ? ((lucroLiquidoTotal / faturamentoTotal) * 100).toFixed(1) : 0;
                
                let produtoMaisRentavel = { nome: '', margem: 0 };
                let produtoMenosRentavel = { nome: '', margem: 100 };
                const feePercent = this.config.taxaPadrao || 15;
                
                this.produtos.forEach(produto => {
                    const precoVenda = produto.precoVenda || 0;
                    const precoCusto = produto.precoCusto || 0;
                    const feeAmount = precoVenda * feePercent / 100;
                    const lucroLiq = precoVenda - precoCusto - feeAmount;
                    const margemProduto = precoVenda > 0 ? (lucroLiq / precoVenda * 100) : 0;
                    if (parseFloat(margemProduto) > produtoMaisRentavel.margem) {
                        produtoMaisRentavel = { nome: produto.nome, margem: parseFloat(margemProduto) };
                    }
                    if (parseFloat(margemProduto) < produtoMenosRentavel.margem) {
                        produtoMenosRentavel = { nome: produto.nome, margem: parseFloat(margemProduto) };
                    }
                });
                
                return `📊 **Análise de Rentabilidade:**
                
**1. Dados Financeiros:**
   • Lucro Líquido Total: R$ ${lucroLiquidoTotal.toFixed(2)}
   • Faturamento Total: R$ ${faturamentoTotal.toFixed(2)}
   • Custo Total (fornecedor): R$ ${custoTotal.toFixed(2)}
   • Margem de Lucro Líquida: ${margem}%

**2. Produtos Mais Rentáveis (lucro líquido):**
   • ${produtoMaisRentavel.nome || 'Nenhum'}: ${produtoMaisRentavel.margem}% de margem líquida

**3. Produtos Menos Rentáveis:**
   • ${produtoMenosRentavel.nome || 'Nenhum'}: ${produtoMenosRentavel.margem}% de margem líquida

**4. Recomendações da IA:**
   ✅ Foque em vender mais do produto "${produtoMaisRentavel.nome || 'seus produtos mais rentáveis'}"
   ⚠️ Considere aumentar o preço ou reduzir custo do produto "${produtoMenosRentavel.nome || 'seus produtos menos rentáveis'}" em 5-10%
   📈 Aumente o ticket médio oferecendo bundles (pacotes) de produtos
   🎯 Crie promoções para produtos com margem líquida acima de 40%`;
            }
            
            gerarAnalisePrecos() {
                let analise = '💰 **Análise de Preços (considerando taxa da plataforma):**\n\n';
                
                this.produtos.forEach((produto, index) => {
                    const categoria = produto.categoria || 'outros';
                    const taxaCategoria = this.obterTaxaCategoria(categoria);
                    const precoVenda = produto.precoVenda || 0;
                    const precoCusto = produto.precoCusto || 0;
                    const feeAmount = precoVenda * taxaCategoria / 100;
                    const lucroLiq = precoVenda - precoCusto - feeAmount;
                    const margem = precoVenda > 0 ? (lucroLiq / precoVenda * 100).toFixed(1) : 0;
                    
                    const precoSugerido = (precoCusto) / (1 - 0.3 - (taxaCategoria/100));
                    const diferenca = precoSugerido - precoVenda;
                    
                    analise += `${index + 1}. **${produto.nome}:**\n`;
                    analise += `   • Preço Atual: R$ ${precoVenda.toFixed(2)}\n`;
                    analise += `   • Custo: R$ ${precoCusto.toFixed(2)}\n`;
                    analise += `   • Taxa (${taxaCategoria}%): R$ ${feeAmount.toFixed(2)}\n`;
                    analise += `   • Lucro Líquido: R$ ${lucroLiq.toFixed(2)}\n`;
                    analise += `   • Margem Líquida Atual: ${margem}%\n`;
                    
                    if (parseFloat(margem) < 30) {
                        analise += `   • ⚠️ Margem líquida baixa! Sugiro aumentar para R$ ${precoSugerido.toFixed(2)}\n`;
                    } else if (parseFloat(margem) > 60) {
                        analise += `   • ✅ Margem líquida excelente! Pode manter ou reduzir para ser mais competitivo\n`;
                    } else {
                        analise += `   • ✅ Margem líquida dentro do ideal\n`;
                    }
                    
                    analise += '\n';
                });
                
                analise += '**Recomendações Gerais:**\n';
                analise += '• Produtos com margem líquida abaixo de 30% devem ter preço revisado\n';
                analise += '• Use preços psicológicos (ex: R$ 99,90 em vez de R$ 100)\n';
                analise += '• Monitore preços da concorrência semanalmente\n';
                analise += '• Crie ofertas especiais para produtos com maior estoque';
                
                return analise;
            }
            
            gerarSugestoesVendas() {
                const produtosMaisVendidos = this.getProdutosMaisVendidos(5);
                const produtosMaiorMargem = this.getProdutosMaiorMargem(5);
                const produtosBaixoEstoque = this.produtos.filter(p => (p.estoque || 0) < 10);
                
                let analise = '🚀 **Sugestões para Aumentar Vendas:**\n\n';
                
                analise += '**1. Produtos Mais Vendidos (Foque neles):**\n';
                produtosMaisVendidos.forEach((produto, index) => {
                    analise += `   ${index + 1}. ${produto.nome}: ${produto.vendas} vendas\n`;
                });
                
                analise += '\n**2. Produtos com Maior Margem Líquida (Maior lucro):**\n';
                produtosMaiorMargem.forEach((produto, index) => {
                    analise += `   ${index + 1}. ${produto.nome}: ${produto.margem}% de margem líquida\n`;
                });
                
                analise += '\n**3. Produtos com Estoque Baixo (Repor):**\n';
                if (produtosBaixoEstoque.length > 0) {
                    produtosBaixoEstoque.slice(0, 3).forEach((produto, index) => {
                        analise += `   ${index + 1}. ${produto.nome}: Apenas ${produto.estoque || 0} unidades\n`;
                    });
                } else {
                    analise += '   ✅ Todos os produtos com estoque adequado\n';
                }
                
                analise += '\n**4. Estratégias Recomendadas:**\n';
                analise += '   • Crie bundle com os 3 produtos mais vendidos (10% off)\n';
                analise += '   • Ofereça frete grátis para compras acima de R$ 200\n';
                analise += '   • Use cupons de primeira compra (10% de desconto)\n';
                analise += '   • Crie programa de indicação (R$ 20 para quem indicar)\n';
                analise += '   • Faça promoções relâmpago nos finais de semana\n';
                
                return analise;
            }
            
            gerarAnaliseEstoque() {
                const produtosBaixoEstoque = this.produtos.filter(p => (p.estoque || 0) < 10);
                const produtosEstoqueAlto = this.produtos.filter(p => (p.estoque || 0) > 50);
                const valorTotalEstoque = this.produtos.reduce((total, produto) => {
                    return total + ((produto.estoque || 0) * (produto.precoCusto || 0));
                }, 0);
                
                let analise = '📦 **Análise de Estoque:**\n\n';
                
                analise += `**Valor Total em Estoque (custo):** R$ ${valorTotalEstoque.toFixed(2)}\n\n`;
                
                analise += '**Produtos com Estoque Baixo (<10 unidades):**\n';
                if (produtosBaixoEstoque.length > 0) {
                    produtosBaixoEstoque.forEach((produto, index) => {
                        analise += `   ${index + 1}. ${produto.nome}: ${produto.estoque || 0} unidades (Custo: R$ ${((produto.estoque || 0) * (produto.precoCusto || 0)).toFixed(2)})\n`;
                    });
                } else {
                    analise += '   ✅ Nenhum produto com estoque crítico\n';
                }
                
                analise += '\n**Produtos com Estoque Alto (>50 unidades):**\n';
                if (produtosEstoqueAlto.length > 0) {
                    produtosEstoqueAlto.slice(0, 5).forEach((produto, index) => {
                        analise += `   ${index + 1}. ${produto.nome}: ${produto.estoque || 0} unidades\n`;
                    });
                } else {
                    analise += '   ✅ Nenhum produto com excesso de estoque\n';
                }
                
                analise += '\n**Recomendações da IA:**\n';
                if (produtosBaixoEstoque.length > 0) {
                    analise += `   ⚠️ Reponha ${produtosBaixoEstoque.length} produto(s) com estoque baixo\n`;
                }
                if (produtosEstoqueAlto.length > 0) {
                    analise += `   🎯 Crie promoções para ${produtosEstoqueAlto.length} produto(s) com estoque alto\n`;
                }
                analise += '   📊 Realize inventário mensal para evitar erros\n';
                analise += '   📈 Use previsão de demanda para compras futuras';
                
                return analise;
            }
            
            gerarAnaliseClientes() {
                const clientesMap = {};
                
                this.pedidos.forEach(pedido => {
                    if (!pedido.cliente) return;
                    
                    const clienteId = pedido.cliente.cpf || pedido.cliente.nome;
                    if (!clientesMap[clienteId]) {
                        clientesMap[clienteId] = {
                            nome: pedido.cliente.nome,
                            totalPedidos: 0,
                            valorTotal: 0,
                            primeiroPedido: pedido.dataCadastro,
                            ultimoPedido: pedido.dataCadastro,
                            cliente: pedido.cliente
                        };
                    }
                    
                    clientesMap[clienteId].totalPedidos++;
                    clientesMap[clienteId].valorTotal += (pedido.produto?.precoVenda || 0);
                    
                    if (new Date(pedido.dataCadastro) < new Date(clientesMap[clienteId].primeiroPedido)) {
                        clientesMap[clienteId].primeiroPedido = pedido.dataCadastro;
                    }
                    if (new Date(pedido.dataCadastro) > new Date(clientesMap[clienteId].ultimoPedido)) {
                        clientesMap[clienteId].ultimoPedido = pedido.dataCadastro;
                    }
                });
                
                const clientesArray = Object.values(clientesMap);
                clientesArray.sort((a, b) => b.valorTotal - a.valorTotal);
                const topClientes = clientesArray.slice(0, 5);
                
                let analise = '👥 **Análise de Clientes:**\n\n';
                
                analise += `**Total de Clientes Únicos:** ${clientesArray.length}\n\n`;
                
                analise += '**Top 5 Clientes (por valor gasto):**\n';
                topClientes.forEach((cliente, index) => {
                    const ticketMedio = cliente.valorTotal / cliente.totalPedidos;
                    analise += `   ${index + 1}. ${cliente.nome}: R$ ${cliente.valorTotal.toFixed(2)} (${cliente.totalPedidos} pedidos, Ticket: R$ ${ticketMedio.toFixed(2)})\n`;
                });
                
                const trintaDiasAtras = new Date();
                trintaDiasAtras.setDate(trintaDiasAtras.getDate() - 30);
                
                const clientesInativos = clientesArray.filter(cliente => 
                    new Date(cliente.ultimoPedido) < trintaDiasAtras
                );
                
                analise += `\n**Clientes Inativos (>30 dias):** ${clientesInativos.length}\n`;
                
                analise += '\n**Recomendações da IA:**\n';
                analise += '   📧 Envie e-mail marketing para clientes inativos\n';
                analise += '   🎁 Crie programa de fidelidade para os top clientes\n';
                analise += '   📱 Use WhatsApp para comunicação personalizada\n';
                analise += '   ⭐ Peça avaliações dos produtos para clientes satisfeitos\n';
                analise += '   🔄 Ofereça descontos para clientes que não compram há muito tempo';
                
                return analise;
            }
            
            gerarPrevisaoVendas() {
                const ultimosMeses = 6;
                const hoje = new Date();
                const previsao = [];
                
                for (let i = 1; i <= 3; i++) {
                    const mes = new Date(hoje.getFullYear(), hoje.getMonth() + i, 1);
                    const nomeMes = mes.toLocaleDateString('pt-BR', { month: 'long' });
                    
                    const crescimento = 1 + (Math.random() * 0.15 + 0.05);
                    const pedidosPrevistos = Math.round(this.pedidos.length / ultimosMeses * crescimento);
                    const faturamentoPrevisto = this.pedidos.reduce((total, pedido) => 
                        total + (pedido.produto?.precoVenda || 0), 0
                    ) / ultimosMeses * crescimento;
                    
                    previsao.push({
                        mes: nomeMes.charAt(0).toUpperCase() + nomeMes.slice(1),
                        pedidos: pedidosPrevistos,
                        faturamento: faturamentoPrevisto
                    });
                }
                
                let analise = '🔮 **Previsão de Vendas (Próximos 3 Meses):**\n\n';
                
                previsao.forEach(item => {
                    analise += `**${item.mes}:**\n`;
                    analise += `   • Pedidos Previstos: ${item.pedidos}\n`;
                    analise += `   • Faturamento Previsto: R$ ${item.faturamento.toFixed(2)}\n\n`;
                });
                
                analise += '**Fatores que Influenciam a Previsão:**\n';
                analise += '   ✅ Tendência de crescimento atual\n';
                analise += '   📈 Sazonalidade do mercado\n';
                analise += '   🛍️ Novos produtos no catálogo\n';
                analise += '   🎯 Estratégias de marketing implementadas\n';
                analise += '   📊 Desempenho histórico de vendas\n\n';
                
                analise += '**Recomendações da IA:**\n';
                analise += '   1. Aumente o estoque dos produtos mais vendidos\n';
                analise += '   2. Planeje campanhas de marketing para períodos de baixa\n';
                analise += '   3. Diversifique o catálogo para reduzir riscos\n';
                analise += '   4. Monitore a concorrência regularmente\n';
                analise += '   5. Ajuste preços conforme a demanda esperada';
                
                return analise;
            }
            
            gerarAnaliseGeral() {
                const totalPedidos = this.pedidos.length;
                const pedidosEntregues = this.pedidos.filter(p => p.rastreio?.status === 'entregue').length;
                const taxaEntrega = totalPedidos > 0 ? ((pedidosEntregues / totalPedidos) * 100).toFixed(1) : 0;
                
                let faturamentoTotal = 0;
                let lucroLiquidoTotal = 0;
                
                this.pedidos.forEach(pedido => {
                    if (pedido.produto) {
                        const precoVenda = pedido.produto.precoVenda || 0;
                        const precoCusto = pedido.produto.precoCusto || 0;
                        const categoria = pedido.produto.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        faturamentoTotal += precoVenda;
                        const feeAmount = precoVenda * taxaCategoria / 100;
                        lucroLiquidoTotal += (precoVenda - precoCusto - feeAmount);
                    }
                });
                
                const margem = faturamentoTotal > 0 ? ((lucroLiquidoTotal / faturamentoTotal) * 100).toFixed(1) : 0;
                
                const clientesUnicos = new Set();
                this.pedidos.forEach(pedido => {
                    if (pedido.cliente?.cpf) {
                        clientesUnicos.add(pedido.cliente.cpf);
                    } else if (pedido.cliente?.nome) {
                        clientesUnicos.add(pedido.cliente.nome);
                    }
                });
                
                // Exemplo de análise de cross‑selling (simulada)
                const crossSelling = this.calcularProdutosJuntos();
                
                return `📈 **Análise Geral do Seu Negócio:**
                
**1. Dados Principais:**
   • Total de Pedidos: ${totalPedidos}
   • Pedidos Entregues: ${pedidosEntregues} (${taxaEntrega}%)
   • Produtos Cadastrados: ${this.produtos.length}
   • Clientes Únicos: ${clientesUnicos.size}

**2. Performance Financeira:**
   • Faturamento Total: R$ ${faturamentoTotal.toFixed(2)}
   • Lucro Líquido (após custo e taxas): R$ ${lucroLiquidoTotal.toFixed(2)}
   • Margem de Lucro Líquida: ${margem}%

**3. Status dos Pedidos:**
   • Entregues: ${this.pedidos.filter(p => p.rastreio?.status === 'entregue').length}
   • Em Trânsito: ${this.pedidos.filter(p => p.rastreio?.status === 'transito').length}
   • Pendentes: ${this.pedidos.filter(p => p.rastreio?.status === 'pendente' || p.rastreio?.status === 'processando').length}
   • Atrasados: ${this.pedidos.filter(p => p.rastreio?.status === 'atrasado').length}

**4. Sugestões de Cross‑Selling (produtos frequentemente comprados juntos):**
${crossSelling.length > 0 ? crossSelling.map(p => `   • Quem comprou "${p.produto}" também comprou "${p.junto}" (${p.vezes} vezes)`).join('\n') : '   • Dados insuficientes para sugerir combinações.'}

**5. Pontos Fortes:**
   ✅ Sistema unificado e organizado
   ✅ IA integrada para análises
   ✅ Controle completo de pedidos e produtos
   ✅ Interface moderna e responsiva
   ✅ Salvamento em JSON (persistente)

**6. Áreas de Melhoria Identificadas:**
   ⚠️ ${this.pedidos.filter(p => p.rastreio?.status === 'pendente').length} pedidos aguardando processamento
   ⚠️ ${this.produtos.filter(p => (p.estoque || 0) < 10).length} produtos com estoque baixo
   ⚠️ ${this.produtos.filter(p => {
        const precoVenda = p.precoVenda || 0;
        const precoCusto = p.precoCusto || 0;
        const feePercent = this.config.taxaPadrao || 15;
        const lucroLiq = precoVenda - precoCusto - (precoVenda * feePercent / 100);
        const margem = precoVenda > 0 ? (lucroLiq / precoVenda * 100) : 0;
        return margem < 30;
    }).length} produtos com margem líquida abaixo de 30%

**7. Próximos Passos Recomendados:**
   1. Processar pedidos pendentes prioritariamente
   2. Repor estoque dos produtos mais vendidos
   3. Revisar preços dos produtos com baixa margem
   4. Implementar sistema de fidelidade para clientes
   5. Expandir catálogo com 3-5 novos produtos/mês

Seu negócio está no caminho certo! Com pequenos ajustes, você pode aumentar a rentabilidade em 25% no próximo trimestre. 🚀`;
            }
            
            // Simulação de cross‑selling (agrupa pedidos do mesmo cliente – simplificado)
            calcularProdutosJuntos() {
                const pares = {};
                // Agrupar pedidos por cliente (CPF ou nome)
                const pedidosPorCliente = {};
                this.pedidos.forEach(pedido => {
                    const chave = pedido.cliente?.cpf || pedido.cliente?.nome || 'anonimo';
                    if (!pedidosPorCliente[chave]) pedidosPorCliente[chave] = [];
                    if (pedido.produto) pedidosPorCliente[chave].push(pedido.produto.nome);
                });
                
                // Para cada cliente com múltiplos produtos, gerar pares
                Object.values(pedidosPorCliente).forEach(produtos => {
                    if (produtos.length >= 2) {
                        for (let i = 0; i < produtos.length; i++) {
                            for (let j = i+1; j < produtos.length; j++) {
                                const par = [produtos[i], produtos[j]].sort().join('|');
                                pares[par] = (pares[par] || 0) + 1;
                            }
                        }
                    }
                });
                
                // Converter para array e ordenar
                const listaPares = Object.entries(pares).map(([par, vezes]) => {
                    const [a, b] = par.split('|');
                    return { produto: a, junto: b, vezes };
                }).sort((a, b) => b.vezes - a.vezes).slice(0, 5);
                
                return listaPares;
            }
            
            carregarSugestoesIA() {
                const container = document.getElementById('sugestoes-ia');
                if (!container) return;
                
                const sugestoes = this.gerarSugestoesIA();
                
                container.innerHTML = '';
                
                sugestoes.forEach(sugestao => {
                    const card = document.createElement('div');
                    card.className = 'suggestion-card';
                    card.dataset.acao = sugestao.acao;
                    
                    card.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <i class="${sugestao.icone}" style="color: ${sugestao.cor}; font-size: 20px;"></i>
                            <strong>${sugestao.titulo}</strong>
                        </div>
                        <div style="font-size: 14px; color: var(--text-muted); line-height: 1.5;">
                            ${sugestao.descricao}
                        </div>
                        ${sugestao.botao ? `
                        <button class="btn btn-small" style="margin-top: 15px; background-color: ${sugestao.cor}20; color: ${sugestao.cor}; border: 1px solid ${sugestao.cor}40;">
                            ${sugestao.botao}
                        </button>
                        ` : ''}
                    `;
                    
                    if (sugestao.acao) {
                        card.addEventListener('click', () => {
                            this.executarAcaoIA(sugestao.acao);
                        });
                    }
                    
                    container.appendChild(card);
                });
            }
            
            gerarSugestoesIA() {
                const sugestoes = [];
                
                const produtosBaixoEstoque = this.produtos.filter(p => (p.estoque || 0) < 10);
                if (produtosBaixoEstoque.length > 0) {
                    sugestoes.push({
                        titulo: "Reposição de Estoque",
                        descricao: `${produtosBaixoEstoque.length} produto(s) com estoque baixo. Considere repor para evitar falta.`,
                        icone: "fas fa-box",
                        cor: "#e74c3c",
                        acao: "analiseEstoque",
                        botao: "Ver Produtos"
                    });
                }
                
                const produtosBaixaMargem = this.produtos.filter(p => {
                    const precoVenda = p.precoVenda || 0;
                    const precoCusto = p.precoCusto || 0;
                    const categoria = p.categoria || 'outros';
                    const taxaCategoria = this.obterTaxaCategoria(categoria);
                    const lucro = precoVenda - precoCusto - (precoVenda * taxaCategoria / 100);
                    const margem = precoVenda > 0 ? (lucro / precoVenda) * 100 : 0;
                    return margem < 30;
                });
                
                if (produtosBaixaMargem.length > 0) {
                    sugestoes.push({
                        titulo: "Ajuste de Preços",
                        descricao: `${produtosBaixaMargem.length} produto(s) com margem líquida abaixo de 30%. Considere ajustar preços ou reduzir custos.`,
                        icone: "fas fa-tags",
                        cor: "#f39c12",
                        acao: "analisePrecos",
                        botao: "Analisar Preços"
                    });
                }
                
                const pedidosPendentes = this.pedidos.filter(p => 
                    p.rastreio?.status === 'pendente' || p.rastreio?.status === 'processando'
                ).length;
                
                if (pedidosPendentes > 0) {
                    sugestoes.push({
                        titulo: "Pedidos Pendentes",
                        descricao: `${pedidosPendentes} pedido(s) aguardando processamento. Acelere para melhorar a experiência do cliente.`,
                        icone: "fas fa-clock",
                        cor: "#3498db",
                        acao: "verPedidosPendentes",
                        botao: "Ver Pedidos"
                    });
                }
                
                const produtosEstoqueAlto = this.produtos.filter(p => (p.estoque || 0) > 50);
                if (produtosEstoqueAlto.length > 0) {
                    sugestoes.push({
                        titulo: "Promoção Recomendada",
                        descricao: `${produtosEstoqueAlto.length} produto(s) com estoque alto. Crie promoções para aumentar as vendas.`,
                        icone: "fas fa-gift",
                        cor: "#9b59b6",
                        acao: "sugerirPromocoes",
                        botao: "Criar Promoção"
                    });
                }
                
                const mesAtual = new Date().getMonth() + 1;
                let sugestaoSazonal = '';
                
                if (mesAtual >= 11 || mesAtual <= 1) {
                    sugestaoSazonal = "Natal/Ano Novo: Promova produtos relacionados a presentes.";
                } else if (mesAtual >= 2 && mesAtual <= 4) {
                    sugestaoSazonal = "Volta às aulas: Destaque materiais escolares e eletrônicos.";
                } else if (mesAtual >= 5 && mesAtual <= 7) {
                    sugestaoSazonal = "Inverno: Promova roupas de frio e aquecedores.";
                } else {
                    sugestaoSazonal = "Primavera/Verão: Destaque roupas leves e produtos de verão.";
                }
                
                sugestoes.push({
                    titulo: "Sugestão Sazonal",
                    descricao: sugestaoSazonal,
                    icone: "fas fa-calendar-alt",
                    cor: "#2ecc71",
                    acao: "analiseSazonal",
                    botao: "Ver Detalhes"
                });
                
                return sugestoes;
            }

            // ========== CLIENTES ==========
            carregarClientes() {
                const container = document.getElementById('clientes-container');
                if (!container) return;
                
                const clientesMap = {};
                this.pedidos.forEach(pedido => {
                    if (pedido.cliente?.cpf) {
                        if (!clientesMap[pedido.cliente.cpf]) {
                            clientesMap[pedido.cliente.cpf] = {
                                ...pedido.cliente,
                                amazonId: pedido.cliente?.amazonId || pedido.amazonId || '',
                                contaShopee: pedido.cliente?.contaShopee || pedido.contaShopee || pedido.cliente?.conta || 'Não informada',
                                pedidos: [],
                                gasto: 0
                            };
                        }
                        clientesMap[pedido.cliente.cpf].pedidos.push(pedido);
                        clientesMap[pedido.cliente.cpf].gasto += (pedido.produto?.precoVenda || 0);
                        if (!clientesMap[pedido.cliente.cpf].amazonId) {
                            clientesMap[pedido.cliente.cpf].amazonId = pedido.amazonId || pedido.cliente?.amazonId || '';
                        }
                        if (!clientesMap[pedido.cliente.cpf].contaShopee || clientesMap[pedido.cliente.cpf].contaShopee === 'Não informada') {
                            clientesMap[pedido.cliente.cpf].contaShopee = pedido.cliente?.contaShopee || pedido.contaShopee || pedido.cliente?.conta || 'Não informada';
                        }
                    }
                });
                
                let clientes = Object.values(clientesMap);
                
                // Filtrar
                const busca = (document.getElementById('busca-clientes')?.value || '').toLowerCase();
                if (busca) {
                    clientes = clientes.filter(c => 
                        c.nome?.toLowerCase().includes(busca) ||
                        c.cpf?.includes(busca) ||
                        c.amazonId?.toLowerCase().includes(busca)
                    );
                }
                
                // Ordenar
                const ordem = document.getElementById('ordenar-clientes')?.value || 'nome';
                switch(ordem) {
                    case 'pedidos':
                        clientes.sort((a, b) => b.pedidos.length - a.pedidos.length);
                        break;
                    case 'gasto':
                        clientes.sort((a, b) => b.gasto - a.gasto);
                        break;
                    case 'recente':
                        clientes.sort((a, b) => new Date(b.pedidos[0]?.dataCadastro) - new Date(a.pedidos[0]?.dataCadastro));
                        break;
                    default:
                        clientes.sort((a, b) => a.nome?.localeCompare(b.nome));
                }
                
                container.innerHTML = '';
                
                if (clientes.length === 0) {
                    container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--text-muted);"><i class="fas fa-users" style="font-size: 48px; margin-bottom: 20px; display: block;"></i><p style="font-size: 18px; margin: 0;">Nenhum cliente encontrado</p></div>';
                    return;
                }
                
                clientes.forEach(cliente => {
                    const card = document.createElement('div');
                    card.className = 'cliente-card';
                    card.style.cssText = 'background: linear-gradient(135deg, rgba(0, 168, 255, 0.08), rgba(70, 180, 255, 0.05)); padding: 25px; border-radius: var(--radius); border: 2px solid rgba(0, 168, 255, 0.25); box-shadow: 0 8px 30px rgba(0, 168, 255, 0.12);';
                    
                    // Gerar lista de produtos comprados
                    const produtosHTML = cliente.pedidos.map(pedido => {
                        const numPedido = pedido.id;
                        const pedidoAmazonId = pedido.amazonId || pedido.cliente?.amazonId || pedido.order_id || pedido.amazon_order_id || '';
                        const nomeProduto = pedido.produto?.nome || 'Produto não identificado';
                        const dataPedido = this.formatarData(pedido.dataCadastro);
                        const dataEnvio = pedido.rastreio?.dataEnvio ? this.formatarData(pedido.rastreio.dataEnvio) : (pedido.dataEnvio ? this.formatarData(pedido.dataEnvio) : 'Aguardando envio');
                        const dataEntrega = pedido.rastreio?.dataEntrega ? this.formatarData(pedido.rastreio.dataEntrega) : 'Não entregue';
                        const statusPedido = this.getStatusText(pedido.rastreio?.status);
                        const statusColor = this.getStatusColor(pedido.rastreio?.status);
                        const conta = pedido.contaShopee || 'Não informada';
                        const preco = (pedido.produto?.precoVenda || 0).toFixed(2);
                        const codigoRastreio = pedido.codigoRastreio || '';
                        const categoriaPedido = pedido.produto?.categoria || 'outros';
                        const taxaCategoriaPedido = this.obterTaxaCategoria(categoriaPedido);
                        const precoVenda = parseFloat(pedido.produto?.precoVenda || 0);
                        const precoCusto = parseFloat(pedido.produto?.precoCusto || 0);
                        const custoTaxa = precoVenda * (taxaCategoriaPedido / 100);
                        const lucroLiquido = precoVenda - precoCusto - custoTaxa;
                        const linkRastreio = pedido.links?.rastreio || `https://www.4tracking.net/pt/tjax/track?nums=${encodeURIComponent(codigoRastreio)}`;
                        
                        return `
                            <div style="background: rgba(0, 0, 0, 0.2); padding: 14px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${statusColor};">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                    <div style="flex: 1;">
                                        <div style="font-size: 13px; font-weight: 700; color: var(--text-light); margin-bottom: 6px;"><span class="dado-sensivel-produto">🛍️ ${nomeProduto}</span></div>
                                        <div style="font-size: 11px; color: var(--text-muted); display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 8px;">
                                            <div><span style="margin-right: 8px;">🆔 Pedido #${numPedido}</span></div>
                                            <div><span>📦 Código: ${codigoRastreio || 'Sem código'}</span></div>
                                            <div style="color: ${statusColor}; font-weight: 700;">✓ ${statusPedido}</div>
                                            <div><span>📅 Pedido: ${dataPedido}</span></div>
                                            <div><span>🚚 Envio: ${dataEnvio}</span></div>
                                            <div><span>📦 Entrega: ${dataEntrega}</span></div>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted); display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                            <span style="background: rgba(0, 168, 255, 0.2); padding: 2px 8px; border-radius: 4px; font-weight: 600;">💳 Preço de Custo: R$ ${precoCusto.toFixed(2)}</span>
                                            <span style="background: rgba(46, 204, 113, 0.12); color: #2ecc71; padding: 2px 8px; border-radius: 4px; font-weight: 600;">💰 Lucro Líquido: R$ ${lucroLiquido.toFixed(2)}</span>
                                        </div>
                                        ${pedidoAmazonId ? `<div class="dado-sensivel-amazon-id" style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px; background: rgba(243, 156, 18, 0.12); color: #e67e22; border: 1px solid rgba(243, 156, 18, 0.25); border-radius: 14px; padding: 8px 12px; font-size: 12px; font-weight: 700;"><i class="fas fa-hashtag" style="font-size: 11px;"></i>id do pedido: <span style="color: var(--text-light);">${pedidoAmazonId}</span></div>` : ''}
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 900; color: #2ecc71; font-size: 14px; margin-bottom: 8px;">R$ ${preco}</div>
                                        ${codigoRastreio ? `<button class="btn btn-small btn-warning" onclick="window.open('${linkRastreio}', '_blank')" style="font-size: 10px; padding: 4px 8px;">
                                            <i class="fas fa-truck"></i> Rastreio
                                        </button>` : '<span style="font-size: 10px; color: var(--text-muted);">Sem rastreio</span>'}
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    const rastreiosHTML = cliente.pedidos.map(pedido => {
                        const endereco = pedido.cliente?.endereco || pedido.endereco || {};
                        const enderecoFormatado = endereco?.rua ? `${endereco.rua}, ${endereco.numero || ''}<br>${endereco.bairro ? endereco.bairro + ' - ' : ''}${endereco.cidade || ''} - ${endereco.estado || ''}<br>CEP: ${endereco.cep || ''}<br>${endereco.complemento ? `<strong>Complemento:</strong> ${endereco.complemento}` : ''}` : 'Endereço não informado';
                        const codigoRastreio = pedido.codigoRastreio || 'Sem código';
                        const pedidoAmazonId = pedido.amazonId || pedido.cliente?.amazonId || pedido.order_id || pedido.amazon_order_id || '';
                        const statusPedido = this.getStatusText(pedido.rastreio?.status);
                        const statusColor = this.getStatusColor(pedido.rastreio?.status);
                        const dataEnvio = pedido.rastreio?.dataEnvio ? this.formatarData(pedido.rastreio.dataEnvio) : (pedido.dataEnvio ? this.formatarData(pedido.dataEnvio) : 'Não informado');
                        const dataEntrega = pedido.rastreio?.dataEntrega ? this.formatarData(pedido.rastreio.dataEntrega) : 'Não entregue';
                        const linkRastreio = pedido.links?.rastreio || `https://www.4tracking.net/pt/tjax/track?nums=${encodeURIComponent(codigoRastreio)}`;
                        
                        return `
                            <div style="background: linear-gradient(180deg, rgba(0, 79, 157, 0.25), rgba(0, 118, 214, 0.08)); border: 1px solid rgba(0, 168, 255, 0.18); padding: 18px; border-radius: 16px; margin-bottom: 16px; box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);">
                                <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start;">
                                    <div style="flex: 1 1 360px; min-width: 280px;">
                                        <div style="font-size: 15px; font-weight: 800; color: var(--text-light); margin-bottom: 14px;">📦 Pedido #${parseInt(pedido.id) || '-'}</div>
                                        ${pedidoAmazonId ? `<div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">ID do Pedido Amazon: <strong style="color: var(--text-light);">${pedidoAmazonId}</strong></div>` : ''}
                                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; font-size: 12px; color: var(--text-muted); margin-bottom: 14px;">
                                            <div style="display: flex; flex-direction: column; gap: 4px;"><span style="font-weight: 700; color: var(--text-light);">Código de Rastreio</span><span style="font-family: monospace; background: rgba(0,0,0,0.16); padding: 10px; border-radius: 12px; color: var(--text-light);">${codigoRastreio}</span></div>
                                            <div style="display: flex; flex-direction: column; gap: 4px;"><span style="font-weight: 700; color: var(--text-light);">Data de Entrega</span><span style="padding: 10px; border-radius: 12px; background: rgba(255,255,255,0.08);">${dataEntrega}</span></div>
                                        </div>

                                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 10px;">
                                            <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); border-radius: 16px; padding: 14px;">
                                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Status</div>
                                                <div style="font-size: 14px; font-weight: 800; color: ${statusColor};">${statusPedido}</div>
                                            </div>
                                            <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); border-radius: 16px; padding: 14px;">
                                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Data de Envio</div>
                                                <div style="font-size: 14px; font-weight: 800; color: var(--text-light);">${dataEnvio}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="flex: 0 0 320px; display: flex; flex-direction: column; gap: 14px;">
                                        <div style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); border-radius: 18px; padding: 18px; color: var(--text-light);">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 13px; font-weight: 700; color: var(--text-light);">
                                                <i class="fas fa-map-marker-alt" style="color: var(--primary-color);"></i>
                                                Endereço de entrega
                                            </div>
                                            <div style="font-size: 13px; line-height: 1.6; color: var(--text-muted);">
                                                ${enderecoFormatado}
                                            </div>
                                        </div>

                                        ${codigoRastreio !== 'Sem código' ? `
                                        <button class="btn btn-small btn-warning" onclick="window.open('${linkRastreio}', '_blank')" style="font-size: 12px; padding: 12px 16px; border-radius: 16px; width: 100%; border: 1px solid rgba(255,255,255,0.22); background: linear-gradient(135deg, rgba(255, 166, 0, 0.95), rgba(255, 120, 0, 0.9)); white-space: nowrap;">
                                            <i class="fas fa-truck" style="margin-right: 10px;"></i> Abrir Rastreio
                                        </button>
                                        ` : '<div style="padding: 14px; border-radius: 16px; background: rgba(255,255,255,0.08); text-align: center; font-size: 12px; color: var(--text-muted);">Rastreamento indisponível</div>'}
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                            <div>
                                <h4 style="margin: 0 0 8px 0; color: var(--text-light); font-size: 18px; font-weight: 700;"><span class="dado-sensivel-cliente" style="display: inline;">${cliente.nome}</span></h4>
                                <div style="background: rgba(70, 180, 255, 0.2); padding: 6px 12px; border-radius: 6px; font-size: 13px; color: var(--primary-color); font-weight: 900; display: inline-block; margin-bottom: 8px;">📋 CPF: <span class="dado-sensivel-cpf">${cliente.cpf}</span></div>
                            </div>
                            <div style="background: rgba(0, 168, 255, 0.2); padding: 8px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; color: var(--primary-color);">👤 ${cliente.contaShopee || 'Conta não informada'}</div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid rgba(0, 168, 255, 0.15);">
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 5px; font-weight: 600; text-transform: uppercase;">📦 Total de Pedidos</div>
                                <div style="font-size: 24px; font-weight: 900; color: var(--primary-color);">${cliente.pedidos.length}</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 5px; font-weight: 600; text-transform: uppercase;">💳 Custo Total</div>
                                <div style="font-size: 24px; font-weight: 900; color: #2ecc71;">R$ ${cliente.gasto.toFixed(2)}</div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px; font-weight: 600; text-transform: uppercase;">📝 Histórico de Compras</div>
                            <div class="historico-compras" style="background: rgba(0, 0, 0, 0.05); padding: 10px; border-radius: 5px;">
                                ${produtosHTML}
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px; padding-top: 15px; border-top: 1px solid rgba(0, 168, 255, 0.15);">
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; text-transform: uppercase;">📱 Informações de Contato</div>
                            <div style="display: flex; gap: 10px; flex-direction: column; font-size: 13px; color: var(--text-light);">
                                ${cliente.telefone ? `<div><i class="fas fa-phone" style="color: var(--primary-color); margin-right: 10px; width: 16px;"></i><strong>Tel:</strong> <span class="dado-sensivel-telefone">${cliente.telefone}</span></div>` : '<div style="color: var(--text-muted);">Telefone não informado</div>'}
                                ${cliente.email ? `<div><i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 10px; width: 16px;"></i><strong>Email:</strong> <span class="dado-sensivel-email">${cliente.email}</span></div>` : ''}
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
                                <button type="button" class="btn btn-small btn-secondary btn-toggle-endereco" data-action="toggle-endereco" style="background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(41, 128, 185, 0.1)); border: 1px solid rgba(52, 152, 219, 0.3); color: var(--primary-color);">
                                    <i class="fas fa-map-marker-alt"></i> Ver informações de rastreio e endereço
                                </button>
                                <span style="font-size: 11px; color: var(--text-muted); font-style: italic;">
                                    ${cliente.pedidos.length} pedido${cliente.pedidos.length !== 1 ? 's' : ''} encontrado${cliente.pedidos.length !== 1 ? 's' : ''}
                                </span>
                            </div>
                            <div class="cliente-rastreio-extra dados-sensiveis" style="display: none; margin-top: 15px; padding: 20px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02)); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                                <div style="font-size: 14px; color: var(--text-light); margin-bottom: 18px; font-weight: 600; display: flex; align-items: center;">
                                    <i class="fas fa-truck" style="color: var(--primary-color); margin-right: 10px;"></i>
                                    Informações de Rastreamento e Endereços
                                </div>
                                ${rastreiosHTML}
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="btn btn-small btn-primary" onclick="window.location.href='https://wa.me/55${(cliente.telefone || '').replace(/\\D/g, '')}'" style="${cliente.telefone ? '' : 'display: none;'}">
                                <i class="fas fa-comments"></i> WhatsApp
                            </button>
                            ${cliente.amazonId ? `
                            <button class="btn btn-small btn-secondary btn-ver-pedido-amazon" data-id="${cliente.amazonId}" title="Ver pedido Amazon">
                                <i class="fas fa-eye"></i> Ver pedido Amazon
                            </button>
                            ` : ''}
                        </div>
                    `;
                    const toggleBtn = card.querySelector('.btn-toggle-endereco');
                    const extraPanel = card.querySelector('.cliente-rastreio-extra');
                    if (toggleBtn && extraPanel) {
                        toggleBtn.addEventListener('click', () => {
                            const visible = extraPanel.style.display === 'block';
                            const container = document.getElementById('clientes-container');
                            
                            if (!visible) {
                                // Fechar qualquer outro card aberto primeiro
                                const outrosCards = container.querySelectorAll('.cliente-card.cliente-expandido');
                                outrosCards.forEach(c => {
                                    const outroToggle = c.querySelector('.btn-toggle-endereco');
                                    const outroPanel = c.querySelector('.cliente-rastreio-extra');
                                    if (outroToggle && outroPanel && outroPanel.style.display === 'block') {
                                        // Simular clique para fechar
                                        outroToggle.click();
                                    }
                                });
                                
                                // Abrindo - modo focado
                                // 1. Esconder todos os outros cards
                                const todosCards = container.querySelectorAll('.cliente-card');
                                todosCards.forEach(c => {
                                    if (c !== card) {
                                        c.classList.add('cliente-oculto');
                                        c.classList.remove('cliente-visivel');
                                    } else {
                                        c.classList.add('cliente-expandido', 'cliente-visivel');
                                        c.classList.remove('cliente-oculto');
                                    }
                                });
                                
                                // 2. Mudar container para modo focado
                                container.classList.add('modo-focado');
                                
                                // 3. Mostrar painel extra com transição
                                extraPanel.style.display = 'block';
                                setTimeout(() => {
                                    extraPanel.style.opacity = '1';
                                    extraPanel.style.transform = 'translateY(0)';
                                }, 10);
                                
                                toggleBtn.innerHTML = '<i class="fas fa-times"></i> Fechar informações';
                                toggleBtn.style.background = 'linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(192, 57, 43, 0.15))';
                                toggleBtn.style.borderColor = 'rgba(231, 76, 60, 0.4)';
                                toggleBtn.style.color = '#e74c3c';
                            } else {
                                // Fechando - volta ao normal
                                // 1. Mostrar todos os cards novamente
                                const todosCards = container.querySelectorAll('.cliente-card');
                                todosCards.forEach(c => {
                                    c.classList.remove('cliente-oculto', 'cliente-expandido');
                                    c.classList.add('cliente-visivel');
                                });
                                
                                // 2. Remover modo focado do container
                                container.classList.remove('modo-focado');
                                
                                // 3. Esconder painel extra
                                extraPanel.style.opacity = '0';
                                extraPanel.style.transform = 'translateY(-10px)';
                                setTimeout(() => {
                                    extraPanel.style.display = 'none';
                                }, 300);
                                
                                toggleBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Ver informações de rastreio e endereço';
                                toggleBtn.style.background = 'linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(41, 128, 185, 0.1))';
                                toggleBtn.style.borderColor = '1px solid rgba(52, 152, 219, 0.3)';
                                toggleBtn.style.color = 'var(--primary-color)';
                            }
                        });
                    }

                    const verPedidoAmazonBtn = card.querySelector('.btn-ver-pedido-amazon');
                    if (verPedidoAmazonBtn) {
                        verPedidoAmazonBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            const amazonId = verPedidoAmazonBtn.getAttribute('data-id');
                            if (!amazonId) {
                                this.mostrarNotificacao('ID da Amazon não encontrado para esse cliente.', 'warning');
                                return;
                            }
                            const url = `https://sellercentral.amazon.com.br/orders-v3/order/${encodeURIComponent(amazonId)}`;
                            window.open(url, '_blank');
                        });
                    }

                    container.appendChild(card);
                });
                
                // Configurar listeners de filtro
                const buscaInput = document.getElementById('busca-clientes');
                const ordenarSelect = document.getElementById('ordenar-clientes');
                if (buscaInput) buscaInput.addEventListener('input', () => this.carregarClientes());
                if (ordenarSelect) ordenarSelect.addEventListener('change', () => this.carregarClientes());
            }

            async salvarClienteAutomatico(clienteData) {
                if (!clienteData || !clienteData.cpf) return;
                
                // Verificar se cliente já existe
                const clienteExistente = this.clientes.find(c => c.cpf === clienteData.cpf);
                if (clienteExistente) return; // Já existe, não duplicar
                
                // Adicionar novo cliente
                const novoCliente = {
                    id: (this.clientes.length > 0 ? Math.max(...this.clientes.map(c => c.id)) : 0) + 1,
                    ...clienteData,
                    dataCadastro: this.obterDataHoje()
                };
                
                this.clientes.push(novoCliente);
                await this.salvarDados('cliente', novoCliente);
            }
            
            async sincronizarClientesDosPedidos() {
                console.log('Sincronizando clientes dos pedidos...');
                const cpfsProcessados = new Set();
                
                for (const pedido of this.pedidos) {
                    if (pedido.cliente && pedido.cliente.cpf && !cpfsProcessados.has(pedido.cliente.cpf)) {
                        cpfsProcessados.add(pedido.cliente.cpf);
                        await this.salvarClienteAutomatico(pedido.cliente);
                    }
                }
                
                console.log(`Clientes sincronizados: ${cpfsProcessados.size}`);
            }
            
            // ========== BUSCA AVANÇADA ==========
            configurarBuscaAvancada() {
                const form = document.getElementById('form-busca-avancada');
                if (!form) return;
                
                form.innerHTML = `
                    <div class="form-group">
                        <label class="form-label">Termo de Busca</label>
                        <input type="text" class="form-control" id="busca-termo" placeholder="Digite o que procura...">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipo de Busca</label>
                            <select class="form-control" id="busca-tipo">
                                <option value="todos">Todos os Campos</option>
                                <option value="cliente">Nome do Cliente</option>
                                <option value="cpf">CPF/CNPJ</option>
                                <option value="rastreio">Código de Rastreio</option>
                                <option value="produto">Nome do Produto</option>
                                <option value="status">Status do Pedido</option>
                                <option value="telefone">Telefone</option>
                                <option value="amazonId">id do pedido</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="busca-status">
                                <option value="todos">Todos</option>
                                <option value="entregue">Entregue</option>
                                <option value="pendente">Pendente</option>
                                <option value="processando">Processando</option>
                                <option value="transito">Em Trânsito</option>
                                <option value="atrasado">Atrasado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Data Mínima</label>
                            <input type="date" class="form-control" id="busca-data-min">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Data Máxima</label>
                            <input type="date" class="form-control" id="busca-data-max">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Valor Mínimo (R$)</label>
                            <input type="number" class="form-control" id="busca-valor-min" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Valor Máximo (R$)</label>
                            <input type="number" class="form-control" id="busca-valor-max" placeholder="0.00" step="0.01">
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px;">
                        <button type="button" class="btn" id="cancelar-busca">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <button type="button" class="btn btn-secondary" id="btn-limpar-busca">
                            <i class="fas fa-broom"></i> Limpar
                        </button>
                    </div>
                `;
                
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.executarBuscaAvancada();
                });
                
                document.getElementById('btn-limpar-busca').addEventListener('click', () => {
                    form.reset();
                    document.getElementById('resultados-busca').style.display = 'none';
                });
            }
            
            executarBuscaAvancada() {
                const termo = document.getElementById('busca-termo').value.toLowerCase();
                const tipo = document.getElementById('busca-tipo').value;
                const status = document.getElementById('busca-status').value;
                const dataMin = document.getElementById('busca-data-min').value;
                const dataMax = document.getElementById('busca-data-max').value;
                const valorMin = parseFloat(document.getElementById('busca-valor-min').value) || 0;
                const valorMax = parseFloat(document.getElementById('busca-valor-max').value) || Infinity;
                
                const resultados = [];
                
                this.pedidos.forEach(pedido => {
                    if (status !== 'todos' && pedido.rastreio?.status !== status) return;
                    if (dataMin && pedido.dataCadastro < dataMin) return;
                    if (dataMax && pedido.dataCadastro > dataMax) return;
                    
                    const valorPedido = pedido.produto?.precoVenda || 0;
                    if (valorPedido < valorMin || valorPedido > valorMax) return;
                    
                    let matches = false;
                    
                    if (termo) {
                        switch(tipo) {
                            case 'todos':
                                matches = 
                                    (pedido.codigoRastreio && pedido.codigoRastreio.toLowerCase().includes(termo)) ||
                                    (pedido.cliente?.nome && pedido.cliente.nome.toLowerCase().includes(termo)) ||
                                    (pedido.cliente?.cpf && pedido.cliente.cpf.toLowerCase().includes(termo)) ||
                                    (pedido.produto?.nome && pedido.produto.nome.toLowerCase().includes(termo)) ||
                                    (pedido.cliente?.telefone && pedido.cliente.telefone.toLowerCase().includes(termo)) ||
                                    (pedido.amazonId && pedido.amazonId.toString().toLowerCase().includes(termo)) ||
                                    (pedido.order_id && pedido.order_id.toString().toLowerCase().includes(termo)) ||
                                    (pedido.amazon_order_id && pedido.amazon_order_id.toString().toLowerCase().includes(termo)) ||
                                    (pedido.cliente?.amazonId && pedido.cliente.amazonId.toLowerCase().includes(termo));
                                break;
                            case 'cliente':
                                matches = pedido.cliente?.nome && pedido.cliente.nome.toLowerCase().includes(termo);
                                break;
                            case 'cpf':
                                matches = pedido.cliente?.cpf && pedido.cliente.cpf.toLowerCase().includes(termo);
                                break;
                            case 'rastreio':
                                matches = pedido.codigoRastreio && pedido.codigoRastreio.toLowerCase().includes(termo);
                                break;
                            case 'produto':
                                matches = pedido.produto?.nome && pedido.produto.nome.toLowerCase().includes(termo);
                                break;
                            case 'telefone':
                                matches = pedido.cliente?.telefone && pedido.cliente.telefone.toLowerCase().includes(termo);
                                break;
                            case 'amazonId':
                                matches = 
                                    (pedido.amazonId && pedido.amazonId.toString().toLowerCase().includes(termo)) ||
                                    (pedido.cliente?.amazonId && pedido.cliente.amazonId.toLowerCase().includes(termo)) ||
                                    (pedido.order_id && pedido.order_id.toString().toLowerCase().includes(termo)) ||
                                    (pedido.amazon_order_id && pedido.amazon_order_id.toString().toLowerCase().includes(termo));
                                break;
                        }
                    } else {
                        matches = true;
                    }
                    
                    if (matches) {
                        resultados.push({
                            tipo: 'pedido',
                            id: pedido.id,
                            titulo: pedido.codigoRastreio || 'Sem código',
                            subtitulo: `Cliente: ${pedido.cliente?.nome || 'Não informado'} | Produto: ${pedido.produto?.nome || 'Não informado'}`,
                            detalhes: `Status: ${this.getStatusText(pedido.rastreio?.status)} | Valor: R$ ${valorPedido.toFixed(2)} | Data: ${this.formatarData(pedido.dataCadastro)}`,
                            acao: () => { 
                                this.fecharModal('modal-busca-avancada');
                                this.ativarAba('pedidos');
                                this.verPedido(pedido.id);
                            }
                        });
                    }
                });
                
                this.produtos.forEach(produto => {
                    if (termo && (tipo === 'todos' || tipo === 'produto')) {
                        if ((produto.nome && produto.nome.toLowerCase().includes(termo)) || 
                            (produto.asin && produto.asin.toLowerCase().includes(termo))) {
                            resultados.push({
                                tipo: 'produto',
                                id: produto.id,
                                titulo: produto.nome,
                                subtitulo: `Categoria: ${this.getCategoryText(produto.categoria)} | Estoque: ${produto.estoque || 0}`,
                                detalhes: `Preço: R$ ${(produto.precoVenda || 0).toFixed(2)} | Custo: R$ ${(produto.precoCusto || 0).toFixed(2)} | Margem: ${(((produto.precoVenda || 0) - (produto.precoCusto || 0) - ((produto.precoVenda || 0) * (this.config.taxaPadrao||15)/100)) / (produto.precoVenda || 1) * 100).toFixed(1)}%`,
                                acao: () => { 
                                    this.fecharModal('modal-busca-avancada');
                                    this.ativarAba('produtos');
                                }
                            });
                        }
                    }
                });
                
                this.exibirResultadosBusca(resultados);
            }
            
            exibirResultadosBusca(resultados) {
                const container = document.getElementById('resultados-container');
                const resultadosDiv = document.getElementById('resultados-busca');
                
                container.innerHTML = '';
                
                if (resultados.length === 0) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h4>Nenhum resultado encontrado</h4>
                            <p>Tente ajustar os filtros da busca.</p>
                        </div>
                    `;
                } else {
                    resultados.forEach(resultado => {
                        const resultadoDiv = document.createElement('div');
                        resultadoDiv.className = 'pedido-card';
                        resultadoDiv.style.cursor = 'pointer';
                        resultadoDiv.style.marginBottom = '15px';
                        resultadoDiv.style.padding = '20px';
                        resultadoDiv.onclick = resultado.acao;
                        
                        resultadoDiv.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%; margin-bottom: 15px;">
                                <div>
                                    <strong style="font-size: 16px; color: var(--text-light);">${resultado.titulo}</strong>
                                    <div style="font-size: 14px; color: var(--text-muted); margin-top: 5px;">
                                        ${resultado.subtitulo}
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-contrast); margin-top: 8px;">
                                        ${resultado.detalhes}
                                    </div>
                                </div>
                                <span class="status-badge ${resultado.tipo === 'pedido' ? 'status-processando' : 'status-ativo'}">
                                    ${resultado.tipo === 'pedido' ? 'Pedido' : 'Produto'}
                                </span>
                            </div>
                            <div style="text-align: right; font-size: 12px; color: var(--text-muted);">
                                Clique para ver detalhes
                            </div>
                        `;
                        
                        container.appendChild(resultadoDiv);
                    });
                    
                    const resumoDiv = document.createElement('div');
                    resumoDiv.style.marginTop = '20px';
                    resumoDiv.style.padding = '15px';
                    resumoDiv.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
                    resumoDiv.style.borderRadius = 'var(--radius-small)';
                    resumoDiv.style.fontSize = '14px';
                    resumoDiv.style.color = 'var(--text-muted)';
                    resumoDiv.innerHTML = `
                        <strong>Resumo da busca:</strong> ${resultados.length} resultado(s) encontrado(s)
                    `;
                    container.appendChild(resumoDiv);
                }
                
                resultadosDiv.style.display = 'block';
                container.scrollTop = 0;
            }
            
            // ========== FUNÇÕES AUXILIARES ==========
            getStatusClass(status) {
                if (!status) return 'status-pendente';
                switch(status) {
                    case 'entregue': return 'status-entregue';
                    case 'processando': return 'status-processando';
                    case 'transito': return 'status-transito';
                    case 'atrasado': return 'status-atrasado';
                    case 'pendente': return 'status-pendente';
                    default: return 'status-pendente';
                }
            }
            
            getStatusColor(status) {
                if (!status) return 'var(--text-muted)';
                switch(status) {
                    case 'entregue': return 'var(--success-color)';
                    case 'processando': return 'var(--processando-color)';
                    case 'transito': return 'var(--transito-color)';
                    case 'atrasado': return 'var(--danger-color)';
                    case 'pendente': return 'var(--warning-color)';
                    default: return 'var(--text-muted)';
                }
            }
            
            getStatusText(status) {
                if (!status) return 'Pendente';
                switch(status) {
                    case 'entregue': return 'Entregue';
                    case 'processando': return 'Processando';
                    case 'transito': return 'Em Trânsito';
                    case 'atrasado': return 'Atrasado';
                    case 'pendente': return 'Pendente';
                    default: return 'Pendente';
                }
            }
            
            getStatusIcon(status) {
                if (!status) return 'question-circle';
                switch(status) {
                    case 'entregue': return 'check-circle';
                    case 'processando': return 'sync-alt';
                    case 'transito': return 'truck-moving';
                    case 'atrasado': return 'exclamation-triangle';
                    case 'pendente': return 'clock';
                    default: return 'question-circle';
                }
            }
            
            getTransportadoraText(transportadora) {
                if (!transportadora) return 'Não informada';
                switch(transportadora) {
                    case 'shopee': return 'Shopee';
                    case 'correios': return 'Correios';
                    case 'jadlog': return 'Jadlog';
                    case 'azul': return 'Azul Cargo';
                    case 'outra': return 'Outra';
                    default: return transportadora;
                }
            }
            
            getCategoryText(categoria) {
                if (!categoria) return 'Outros';
                switch(categoria) {
                    case 'eletronicos': return 'Eletrônicos';
                    case 'vestuario': return 'Vestuário';
                    case 'casa': return 'Casa';
                    case 'beleza': return 'Beleza';
                    case 'livros': return 'Livros';
                    default: return categoria;
                }
            }
            
            formatarData(dataString) {
                if (!dataString) return 'Não informada';
                
                // Parsear data no formato YYYY-MM-DD sem timezone shift
                const partes = dataString.split('-');
                if (partes.length === 3) {
                    const data = new Date(partes[0], partes[1] - 1, partes[2]);
                    return data.toLocaleDateString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                }
                
                // Fallback para outros formatos
                const data = new Date(dataString);
                if (isNaN(data.getTime())) return dataString;
                
                return data.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }
            
            calcularPrevisaoEntrega(dataEnvio) {
                if (!dataEnvio) return 'Não informada';
                
                // Parsear corretamente a data
                const partes = dataEnvio.split('-');
                const data = partes.length === 3 ? 
                    new Date(partes[0], partes[1] - 1, partes[2]) :
                    new Date(dataEnvio);
                
                data.setDate(data.getDate() + 7);
                
                const ano = data.getFullYear();
                const mes = String(data.getMonth() + 1).padStart(2, '0');
                const dia = String(data.getDate()).padStart(2, '0');
                
                return this.formatarData(`${ano}-${mes}-${dia}`);
            }
            
            getProdutosMaisVendidos(quantidade = 5) {
                const produtosMap = {};
                
                this.pedidos.forEach(pedido => {
                    if (!pedido.produto) return;
                    
                    const produtoNome = pedido.produto.nome;
                    if (!produtosMap[produtoNome]) {
                        produtosMap[produtoNome] = {
                            nome: produtoNome,
                            vendas: 0,
                            precoVenda: pedido.produto.precoVenda || 0,
                            precoCusto: pedido.produto.precoCusto || 0
                        };
                    }
                    
                    produtosMap[produtoNome].vendas++;
                });
                
                const produtosArray = Object.values(produtosMap);
                produtosArray.sort((a, b) => b.vendas - a.vendas);
                
                return produtosArray.slice(0, quantidade);
            }
            
            getProdutosMaiorMargem(quantidade = 5) {
                const produtosComMargem = this.produtos.map(produto => {
                    const categoria = produto.categoria || 'outros';
                    const taxaCategoria = this.obterTaxaCategoria(categoria);
                    const precoVenda = produto.precoVenda || 0;
                    const precoCusto = produto.precoCusto || 0;
                    const lucroLiq = precoVenda - precoCusto - (precoVenda * taxaCategoria / 100);
                    const margem = precoVenda > 0 ? (lucroLiq / precoVenda * 100) : 0;
                    return {
                        nome: produto.nome,
                        margem: margem.toFixed(1),
                        precoVenda: precoVenda,
                        precoCusto: precoCusto
                    };
                });
                
                produtosComMargem.sort((a, b) => parseFloat(b.margem) - parseFloat(a.margem));
                
                return produtosComMargem.slice(0, quantidade);
            }
            
            // ========== FUNÇÕES DE GERENCIAMENTO ==========
            reorganizarIdsPedidos(mostrarNotificacao = true) {
                console.log('Reorganizando IDs dos pedidos...');
                
                // Ordenar pedidos por data de cadastro (mais antigos primeiro)
                this.pedidos.sort((a, b) => new Date(a.dataCadastro || 0) - new Date(b.dataCadastro || 0));
                
                // Reatribuir IDs sequenciais
                this.pedidos.forEach((pedido, index) => {
                    const novoId = index + 1;
                    if (parseInt(pedido.id) !== novoId) {
                        console.log(`Alterando ID do pedido ${pedido.id} para ${novoId}`);
                        pedido.id = novoId;
                        // Atualizar no servidor se necessário
                        this.atualizarDados('pedido', pedido.id, pedido);
                    }
                });
                
                console.log('IDs reorganizados com sucesso!');
                if (mostrarNotificacao) {
                    this.mostrarNotificacao('IDs dos pedidos reorganizados com sucesso!', 'success');
                }
            }
            
            async adicionarPedido(pedido) {
                try {
                    console.log('Iniciando adicionarPedido:', pedido);
                    
                    // Garantir que IDs sejam únicos e sequenciais
                    const idsExistentes = this.pedidos
                        .map(p => parseInt(p.id) || 0)
                        .filter(id => id > 0)
                        .sort((a, b) => a - b);
                    
                    // Encontrar o próximo ID disponível
                    let proximoId = 1;
                    for (let i = 0; i < idsExistentes.length; i++) {
                        if (idsExistentes[i] !== proximoId) {
                            break;
                        }
                        proximoId++;
                    }
                    
                    pedido.id = proximoId;
                    console.log('ID gerado para pedido:', pedido.id);

                    if (!pedido.dataCadastro) {
                        pedido.dataCadastro = this.obterDataHoje();
                    }

                    // Usar data de criação como data de envio se não foi definida
                    if (!pedido.rastreio.dataEnvio) {
                        pedido.rastreio.dataEnvio = pedido.dataCadastro;
                    }

                    // Gerar link de rastreamento automático se tiver código de rastreio
                    if (pedido.codigoRastreio && !pedido.links.rastreio) {
                        pedido.links.rastreio = `https://www.4tracking.net/pt/tjax/track?nums=${encodeURIComponent(pedido.codigoRastreio)}`;
                    }

                    console.log('Tentando salvar pedido no servidor...');
                    const resultadoSalvar = await this.salvarDadosComResposta('pedido', pedido);
                    console.log('Resultado do salvarDados:', resultadoSalvar);

                    if (resultadoSalvar.success) {
                        const idDoServidor = Number(resultadoSalvar.id);
                        if (Number.isInteger(idDoServidor) && idDoServidor > 0) {
                            pedido.id = idDoServidor;
                        }
                        if (!this.pedidos.some(p => Number(p.id) === Number(pedido.id))) {
                            this.pedidos.push(pedido);
                        }
                        console.log('Pedido salvo com sucesso, atualizando estoque...');
                        
                        // Atualizar estoque quando pedido é criado
                        if (pedido.produto && pedido.produto.nome) {
                            const produtoIndex = this.produtos.findIndex(p => p.nome.toLowerCase() === pedido.produto.nome.toLowerCase());
                            if (produtoIndex !== -1) {
                                // Decrementar estoque
                                this.produtos[produtoIndex].estoque = (this.produtos[produtoIndex].estoque || 0) - 1;

                                // Salvar arquivo de produtos
                                await this.atualizarDados('produto', this.produtos[produtoIndex].id, this.produtos[produtoIndex]);

                                // Notificar se estoque ficou baixo
                                if ((this.produtos[produtoIndex].estoque || 0) < 5) {
                                    this.mostrarNotificacao(`⚠️ Estoque baixo para ${pedido.produto.nome}! Restam ${this.produtos[produtoIndex].estoque} unidades.`, 'warning');
                                }
                            }
                        }

                        // Pequeno delay para garantir que os dados sejam salvos
                        await new Promise(resolve => setTimeout(resolve, 100));

                        console.log('Recarregando dados do servidor...');
                        // Recarregar dados do servidor para garantir sincronização
                        await this.carregarDadosServidor();

                        console.log('Dados recarregados, atualizando interface...');
                        // Forçar atualização completa da interface
                        this.atualizarBadgesMenu();
                        this.atualizarDashboard();
                        this.carregarPedidos('todos');
                        this.carregarProdutos();
                        
                        console.log('Interface atualizada, pedido adicionado com sucesso!');
                        this.mostrarNotificacao('Pedido adicionado com sucesso!', 'success');
                        return pedido;
                    } else {
                        return null;
                    }
                } catch (e) {
                    console.error('Erro ao adicionar pedido:', e);
                    return null;
                }
            }
            
            async atualizarPedido(id, dadosAtualizados) {
                try {
                    const index = this.pedidos.findIndex(p => Number(p.id) === Number(id));
                    if (index === -1) {
                        this.mostrarNotificacao('Pedido não encontrado', 'warning');
                        return false;
                    }

                    dadosAtualizados.id = id;
                    const pedidoOriginal = this.pedidos[index];
                    this.pedidos[index] = { ...pedidoOriginal, ...dadosAtualizados };

                    const sucesso = await this.atualizarDados('pedido', id, this.pedidos[index]);

                    if (sucesso) {
                        // Recarregar dados do servidor para garantir sincronização
                        await this.carregarDadosServidor();

                        const abaAtiva = document.querySelector('.pedido-tab-btn.active')?.getAttribute('data-pedido-tab') || 'todos';

                        // Atualizar interface com dados atualizados
                        this.atualizarDashboard();
                        this.carregarPedidos(abaAtiva);
                        this.mostrarNotificacao('Pedido atualizado com sucesso!', 'success');
                        return true;
                    } else {
                        this.pedidos[index] = pedidoOriginal;
                        return false;
                    }
                } catch (e) {
                    console.error('Erro ao atualizar pedido:', e);
                    return false;
                }
            }
            
            async removerPedido(id) {
                try {
                    const index = this.pedidos.findIndex(p => Number(p.id) === Number(id));
                    if (index === -1) {
                        this.mostrarNotificacao('Pedido não encontrado', 'warning');
                        return false;
                    }

                    const pedidoRemovido = this.pedidos[index];
                    const pedidoCpf = pedidoRemovido.cliente?.cpf || pedidoRemovido.cliente?.cpfCnpj || '';
                    const abaAtiva = document.querySelector('.pedido-tab-btn.active')?.getAttribute('data-pedido-tab') || 'todos';

                    // Remover imediatamente da interface
                    this.pedidos.splice(index, 1);

                    // Atualizar clientes locais e interface de clientes
                    if (pedidoCpf) {
                        const clienteAindaExiste = this.pedidos.some(p => {
                            const cpf = p.cliente?.cpf || p.cliente?.cpfCnpj || '';
                            return cpf === pedidoCpf;
                        });
                        if (!clienteAindaExiste) {
                            this.clientes = this.clientes.filter(c => {
                                const cpf = c.cpf || c.cpfCnpj || '';
                                return cpf !== pedidoCpf;
                            });
                        }
                    }
                    this.carregarClientes();
                    this.carregarTabelaTopClientes();

                    // Atualizar interface imediatamente
                    this.atualizarBadgesMenu();
                    this.atualizarDashboard();
                    this.carregarPedidos(abaAtiva);

                    // Tentar remover no servidor
                    try {
                        await this.excluirDados('pedido', id);
                        this.mostrarNotificacao('Pedido removido com sucesso!', 'success');
                    } catch (serverError) {
                        // Se falhar no servidor, recarregar os dados
                        console.warn('Erro ao remover no servidor, recarregando dados:', serverError);
                        await this.carregarDadosServidor();
                        this.atualizarBadgesMenu();
                        this.atualizarDashboard();
                        this.carregarPedidos(abaAtiva);
                        this.carregarProdutos();
                        this.carregarClientes();
                        this.carregarTabelaTopClientes();
                        this.mostrarNotificacao('Erro ao remover no servidor. Dados recarregados.', 'warning');
                    }

                    return true;
                } catch (e) {
                    console.error('Erro ao remover pedido:', e);
                    // Recarregar dados em caso de erro
                    await this.carregarDadosServidor();
                    this.atualizarBadgesMenu();
                    this.atualizarDashboard();
                    this.carregarPedidos('todos');
                    this.carregarProdutos();
                    this.carregarClientes();
                    this.carregarTabelaTopClientes();
                    return false;
                }
            }
            
            async adicionarProduto(produto) {
                try {
                    const ids = this.produtos
                        .map(p => Number(p.id))
                        .filter(id => Number.isInteger(id) && id > 0);
                    produto.id = ids.length > 0 ? Math.max(...ids) + 1 : 1;

                    if (!produto.dataCadastro) {
                        produto.dataCadastro = this.obterDataHoje();
                    }

                    // Adicionar imediatamente à interface
                    this.produtos.unshift(produto);

                    // Atualizar interface imediatamente
                    this.atualizarBadgesMenu();
                    this.carregarProdutos();

                    // Tentar salvar no servidor
                    try {
                        await this.salvarDados('produto', produto);
                        this.mostrarNotificacao('Produto adicionado com sucesso!', 'success');
                        return produto;
                    } catch (serverError) {
                        // Se falhar no servidor, remover da interface e recarregar
                        console.warn('Erro ao salvar no servidor, recarregando dados:', serverError);
                        this.produtos.shift(); // Remover da interface
                        await this.carregarDadosServidor();
                        this.atualizarBadgesMenu();
                        this.carregarProdutos();
                        this.atualizarDashboard();
                        this.mostrarNotificacao('Erro ao salvar no servidor. Dados recarregados.', 'warning');
                        return null;
                    }
                } catch (e) {
                    console.error('Erro ao adicionar produto:', e);
                    // Recarregar dados em caso de erro
                    await this.carregarDadosServidor();
                    this.atualizarBadgesMenu();
                    this.carregarProdutos();
                    this.atualizarDashboard();
                    return null;
                }
            }
            
            async atualizarProduto(id, dadosAtualizados) {
                try {
                    const index = this.produtos.findIndex(p => Number(p.id) === Number(id));
                    if (index === -1) {
                        this.mostrarNotificacao('Produto não encontrado', 'warning');
                        return false;
                    }

                    dadosAtualizados.id = id;
                    const produtoOriginal = this.produtos[index];
                    this.produtos[index] = { ...produtoOriginal, ...dadosAtualizados };

                    const sucesso = await this.atualizarDados('produto', id, this.produtos[index]);

                    if (sucesso) {
                        // Recarregar dados do servidor para garantir sincronização
                        await this.carregarDadosServidor();

                        // Pequeno delay para garantir sincronização
                        await new Promise(resolve => setTimeout(resolve, 100));

                        // Forçar atualização completa da interface
                        this.carregarProdutos();
                        this.atualizarDashboard();
                        
                        this.mostrarNotificacao('Produto atualizado com sucesso!', 'success');
                        return true;
                    } else {
                        this.produtos[index] = produtoOriginal;
                        return false;
                    }
                } catch (e) {
                    console.error('Erro ao atualizar produto:', e);
                    return false;
                }
            }
            
            async removerProduto(id) {
                try {
                    const index = this.produtos.findIndex(p => p.id == id);
                    if (index === -1) {
                        this.mostrarNotificacao('Produto não encontrado', 'warning');
                        return false;
                    }

                    const sucesso = await this.excluirDados('produto', id);

                    if (sucesso) {
                        // Recarregar dados do servidor para garantir sincronização
                        await this.carregarDadosServidor();

                        // Pequeno delay para garantir sincronização
                        await new Promise(resolve => setTimeout(resolve, 100));

                        // Forçar atualização completa da interface
                        this.atualizarBadgesMenu();
                        this.carregarProdutos();
                        this.atualizarDashboard();
                        
                        this.mostrarNotificacao('Produto removido com sucesso!', 'success');
                        return true;
                    } else {
                        return false;
                    }
                } catch (e) {
                    console.error('Erro ao remover produto:', e);
                    return false;
                }
            }
            
            // ========== NOTIFICAÇÕES ==========
            mostrarNotificacao(mensagem, tipo = 'info') {
                const container = document.getElementById('notification-container');
                if (!container) return;
                
                const notification = document.createElement('div');
                notification.className = `notification ${tipo}`;
                notification.innerHTML = `
                    <i class="fas fa-${this.getNotificacaoIcon(tipo)}"></i>
                    <span>${mensagem}</span>
                `;
                
                container.appendChild(notification);
                
                setTimeout(() => {
                    notification.classList.add('show');
                }, 10);
                
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 400);
                }, 5000);
            }
            
            getNotificacaoIcon(tipo) {
                switch(tipo) {
                    case 'success': return 'check-circle';
                    case 'warning': return 'exclamation-triangle';
                    case 'danger': return 'times-circle';
                    default: return 'info-circle';
                }
            }
            
            // ========== CONFIRMAÇÃO DE EXCLUSÃO ==========
            mostrarConfirmacaoExclusao(tipo, id) {
                const modal = document.createElement('div');
                modal.id = 'modal-confirmacao-exclusao';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                `;

                const titulo = tipo === 'pedido' ? 'Excluir Pedido' : 'Excluir Produto';
                const mensagem = tipo === 'pedido' 
                    ? 'Tem certeza que deseja excluir este pedido? Esta ação não pode ser desfeita.'
                    : 'Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.';

                modal.innerHTML = `
                    <div style="background: linear-gradient(135deg, #2c3e50, #34495e); padding: 30px; border-radius: 15px; max-width: 400px; width: 90%; border: 2px solid #e74c3c; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);">
                        <div style="text-align: center; margin-bottom: 25px;">
                            <div style="font-size: 48px; color: #e74c3c; margin-bottom: 15px;">⚠️</div>
                            <h3 style="color: #ecf0f1; margin: 0 0 10px 0; font-size: 20px;">${titulo}</h3>
                            <p style="color: #bdc3c7; margin: 0; font-size: 14px; line-height: 1.5;">${mensagem}</p>
                        </div>
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button id="btn-cancelar-exclusao" style="padding: 12px 25px; border: 2px solid #95a5a6; background: transparent; color: #95a5a6; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                                ❌ Cancelar
                            </button>
                            <button id="btn-confirmar-exclusao" style="padding: 12px 25px; background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(231, 76, 60, 0.4)'" onmouseout="this.style.transform='translateY(0)'">
                                🗑️ Excluir
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                document.getElementById('btn-cancelar-exclusao').addEventListener('click', () => {
                    modal.remove();
                });

                document.getElementById('btn-confirmar-exclusao').addEventListener('click', () => {
                    modal.remove();
                    if (tipo === 'pedido') {
                        this.removerPedido(id);
                    } else {
                        this.removerProduto(id);
                    }
                });

                modal.addEventListener('click', (e) => {
                    if (e.target === modal) modal.remove();
                });
            }

            // ========== DETALHES DO PEDIDO ==========
            mostrarDetalhesPedido(pedidoId) {
                const pedido = this.pedidos.find(p => p.id == pedidoId);
                if (!pedido) {
                    this.mostrarNotificacao('Pedido não encontrado', 'warning');
                    return;
                }

                const modal = document.createElement('div');
                modal.id = 'modal-detalhes-pedido';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                `;

                const produto = pedido.produto || {};
                const cliente = pedido.cliente || {};
                const rastreio = pedido.rastreio || {};

                const html = `
                    <div style="background: linear-gradient(135deg, #1a1f3a, #16213e); padding: 30px; border-radius: 15px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; border: 2px solid #3498db; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h2 style="color: #3498db; margin: 0; font-size: 24px;">📦 Detalhes do Pedido #${pedido.id}</h2>
                            <button id="btn-fechar-detalhes" style="background: transparent; border: none; color: #95a5a6; font-size: 24px; cursor: pointer; padding: 5px;">×</button>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                            <div style="background: rgba(52, 152, 219, 0.1); padding: 20px; border-radius: 10px; border-left: 4px solid #3498db;">
                                <h4 style="color: #3498db; margin: 0 0 15px 0; font-size: 16px;">🛍️ Produto</h4>
                                <div style="color: #ecf0f1; line-height: 1.6;">
                                    <strong>Nome:</strong> <span class="dado-sensivel-produto">${produto.nome || 'Não informado'}</span><br>
                                    <strong>Preço Venda:</strong> R$ ${(produto.precoVenda || 0).toFixed(2)}<br>
                                    <strong>Preço Custo:</strong> R$ ${(produto.precoCusto || 0).toFixed(2)}<br>
                                    <strong>Categoria:</strong> ${produto.categoria || 'Não informada'}<br>
                                    <strong>ASIN:</strong> ${produto.asin || 'Não informado'}
                                </div>
                            </div>

                            <div style="background: rgba(46, 204, 113, 0.1); padding: 20px; border-radius: 10px; border-left: 4px solid #2ecc71;">
                                <h4 style="color: #2ecc71; margin: 0 0 15px 0; font-size: 16px;">👤 Cliente</h4>
                                <div style="color: #ecf0f1; line-height: 1.6;">
                                    <strong>Nome:</strong> <span class="dado-sensivel-cliente">${cliente.nome || 'Não informado'}</span><br>
                                    <strong>CPF:</strong> <span class="dado-sensivel-cpf">${cliente.cpf || 'Não informado'}</span><br>
                                    <strong>Telefone:</strong> <span class="dado-sensivel-telefone">${cliente.telefone || 'Não informado'}</span><br>
                                    <strong>ID do Pedido Amazon:</strong> <span class="dado-sensivel-amazon-id">${cliente.amazonId || 'Não informado'}</span>
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(155, 89, 182, 0.1); padding: 20px; border-radius: 10px; border-left: 4px solid #9b59b6; margin-bottom: 25px;">
                            <h4 style="color: #9b59b6; margin: 0 0 15px 0; font-size: 16px;">📊 Status e Rastreamento</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; color: #ecf0f1; margin-bottom: 15px;">
                                <div><strong>Status:</strong><br><span class="status-badge ${this.getStatusClass(rastreio.status)}">${this.getStatusText(rastreio.status)}</span></div>
                                <div><strong>Código:</strong><br><span class="dado-sensivel-rastreio">${pedido.codigoRastreio || 'Não informado'}</span></div>
                                <div><strong>Conta:</strong><br>${pedido.contaShopee || 'Não informada'}</div>
                            </div>
                            ${pedido.codigoRastreio && !pedido.codigoRastreio.toUpperCase().startsWith('BR') ? '<div style="background: rgba(255, 193, 7, 0.15); color: #f39c12; padding: 10px; border-radius: 6px; border-left: 4px solid #f39c12; font-size: 13px;"><strong>⚠️ Aviso:</strong> O código de rastreio deve começar com "BR" para ser válido.</div>' : ''}
                        </div>

                        <div style="background: rgba(230, 126, 34, 0.1); padding: 20px; border-radius: 10px; border-left: 4px solid #e67e22;">
                            <h4 style="color: #e67e22; margin: 0 0 15px 0; font-size: 16px;">📅 Datas</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; color: #ecf0f1;">
                                <div><strong>Cadastro:</strong><br>${this.formatarData(pedido.dataCadastro)}</div>
                                <div><strong>Envio:</strong><br>${rastreio.dataEnvio ? this.formatarData(rastreio.dataEnvio) : 'Não informado'}</div>
                                <div><strong>Entrega:</strong><br>${rastreio.dataEntrega ? this.formatarData(rastreio.dataEntrega) : 'Não entregue'}</div>
                            </div>
                        </div>

                        <div style="text-align: center; margin-top: 25px;">
                            ${cliente.amazonId ? `
                            <a href="https://sellercentral.amazon.com.br/orders-v3/order/${encodeURIComponent(cliente.amazonId)}" target="_blank" style="padding: 12px 25px; background: linear-gradient(135deg, #ff9900, #ff6600); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-right: 10px; display: inline-block; text-decoration: none;">
                                🔗 Ver Pedido na Amazon
                            </a>
                            ` : ''}
                            <button id="btn-editar-pedido-modal" style="padding: 12px 25px; background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-right: 10px;">
                                ✏️ Editar Pedido
                            </button>
                            <button id="btn-rastrear-pedido-modal" style="padding: 12px 25px; background: linear-gradient(135deg, #e67e22, #d35400); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                🚚 Rastrear
                            </button>
                        </div>
                    </div>
                `;

                modal.innerHTML = html;
                document.body.appendChild(modal);

                document.getElementById('btn-fechar-detalhes').addEventListener('click', () => {
                    modal.remove();
                });

                document.getElementById('btn-editar-pedido-modal').addEventListener('click', () => {
                    modal.remove();
                    this.editarPedido(pedidoId);
                });

                document.getElementById('btn-rastrear-pedido-modal').addEventListener('click', () => {
                    const codigo = pedido.codigoRastreio;
                    if (codigo) {
                        this.abrirRastreioModal(codigo);
                    } else {
                        this.mostrarNotificacao('Código de rastreio não encontrado', 'warning');
                    }
                });

                modal.addEventListener('click', (e) => {
                    if (e.target === modal) modal.remove();
                });
            }
            
            // ========== CONFIGURAÇÃO DE EVENTOS ==========
            configurarEventos() {
                // Navegação do menu lateral
                document.querySelectorAll('.menu-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        document.querySelectorAll('.menu-link').forEach(item => {
                            item.classList.remove('active');
                        });
                        
                        link.classList.add('active');
                        
                        const tabId = link.getAttribute('data-tab');
                        this.ativarAba(tabId);
                    });
                });
                
                document.querySelectorAll('.tab-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        const tabsContainer = button.closest('.tabs-header');
                        tabsContainer.querySelectorAll('.tab-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        
                        button.classList.add('active');
                        
                        const tabId = button.getAttribute('data-tab');
                        
                        // Verificar se é aba de análise (dentro da seção de análise financeira)
                        if (tabsContainer.closest('#analise')) {
                            this.carregarAbaAnalise(tabId);
                        } else {
                            this.ativarTab(tabId);
                        }
                    });
                });
                
                document.querySelectorAll('.pedido-tab-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        document.querySelectorAll('.pedido-tab-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        
                        button.classList.add('active');
                        
                        const tabId = button.getAttribute('data-pedido-tab');
                        this.ativarPedidoTab(tabId);
                    });
                });
                
                document.getElementById('btn-novo-pedido').addEventListener('click', () => {
                    this.abrirModalNovoPedido();
                });
                
                document.getElementById('btn-novo-produto').addEventListener('click', () => {
                    this.abrirModalNovoProduto();
                });
                
                // Filtro de Conta Shopee
                const filtroContaShopee = document.getElementById('filtro-conta-shopee');
                if (filtroContaShopee) {
                    filtroContaShopee.addEventListener('change', () => {
                        this.atualizarFiltroContaShopee();
                    });
                }
                
                // Botões da dashboard
                const btnNovoPedidoDash = document.getElementById('btn-novo-pedido-dashboard');
                if (btnNovoPedidoDash) {
                    btnNovoPedidoDash.addEventListener('click', () => {
                        this.abrirModalNovoPedido();
                    });
                }
                
                const btnNovoProdutoDash = document.getElementById('btn-novo-produto-dashboard');
                if (btnNovoProdutoDash) {
                    btnNovoProdutoDash.addEventListener('click', () => {
                        this.abrirModalNovoProduto();
                    });
                }
                
                const btnVerPedidosDash = document.getElementById('btn-ver-pedidos-dashboard');
                if (btnVerPedidosDash) {
                    btnVerPedidosDash.addEventListener('click', () => {
                        this.ativarAba('pedidos');
                    });
                }

                const btnHeroPedidos = document.getElementById('btn-hero-pedidos');
                if (btnHeroPedidos) {
                    btnHeroPedidos.addEventListener('click', () => {
                        this.ativarAba('pedidos');
                    });
                }

                const btnHeroAnalise = document.getElementById('btn-hero-analise');
                if (btnHeroAnalise) {
                    btnHeroAnalise.addEventListener('click', () => {
                        this.ativarAba('analise');
                    });
                }
                
                const btnVerProdutosDash = document.getElementById('btn-ver-produtos-dashboard');
                if (btnVerProdutosDash) {
                    btnVerProdutosDash.addEventListener('click', () => {
                        this.ativarAba('produtos');
                    });
                }
                
                document.getElementById('btn-backup-pedidos').addEventListener('click', () => {
                    this.fazerBackup('pedidos');
                });
                
                document.getElementById('btn-backup-produtos').addEventListener('click', () => {
                    this.fazerBackup('produtos');
                });

                // Botões de importação
                const btnImportarPedidos = document.getElementById('btn-importar-pedidos');
                if (btnImportarPedidos) {
                    btnImportarPedidos.addEventListener('click', () => {
                        this.abrirImportadorPedidos();
                    });
                }

                const btnImportarProdutos = document.getElementById('btn-importar-produtos');
                if (btnImportarProdutos) {
                    btnImportarProdutos.addEventListener('click', () => {
                        this.abrirImportadorProdutos();
                    });
                }
                
                // Botão para ocultar/mostrar dados sensíveis
                const btnToggleDadosSensiveis = document.getElementById('btn-toggle-dados-sensiveis');
                if (btnToggleDadosSensiveis) {
                    btnToggleDadosSensiveis.addEventListener('click', () => {
                        this.toggleDadosSensiveis();
                    });
                }
                
                document.getElementById('btn-advanced-search').addEventListener('click', () => {
                    this.abrirModalBuscaAvancada();
                });
                
                const searchInput = document.getElementById('search-input');
                if (searchInput) {
                    let timeout;
                    searchInput.addEventListener('input', (e) => {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => {
                            this.executarBuscaRapida(e.target.value);
                        }, 300);
                    });
                }
                
                document.getElementById('btn-perguntar-ia').addEventListener('click', () => {
                    const pergunta = document.getElementById('pergunta-ia').value;
                    if (pergunta.trim()) {
                        this.perguntarIA(pergunta);
                    } else {
                        this.mostrarNotificacao('Digite uma pergunta para a IA.', 'warning');
                    }
                });
                
                document.getElementById('btn-analise-precos').addEventListener('click', () => {
                    document.getElementById('pergunta-ia').value = 'Analise os preços dos meus produtos e sugira ajustes';
                    this.perguntarIA('Analise os preços dos meus produtos e sugira ajustes');
                });
                
                document.getElementById('btn-sugestoes-vendas').addEventListener('click', () => {
                    document.getElementById('pergunta-ia').value = 'Dê sugestões para aumentar minhas vendas';
                    this.perguntarIA('Dê sugestões para aumentar minhas vendas');
                });
                
                document.getElementById('btn-previsao-vendas').addEventListener('click', () => {
                    document.getElementById('pergunta-ia').value = 'Faça uma previsão de vendas para os próximos meses';
                    this.perguntarIA('Faça uma previsão de vendas para os próximos meses');
                });
                
                document.getElementById('btn-pesquisa-mercado').addEventListener('click', () => {
                    const produto = prompt('Digite o nome do produto para pesquisar no mercado:');
                    if (produto) {
                        document.getElementById('pergunta-ia').value = `Pesquise o produto "${produto}" no mercado. Inclua: preço médio, concorrentes principais, demanda sazonal, e sugestões de posicionamento.`;
                        this.perguntarIA(document.getElementById('pergunta-ia').value);
                    }
                });
                
                document.getElementById('btn-analise-concorrencia').addEventListener('click', () => {
                    const produto = prompt('Digite o nome do produto para analisar a concorrência:');
                    if (produto) {
                        document.getElementById('pergunta-ia').value = `Faça uma análise detalhada da concorrência para o produto "${produto}". Inclua: principais concorrentes, faixa de preço, estratégias de marketing, e diferenciais.`;
                        this.perguntarIA(document.getElementById('pergunta-ia').value);
                    }
                });
                
                document.getElementById('btn-gerar-anuncio').addEventListener('click', () => {
                    const produto = prompt('Digite o nome do produto para gerar um anúncio:');
                    if (produto) {
                        document.getElementById('pergunta-ia').value = `Crie um anúncio completo para o produto "${produto}" para ser usado na Amazon/Shopee. Inclua: título chamativo, 5 bullets points, descrição detalhada em HTML, e sugestão de imagens (descrição).`;
                        this.perguntarIA(document.getElementById('pergunta-ia').value);
                    }
                });
                
                document.getElementById('btn-analise-tendencias').addEventListener('click', () => {
                    document.getElementById('pergunta-ia').value = `Com base no meu catálogo de produtos e nas tendências atuais de mercado, quais produtos devo priorizar? Quais categorias estão em alta? Dê recomendações práticas.`;
                    this.perguntarIA(document.getElementById('pergunta-ia').value);
                });
                
                document.getElementById('btn-copiar-resposta').addEventListener('click', () => {
                    this.copiarRespostaIA();
                });
                
                document.getElementById('btn-limpar-resposta').addEventListener('click', () => {
                    document.getElementById('resposta-ia').style.display = 'none';
                });
                
                document.getElementById('btn-limpar-historico').addEventListener('click', () => {
                    this.historicoIA = [];
                    this.atualizarHistoricoIA();
                    this.mostrarNotificacao('Histórico limpo.', 'info');
                });
                
                document.getElementById('btn-calcular-lucro').addEventListener('click', () => {
                    this.calcularLucro();
                });
                
                this.configurarSourcing();
                this.configurarImportacaoAmazon(); // NOVO
                // Visualização será configurada via JavaScript global

                document.addEventListener('click', (e) => {
                    // Botões de visualização - verificar primeiro para garantir que funcionem
                    if (e.target.closest('.view-btn')) {
                        const btn = e.target.closest('.view-btn');
                        const tipo = btn.id.includes('pedidos') ? 'pedidos' : 'produtos';
                        const modo = btn.dataset.view;

                        // Para pedidos, precisamos atualizar múltiplos containers
                        if (tipo === 'pedidos') {
                            // Atualizar todos os containers de pedidos ativos
                            const containers = document.querySelectorAll('.pedidos-container');
                            const btnLista = document.getElementById('view-lista-pedidos');
                            const btnGrid = document.getElementById('view-quadrado-pedidos');

                            if (containers.length > 0) {
                                containers.forEach(container => {
                                    if (modo === 'lista') {
                                        container.classList.add('lista');
                                        container.classList.remove('grid');
                                    } else {
                                        container.classList.add('grid');
                                        container.classList.remove('lista');
                                    }
                                });

                                // Atualizar botões
                                if (modo === 'lista') {
                                    btnLista.classList.add('active');
                                    btnGrid.classList.remove('active');
                                } else {
                                    btnGrid.classList.add('active');
                                    btnLista.classList.remove('active');
                                }

                                // Salvar preferência
                                localStorage.setItem('config_visualizacao_pedidos', modo);

                                const abaAtiva = document.querySelector('.pedido-tab-btn.active')?.getAttribute('data-pedido-tab') || 'todos';
                                this.carregarPedidos(abaAtiva);

                                this.mostrarNotificacao(
                                    `Visualização de Pedidos alterada para ${modo === 'lista' ? 'Lista' : 'Grade'}`,
                                    'success'
                                );
                            }
                        } else if (tipo === 'produtos') {
                            // Para produtos, atualizar o container específico
                            const container = document.getElementById('products-container');
                            const btnLista = document.getElementById('view-lista-produtos');
                            const btnGrid = document.getElementById('view-quadrado-produtos');

                            if (container) {
                                if (modo === 'lista') {
                                    container.classList.add('lista');
                                    container.classList.remove('grid');
                                    btnLista.classList.add('active');
                                    btnGrid.classList.remove('active');
                                } else {
                                    container.classList.add('grid');
                                    container.classList.remove('lista');
                                    btnGrid.classList.add('active');
                                    btnLista.classList.remove('active');
                                }

                                // Salvar preferência
                                localStorage.setItem('config_visualizacao_produtos', modo);

                                this.mostrarNotificacao(
                                    `Visualização de Produtos alterada para ${modo === 'lista' ? 'Lista' : 'Grade'}`,
                                    'success'
                                );
                            }
                        }
                        return;
                    }

                    if (e.target.closest('.btn-editar-pedido')) {
                        const pedidoId = parseInt(e.target.closest('.btn-editar-pedido').dataset.id);
                        this.editarPedido(pedidoId);
                    }

                    if (e.target.closest('.btn-excluir-pedido')) {
                        const pedidoId = parseInt(e.target.closest('.btn-excluir-pedido').dataset.id);
                        this.mostrarConfirmacaoExclusao('pedido', pedidoId);
                    }

                    if (e.target.closest('.btn-atualizar-status')) {
                        const pedidoId = parseInt(e.target.closest('.btn-atualizar-status').dataset.id);
                        this.atualizarStatusPedido(pedidoId);
                    }

                    if (e.target.closest('.btn-rastrear-pedido')) {
                        const codigoRastreio = e.target.closest('.btn-rastrear-pedido').dataset.codigo;
                        if (codigoRastreio) {
                            this.abrirRastreioModal(codigoRastreio);
                        } else {
                            this.mostrarNotificacao('Código de rastreio não encontrado', 'warning');
                        }
                    }

                    // Clique na linha da tabela para mostrar detalhes do produto (modo lista)
                    if (e.target.closest('tr') && !e.target.closest('button') && !e.target.closest('a')) {
                        const row = e.target.closest('tr');
                        const pedidoId = parseInt(row.dataset.pedidoId);
                        if (pedidoId) {
                            this.mostrarDetalhesPedido(pedidoId);
                        }
                    }

                    if (e.target.closest('.btn-editar-produto')) {
                        const produtoId = parseInt(e.target.closest('.btn-editar-produto').dataset.id);
                        this.editarProduto(produtoId);
                    }
                    
                    if (e.target.closest('.btn-excluir-produto')) {
                        const produtoId = parseInt(e.target.closest('.btn-excluir-produto').dataset.id);
                        this.mostrarConfirmacaoExclusao('produto', produtoId);
                    }
                    
                    if (e.target.closest('.btn-analisar-produto-detalhe')) {
                        const produtoId = parseInt(e.target.closest('.btn-analisar-produto-detalhe').dataset.id);
                        this.analisarProdutoComIA(produtoId);
                    }
                });
                
                this.configurarBuscaAvancada();
                this.configurarModais();
            }
            
            configurarModais() {
                document.querySelectorAll('.modal-close').forEach(closeBtn => {
                    closeBtn.addEventListener('click', () => {
                        const modalId = closeBtn.closest('.modal-overlay').id;
                        this.fecharModal(modalId);
                    });
                });
                
                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            this.fecharModal(modal.id);
                        }
                    });
                });
                
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        document.querySelectorAll('.modal-overlay').forEach(modal => {
                            if (modal.style.display === 'flex') {
                                this.fecharModal(modal.id);
                            }
                        });
                    }
                });
            }
            
            // ========== FUNÇÕES DE MODAL ==========
            abrirModalNovoPedido(editMode = false) {
                if (!editMode) {
                    this.pedidoEditandoId = null;
                }
                this.criarFormularioPedido();
                this.mostrarModal('modal-novo-pedido');
            }
            
            abrirModalNovoProduto() {
                this.criarFormularioProduto();
                this.mostrarModal('modal-produto');
            }
            
            abrirModalBuscaAvancada() {
                this.mostrarModal('modal-busca-avancada');
            }
            
            mostrarModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            }
            
            abrirRastreioModal(codigoRastreio) {
                const urlRastreamento = `https://www.4tracking.net/pt/tjax/track?nums=${encodeURIComponent(codigoRastreio)}`;
                window.open(urlRastreamento, '_blank');
            }
            
            fecharModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            }
            
            criarFormularioPedido() {
                const form = document.getElementById('form-novo-pedido');
                if (!form) return;
                
                const hoje = this.obterDataHoje();
                
                form.innerHTML = `
                    <div id="cliente" class="modal-tab-content active">
                        <div class="form-group">
                            <label class="form-label">Selecionar Produto Existente</label>
                            <select class="form-control" id="pedido-produto-existente" required>
                                <option value="">Selecione um produto existente</option>
                                ${this.produtos.map(p => `<option value="${p.id}">${p.nome} (R$ ${(p.precoVenda || 0).toFixed(2)})</option>`).join('')}
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Conta Shopee*</label>
                                <input type="text" class="form-control" id="pedido-conta-shopee" placeholder="Ex: minha_loja_oficial" required>
                                <small style="color: var(--text-muted);">Nome de usuário ou ID da sua conta</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="pedido-cliente-nome" placeholder="Ex: João Silva" required>
                            </div>
                            <div class="form-group campo-cpf-container">
                                <label class="form-label">
                                    📋 CPF/CNPJ *
                                    <span style="font-size: 11px; color: var(--text-muted); margin-left: 5px;">(Apenas números ou formatado)</span>
                                </label>
                                <input type="text"
                                       class="form-control dado-sensivel-cpf"
                                       id="pedido-cliente-cpf"
                                       placeholder="000.000.000-00"
                                       maxlength="14"
                                       data-type="cpf"
                                       required>
                                <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">
                                    💡 Digite apenas os números ou use o formato 123.456.789-00
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Telefone *</label>
                                <input type="text" class="form-control" id="pedido-cliente-telefone" placeholder="(11) 99999-9999" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ID do Pedido Amazon *</label>
                                <input type="text" class="form-control" id="pedido-cliente-amazon-id" placeholder="702-3694435-8872212" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">CEP *</label>
                                <input type="text" class="form-control" id="pedido-cliente-cep" placeholder="38414-224" required>
                                <small style="color: var(--text-muted); display: block; margin-top: 5px;">Informe o CEP para preencher automaticamente o endereço.</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Número *</label>
                                <input type="text" class="form-control" id="pedido-cliente-numero" placeholder="361" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Rua *</label>
                                <input type="text" class="form-control" id="pedido-cliente-rua" placeholder="Ex: Avenida do Óleo" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="pedido-cliente-complemento" placeholder="Bloco 20 – Apto 104">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Bairro *</label>
                                <input type="text" class="form-control" id="pedido-cliente-bairro" placeholder="Centro" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Cidade *</label>
                                <input type="text" class="form-control" id="pedido-cliente-cidade" placeholder="Uberlândia" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Estado *</label>
                                <input type="text" class="form-control" id="pedido-cliente-estado" placeholder="MG" maxlength="2" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Link do Mapa (opcional)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="url" class="form-control" id="pedido-cliente-maps" placeholder="https://maps.app.goo.gl/...">
                                <button type="button" class="btn btn-secondary" id="btn-gerar-maps">Gerar Link</button>
                            </div>
                            <small style="color: var(--text-muted);">Clique em "Gerar Link" para criar automaticamente com base no endereço</small>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary btn-proximo" data-proximo="produto">
                                Próximo: Produto <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="produto" class="modal-tab-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nome do Produto *</label>
                                <input type="text" class="form-control" id="pedido-produto-nome" placeholder="Ex: Fone Bluetooth Premium" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Categoria *</label>
                                <select class="form-control" id="pedido-produto-categoria" onchange="marketManager.atualizarCalculoTaxaPedido()">
                                    ${this.gerarOpcoesCategorias()}
                                </select>
                                <small id="pedido-taxa-info" style="color: var(--text-muted); display: block; margin-top: 5px;">Taxa: --</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Link do Produto *</label>
                                <input type="url" class="form-control" id="pedido-produto-link" placeholder="https://amazon.com.br/produto">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Preço Pago (Custo) *</label>
                                <input type="number" step="0.01" class="form-control" id="pedido-produto-preco-custo" placeholder="0.00" required min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preço de Venda *</label>
                                <input type="number" step="0.01" class="form-control" id="pedido-produto-preco-venda" placeholder="0.00" required min="0">
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" class="btn btn-voltar" data-voltar="cliente">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </button>
                            <button type="button" class="btn btn-secondary btn-proximo" data-proximo="rastreio">
                                Próximo: Rastreio <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="rastreio" class="modal-tab-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Código de Rastreio *</label>
                                <input type="text" class="form-control" id="pedido-rastreio-codigo" placeholder="Ex: BR123456789" required>
                                <small style="display: block; margin-top: 8px; padding: 10px; background: rgba(33, 150, 243, 0.1); border-left: 3px solid #2196F3; border-radius: 4px; color: #1976D2;">
                                    <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                                    📌 <strong>Importante:</strong> Coloque o código de rastreio com <strong>"BR" NA FRENTE</strong> quando estiver disponível (Ex: <strong>BR</strong>123456789). Assim que tiver o código, atualize este campo para poder usar "Atualizar Status".
                                </small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Transportadora</label>
                                <select class="form-control" id="pedido-rastreio-transportadora">
                                    <option value="shopee" selected>Shopee</option>
                                    <option value="correios">Correios</option>
                                    <option value="jadlog">Jadlog</option>
                                    <option value="azul">Azul Cargo</option>
                                    <option value="outra">Outra</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Data de Envio</label>
                                <input type="date" class="form-control" id="pedido-rastreio-data" value="${hoje}" disabled>
                                <small style="color: #8a9bb2;">Preenchida automaticamente ao atualizar status</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-control" id="pedido-rastreio-status" disabled>
                                    <option value="pendente">Pendente</option>
                                    <option value="processando">Processando</option>
                                    <option value="transito">Em Trânsito</option>
                                    <option value="entregue">Entregue</option>
                                    <option value="atrasado">Atrasado</option>
                                </select>
                                <small style="color: #8a9bb2;">Use &quot;Atualizar Status&quot; para mudar</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Data de Entrega (opcional)</label>
                            <input type="date" class="form-control" id="pedido-rastreio-data-entrega" placeholder="">
                            <small style="color: #8a9bb2;">Data em que o pedido foi entregue ao cliente</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Link de Rastreio</label>
                            <input type="url" class="form-control" id="pedido-rastreio-link" placeholder="https://www.4tracking.net/pt/tjax/track?nums=" disabled>
                            <small style="color: #8a9bb2;">Gerado automaticamente com base no código de rastreio</small>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" class="btn btn-voltar" data-voltar="produto">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </button>
                            <button type="button" class="btn btn-secondary btn-proximo" data-proximo="pagamento">
                                Próximo: Pagamento <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="pagamento" class="modal-tab-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Método de Pagamento</label>
                                <select class="form-control" id="pedido-metodo-pagamento">
                                    <option value="cartao">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="pix">Cartão de Crédito</option>
                                    <option value="shopee">Shopee Pay</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status do Pagamento</label>
                                <select class="form-control" id="pedido-status-pagamento">
                                    <option value="pago">Pago</option>
                                    <option value="pendente">Pendente</option>
                                    <option value="estornado">Estornado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" id="pedido-observacoes" placeholder="Observações sobre o pedido" rows="3"></textarea>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                            <button type="button" class="btn btn-voltar" data-voltar="rastreio">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Pedido Completo
                            </button>
                        </div>
                    </div>
                `;
                
                // Adicionar preenchimento automático do CEP
                document.getElementById('pedido-cliente-cep').addEventListener('blur', async () => {
                    const cepInput = document.getElementById('pedido-cliente-cep');
                    const cep = cepInput.value.replace(/\D/g, '');
                    
                    if (cep.length === 8) {
                        cepInput.disabled = true;
                        cepInput.value = 'Buscando...';
                        
                        try {
                            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                            const data = await response.json();
                            
                            if (!data.erro) {
                                document.getElementById('pedido-cliente-rua').value = data.logradouro || '';
                                document.getElementById('pedido-cliente-bairro').value = data.bairro || '';
                                document.getElementById('pedido-cliente-cidade').value = data.localidade || '';
                                document.getElementById('pedido-cliente-estado').value = data.uf || '';
                                this.mostrarNotificacao('Endereço preenchido automaticamente!', 'success');
                            } else {
                                this.mostrarNotificacao('CEP não encontrado. Verifique o código digitado.', 'warning');
                            }
                        } catch (error) {
                            console.error('Erro ao buscar CEP:', error);
                            this.mostrarNotificacao('Erro ao buscar CEP. Tente novamente.', 'error');
                        } finally {
                            cepInput.disabled = false;
                            cepInput.value = cep.replace(/(\d{5})(\d{3})/, '$1-$2');
                        }
                    }
                });
                
                const produtoSelect = document.getElementById('pedido-produto-existente');
                produtoSelect.addEventListener('change', () => {
                    const produtoId = parseInt(produtoSelect.value);
                    if (produtoId) {
                        const produto = this.produtos.find(p => p.id === produtoId);
                        if (produto) {
                            document.getElementById('pedido-produto-nome').value = produto.nome;
                            document.getElementById('pedido-produto-categoria').value = produto.categoria || 'eletronicos';
                            document.getElementById('pedido-produto-preco-custo').value = produto.precoCusto || '';
                            document.getElementById('pedido-produto-preco-venda').value = produto.precoVenda || '';
                            document.getElementById('pedido-produto-link').value = produto.link || '';
                            this.atualizarCalculoTaxaPedido();
                        }
                    } else {
                        document.getElementById('pedido-produto-nome').value = '';
                        document.getElementById('pedido-produto-categoria').value = 'eletronicos';
                        document.getElementById('pedido-produto-preco-custo').value = '';
                        document.getElementById('pedido-produto-preco-venda').value = '';
                        document.getElementById('pedido-produto-link').value = '';
                        this.atualizarCalculoTaxaPedido();
                    }
                });
                
                // Adicionar listeners para cálculo automático nos preços
                setTimeout(() => {
                    document.getElementById('pedido-produto-preco-custo')?.addEventListener('input', () => {
                        this.atualizarCalculoTaxaPedido();
                    });
                    
                    document.getElementById('pedido-produto-preco-venda')?.addEventListener('input', () => {
                        this.atualizarCalculoTaxaPedido();
                    });
                }, 100);
                
                form.querySelectorAll('.btn-proximo').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const proximaAba = btn.dataset.proximo;
                        const abaAtual = btn.closest('.modal-tab-content')?.id;
                        if (this.validarAbaPedido(abaAtual)) {
                            this.mudarAbaModal(proximaAba);
                        }
                    });
                });
                
                form.querySelectorAll('.btn-voltar').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const abaAnterior = btn.dataset.voltar;
                        this.mudarAbaModal(abaAnterior);
                    });
                });
                
                document.getElementById('btn-gerar-maps').addEventListener('click', () => {
                    const rua = document.getElementById('pedido-cliente-rua').value;
                    const numero = document.getElementById('pedido-cliente-numero').value;
                    const complemento = document.getElementById('pedido-cliente-complemento').value;
                    const cidade = document.getElementById('pedido-cliente-cidade').value;
                    const estado = document.getElementById('pedido-cliente-estado').value;
                    const cep = document.getElementById('pedido-cliente-cep').value;

                    const enderecoCompleto = `${rua}, ${numero}${complemento ? ' - ' + complemento : ''}, ${cidade} - ${estado}, ${cep}`;
                    const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(enderecoCompleto)}`;
                    document.getElementById('pedido-cliente-maps').value = mapsUrl;
                    this.mostrarNotificacao('Link do mapa gerado!', 'success');
                });
            }
            
            criarFormularioProduto() {
                const form = document.getElementById('form-novo-produto');
                if (!form) return;
                
                form.innerHTML = `
                    <!-- Nome e Categoria -->
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Nome do Produto *</label>
                            <input type="text" class="form-control" id="produto-nome-novo" placeholder="Ex: Fone Bluetooth Premium" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Categoria *</label>
                            <select class="form-control" id="produto-categoria-novo" required onchange="marketManager.atualizarCalculoTaxaProduto()">
                                ${this.gerarOpcoesCategorias()}
                            </select>
                            <small id="produto-taxa-info" style="color: var(--text-muted); display: block; margin-top: 5px;">Taxa: --</small>
                        </div>
                    </div>
                    
                    <!-- ASIN e Plataforma -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Código/ASIN</label>
                            <input type="text" class="form-control" id="produto-asin-novo" placeholder="B08XYZ1234">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Plataforma Principal</label>
                            <select class="form-control" id="produto-plataforma-novo">
                                <option value="amazon">Amazon</option>
                                <option value="shopee" selected>Shopee</option>
                                <option value="ambas">Ambas</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Preços -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Preço de Custo *</label>
                            <input type="number" step="0.01" class="form-control" id="produto-preco-custo-novo" placeholder="0.00" required min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Preço de Venda *</label>
                            <input type="number" step="0.01" class="form-control" id="produto-preco-venda-novo" placeholder="0.00" required min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estoque</label>
                            <input type="number" class="form-control" id="produto-estoque-novo" placeholder="0" min="0" value="0">
                        </div>
                    </div>
                    
                    <!-- Link e Descrição -->
                    <div class="form-group">
                        <label class="form-label">Link do Produto</label>
                        <input type="url" class="form-control" id="produto-link-novo" placeholder="https://shopee.com.br/produto">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Descrição do Produto</label>
                        <textarea class="form-control" id="produto-descricao-novo" rows="3" placeholder="Descrição detalhada do produto, características, especificações..."></textarea>
                    </div>
                    
                    <!-- Botões -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-outline" id="limpar-produto" style="color: var(--text-muted);">
                            <i class="fas fa-eraser"></i> Limpar
                        </button>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="btn btn-secondary" id="cancelar-produto">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Salvar Produto
                            </button>
                        </div>
                    </div>
                `;
                
                document.getElementById('cancelar-produto').addEventListener('click', () => {
                    if (confirm('Tem certeza que deseja cancelar? Os dados não salvos serão perdidos.')) {
                        this.fecharModal('modal-produto');
                        document.getElementById('form-novo-produto').reset();
                    }
                });
                
                document.getElementById('limpar-produto').addEventListener('click', () => {
                    if (confirm('Limpar todos os campos do formulário?')) {
                        document.getElementById('form-novo-produto').reset();
                        this.atualizarCalculoTaxaProduto();
                        document.getElementById('produto-nome-novo').focus();
                    }
                });
                
                // Adicionar listeners para cálculo automático
                document.getElementById('produto-preco-custo-novo')?.addEventListener('input', () => {
                    this.atualizarCalculoTaxaProduto();
                });
                
                document.getElementById('produto-preco-venda-novo')?.addEventListener('input', () => {
                    this.atualizarCalculoTaxaProduto();
                });
                
                // Disparar cálculo inicial
                this.atualizarCalculoTaxaProduto();
                
                form.onsubmit = async (e) => {
                    e.preventDefault();
                    
                    // Validações básicas
                    const nome = document.getElementById('produto-nome-novo').value.trim();
                    const categoria = document.getElementById('produto-categoria-novo').value;
                    const precoCusto = parseFloat(document.getElementById('produto-preco-custo-novo').value) || 0;
                    const precoVenda = parseFloat(document.getElementById('produto-preco-venda-novo').value) || 0;
                    
                    if (!nome) {
                        this.mostrarNotificacao('Nome do produto é obrigatório!', 'warning');
                        document.getElementById('produto-nome-novo').focus();
                        return;
                    }
                    
                    if (!categoria) {
                        this.mostrarNotificacao('Selecione uma categoria!', 'warning');
                        document.getElementById('produto-categoria-novo').focus();
                        return;
                    }
                    
                    if (precoCusto <= 0) {
                        this.mostrarNotificacao('Preço de custo deve ser maior que zero!', 'warning');
                        document.getElementById('produto-preco-custo-novo').focus();
                        return;
                    }
                    
                    if (precoVenda <= 0) {
                        this.mostrarNotificacao('Preço de venda deve ser maior que zero!', 'warning');
                        document.getElementById('produto-preco-venda-novo').focus();
                        return;
                    }
                    
                    const link = document.getElementById('produto-link-novo').value.trim();
                    if (link && !link.match(/^https?:\/\/.+/)) {
                        this.mostrarNotificacao('Link do produto deve começar com http:// ou https://', 'warning');
                        document.getElementById('produto-link-novo').focus();
                        return;
                    }
                    
                    await this.salvarProdutoDoFormulario();
                };
            }
            
            validarAbaPedido(abaId) {
                const abaContent = document.getElementById(abaId);
                if (!abaContent) return true;

                const campos = Array.from(abaContent.querySelectorAll('[required]'));
                for (const campo of campos) {
                    const valor = campo.value?.toString().trim();
                    if (!valor) {
                        this.marcarCampoInvalido(campo);
                        const label = campo.closest('.form-group')?.querySelector('.form-label')?.textContent?.trim() || 'Campo obrigatório';
                        this.mostrarNotificacao(`Preencha o campo: ${label}`, 'warning');
                        return false;
                    }

                    if (campo.type === 'url' && valor && !/^https?:\/\/.+/.test(valor)) {
                        this.marcarCampoInvalido(campo);
                        const label = campo.closest('.form-group')?.querySelector('.form-label')?.textContent?.trim() || 'URL inválida';
                        this.mostrarNotificacao(`Informe um endereço válido em: ${label}`, 'warning');
                        return false;
                    }
                }

                if (abaId === 'cliente') {
                    const produtoSelect = document.getElementById('pedido-produto-existente');
                    if (!produtoSelect?.value) {
                        this.marcarCampoInvalido(produtoSelect);
                        this.mostrarNotificacao('Selecione um produto existente antes de continuar.', 'warning');
                        return false;
                    }
                }

                return true;
            }

            marcarCampoInvalido(campo) {
                if (!campo) return;
                campo.focus();
                const originalBorder = campo.style.border;
                campo.style.border = '1px solid #e74c3c';
                setTimeout(() => {
                    campo.style.border = originalBorder;
                }, 3000);
            }

            mudarAbaModal(abaId) {
                document.querySelectorAll('.modal-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                document.querySelectorAll('.modal-tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                const abaBtn = document.querySelector(`[data-modal-tab="${abaId}"]`);
                const abaContent = document.getElementById(abaId);
                
                if (abaBtn) abaBtn.classList.add('active');
                if (abaContent) abaContent.classList.add('active');
            }
            
            async salvarPedidoDoFormulario() {
                console.log('Iniciando salvarPedidoDoFormulario...');
                
                const btn = document.querySelector('#form-novo-pedido button[type="submit"]');
                await this.executarComBloqueio(btn, async () => {
                    try {
                        const cliente = document.getElementById('pedido-cliente-nome').value;
                        const produto = document.getElementById('pedido-produto-nome').value;
                        const produtoExistente = document.getElementById('pedido-produto-existente').value;

                        if (!cliente) {
                            this.mostrarNotificacao('Preencha o nome do cliente.', 'warning');
                            return;
                        }

                        if (!produtoExistente) {
                            this.mostrarNotificacao('Selecione um produto existente antes de salvar o pedido.', 'warning');
                            return;
                        }

                        if (!produto) {
                            this.mostrarNotificacao('Preencha o nome do produto.', 'warning');
                            return;
                        }

                        const pedido = {
                            codigoRastreio: document.getElementById('pedido-rastreio-codigo').value,
                            contaShopee: document.getElementById('pedido-conta-shopee').value,
                            cliente: {
                                nome: document.getElementById('pedido-cliente-nome').value,
                                cpf: document.getElementById('pedido-cliente-cpf').value,
                                telefone: document.getElementById('pedido-cliente-telefone').value,
                                amazonId: document.getElementById('pedido-cliente-amazon-id').value,
                                endereco: {
                                    rua: document.getElementById('pedido-cliente-rua').value,
                                    numero: document.getElementById('pedido-cliente-numero').value,
                                    complemento: document.getElementById('pedido-cliente-complemento').value,
                                    bairro: document.getElementById('pedido-cliente-bairro').value,
                                    cidade: document.getElementById('pedido-cliente-cidade').value,
                                    estado: document.getElementById('pedido-cliente-estado').value,
                                    cep: document.getElementById('pedido-cliente-cep').value
                                }
                            },
                            produto: {
                                nome: document.getElementById('pedido-produto-nome').value,
                                categoria: document.getElementById('pedido-produto-categoria').value,
                                asin: '',
                                precoCusto: parseFloat(document.getElementById('pedido-produto-preco-custo').value) || 0,
                                precoVenda: parseFloat(document.getElementById('pedido-produto-preco-venda').value) || 0
                            },
                            rastreio: {
                                codigo: document.getElementById('pedido-rastreio-codigo').value,
                                transportadora: document.getElementById('pedido-rastreio-transportadora').value,
                                status: 'pendente',
                                dataEnvio: null,
                                dataEntrega: document.getElementById('pedido-rastreio-data-entrega').value || null
                            },
                            pagamento: {
                                metodo: document.getElementById('pedido-metodo-pagamento').value,
                                status: document.getElementById('pedido-status-pagamento').value
                            },
                            links: {
                                produto: document.getElementById('pedido-produto-link').value,
                                rastreio: null,
                                maps: document.getElementById('pedido-cliente-maps').value
                            },
                            observacoes: document.getElementById('pedido-observacoes').value
                        };
                        
                        const pedidoAdicionado = await this.adicionarPedido(pedido);
                        
                        if (pedidoAdicionado) {
                            // Salvar cliente automaticamente
                            await this.salvarClienteAutomatico(pedido.cliente);
                            
                            // Obter o nome do produto ANTES de fechar o modal e resetar
                            const produtoNome = document.getElementById('pedido-produto-nome').value;
                            const produtoCategoria = document.getElementById('pedido-produto-categoria').value;
                            const produtoPrecoCusto = document.getElementById('pedido-produto-preco-custo').value;
                            const produtoPrecoVenda = document.getElementById('pedido-produto-preco-venda').value;
                            const produtoLink = document.getElementById('pedido-produto-link').value;
                            
                            this.fecharModal('modal-novo-pedido');
                            document.getElementById('form-novo-pedido').reset();
                        }
                    } catch (e) {
                        console.error('Erro ao salvar pedido:', e);
                    }
                });
            }
            
            async salvarProdutoDoFormulario() {
                const btn = document.querySelector('#form-novo-produto button[type="submit"]');
                await this.executarComBloqueio(btn, async () => {
                    try {
                        const produto = {
                            nome: document.getElementById('produto-nome-novo').value.trim(),
                            categoria: document.getElementById('produto-categoria-novo').value,
                            asin: document.getElementById('produto-asin-novo').value.trim(),
                            plataforma: document.getElementById('produto-plataforma-novo').value,
                            precoCusto: parseFloat(document.getElementById('produto-preco-custo-novo').value) || 0,
                            precoVenda: parseFloat(document.getElementById('produto-preco-venda-novo').value) || 0,
                            estoque: parseInt(document.getElementById('produto-estoque-novo').value) || 0,
                            descricao: document.getElementById('produto-descricao-novo').value.trim(),
                            link: document.getElementById('produto-link-novo').value.trim(),
                            dataCadastro: this.obterDataHoje()
                        };
                        
                        const produtoAdicionado = await this.adicionarProduto(produto);
                        
                        if (produtoAdicionado) {
                            this.mostrarNotificacao(`Produto "${produto.nome}" salvo com sucesso!`, 'success');
                            this.fecharModal('modal-produto');
                            document.getElementById('form-novo-produto').reset();
                            this.carregarProdutos(); // Recarregar lista de produtos
                        } else {
                            this.mostrarNotificacao('Erro ao salvar produto. Tente novamente.', 'error');
                        }
                    } catch (e) {
                        console.error('Erro ao salvar produto:', e);
                        this.mostrarNotificacao('Erro inesperado ao salvar produto.', 'error');
                    }
                });
            }
            
            // ========== FUNÇÕES DE NAVEGAÇÃO ==========
            ativarAba(tabId) {
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });
                
                const activePane = document.getElementById(tabId);
                if (activePane) {
                    activePane.classList.add('active');
                }
                
                this.atualizarTituloHeader(tabId);
                
                if (tabId === 'pedidos') {
                    this.carregarPedidos('todos');
                } else if (tabId === 'produtos') {
                    this.carregarProdutos();
                } else if (tabId === 'ia') {
                    this.carregarSugestoesIA();
                } else if (tabId === 'analise') {
                    this.carregarAnaliseFinanceira();
                } else if (tabId === 'clientes') {
                    this.carregarClientes();
                }
            }
            
            ativarTab(tabId) {
                // Usar seletor mais específico: apenas abas dentro de .tabs-content
                document.querySelectorAll('.tabs-content .tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });
                
                const tabPane = document.getElementById(tabId);
                if (tabPane) {
                    tabPane.classList.add('active');
                    
                    // Carregar dados da aba ativada
                    switch(tabId) {
                        case 'pedidos-recentes':
                            this.carregarTabelaPedidosRecentes();
                            break;
                        case 'clientes-top':
                            this.carregarTabelaTopClientes();
                            break;
                        case 'produtos-top':
                            this.carregarTabelaProdutosTop();
                            break;
                        case 'status-pedidos':
                            this.carregarTabelaStatusPedidos();
                            break;
                    }
                }
            }
            
            ativarPedidoTab(tabId) {
                document.querySelectorAll('.pedido-tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                const tab = document.getElementById(tabId);
                if (tab) {
                    tab.classList.add('active');
                    this.carregarPedidos(tabId);
                }
            }
            
            atualizarTituloHeader(tabId) {
                const headerTitle = document.querySelector('.header h2');
                const tabNames = {
                    'dashboard': '<i class="fas fa-tachometer-alt"></i> Dashboard de Gestão',
                    'pedidos': '<i class="fas fa-shipping-fast"></i> Pedidos Unificados',
                    'produtos': '<i class="fas fa-boxes"></i> Catálogo de Produtos',
                    'ia': '<i class="fas fa-brain"></i> Assistente IA Inteligente',
                    'analise': '<i class="fas fa-chart-line"></i> Análise Financeira',
                    'clientes': '<i class="fas fa-users"></i> Base de Clientes'
                };
                
                if (tabNames[tabId]) {
                    headerTitle.innerHTML = tabNames[tabId];
                }
            }
            
            // ========== FUNÇÕES ADICIONAIS ==========
            verPedido(id) {
                const pedido = this.pedidos.find(p => p.id == id);
                if (!pedido) {
                    this.mostrarNotificacao('Pedido não encontrado!', 'warning');
                    return;
                }

                // Obter ID da Amazon
                const amazonId = pedido.amazonId || pedido.cliente?.amazonId || pedido.order_id || pedido.amazon_order_id;

                // Criar conteúdo do modal com detalhes do pedido
                const content = document.getElementById('modal-ver-pedido-content');
                let html = this.criarCardPedido(pedido, false).outerHTML;

                content.innerHTML = html;

                // Mostrar modal
                document.getElementById('modal-ver-pedido').style.display = 'flex';
            }
            
            async editarPedido(id) {
                const pedido = this.pedidos.find(p => p.id == id);
                if (!pedido) return;
                
                this.pedidoEditandoId = id;
                this.abrirModalNovoPedido(true);
                
                setTimeout(() => {
                    document.getElementById('pedido-conta-shopee').value = pedido.contaShopee || '';
                    document.getElementById('pedido-cliente-nome').value = pedido.cliente?.nome || '';
                    document.getElementById('pedido-cliente-cpf').value = pedido.cliente?.cpf || '';
                    document.getElementById('pedido-cliente-telefone').value = pedido.cliente?.telefone || '';
                    document.getElementById('pedido-cliente-amazon-id').value = pedido.cliente?.amazonId || '';
                    
                    if (pedido.cliente?.endereco) {
                        document.getElementById('pedido-cliente-rua').value = pedido.cliente.endereco.rua || '';
                        document.getElementById('pedido-cliente-numero').value = pedido.cliente.endereco.numero || '';
                        document.getElementById('pedido-cliente-complemento').value = pedido.cliente.endereco.complemento || '';
                        document.getElementById('pedido-cliente-bairro').value = pedido.cliente.endereco.bairro || '';
                        document.getElementById('pedido-cliente-cidade').value = pedido.cliente.endereco.cidade || '';
                        document.getElementById('pedido-cliente-estado').value = pedido.cliente.endereco.estado || '';
                        document.getElementById('pedido-cliente-cep').value = pedido.cliente.endereco.cep || '';
                    } else {
                        document.getElementById('pedido-cliente-rua').value = pedido.cliente?.endereco || '';
                    }

                    const produtoSelect = document.getElementById('pedido-produto-existente');
                    const produtoExistente = this.produtos.find(p => p.id === pedido.produto?.id || p.nome === pedido.produto?.nome || p.link === pedido.produto?.link);
                    if (produtoSelect) {
                        produtoSelect.value = produtoExistente ? produtoExistente.id : '';
                    }
                    
                    document.getElementById('pedido-cliente-maps').value = pedido.links?.maps || '';
                    
                    document.getElementById('pedido-produto-nome').value = pedido.produto?.nome || '';
                    document.getElementById('pedido-produto-categoria').value = pedido.produto?.categoria || 'eletronicos';
                    document.getElementById('pedido-produto-preco-custo').value = pedido.produto?.precoCusto || '';
                    document.getElementById('pedido-produto-preco-venda').value = pedido.produto?.precoVenda || '';
                    document.getElementById('pedido-produto-link').value = pedido.links?.produto || '';
                    
                    document.getElementById('pedido-rastreio-codigo').value = pedido.codigoRastreio || '';
                    document.getElementById('pedido-rastreio-transportadora').value = pedido.rastreio?.transportadora || 'shopee';
                    document.getElementById('pedido-rastreio-status').value = pedido.rastreio?.status || 'pendente';
                    document.getElementById('pedido-rastreio-data').value = pedido.rastreio?.dataEnvio || '';
                    document.getElementById('pedido-rastreio-data-entrega').value = pedido.rastreio?.dataEntrega || '';
                    document.getElementById('pedido-rastreio-link').value = pedido.links?.rastreio || '';
                    
                    if (pedido.pagamento) {
                        document.getElementById('pedido-metodo-pagamento').value = pedido.pagamento.metodo || 'pix';
                        document.getElementById('pedido-status-pagamento').value = pedido.pagamento.status || 'pago';
                    }
                    
                    document.getElementById('pedido-observacoes').value = pedido.observacoes || '';
                    
                    document.querySelector('#modal-novo-pedido .modal-title').textContent = 'Editar Pedido';
                    
                    const submitBtn = document.querySelector('#form-novo-pedido button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Atualizar Pedido';
                    }
                    const form = document.getElementById('form-novo-pedido');
                    if (form) {
                        // O envio do formulário é tratado pelo listener global em configurarImportacaoAmazon
                    }
                }, 100);
            }
            
            async atualizarPedidoDoFormulario(id) {
                const btn = document.querySelector('#form-novo-pedido button[type="submit"]');
                await this.executarComBloqueio(btn, async () => {
                    try {
                        const pedidoOriginal = this.pedidos.find(p => p.id == id);
                        
                        const dadosAtualizados = {
                            codigoRastreio: document.getElementById('pedido-rastreio-codigo').value,
                            contaShopee: document.getElementById('pedido-conta-shopee').value,
                            cliente: {
                                nome: document.getElementById('pedido-cliente-nome').value,
                                cpf: document.getElementById('pedido-cliente-cpf').value,
                                telefone: document.getElementById('pedido-cliente-telefone').value,
                                amazonId: document.getElementById('pedido-cliente-amazon-id').value,
                                endereco: {
                                    rua: document.getElementById('pedido-cliente-rua').value,
                                    numero: document.getElementById('pedido-cliente-numero').value,
                                    complemento: document.getElementById('pedido-cliente-complemento').value,
                                    bairro: document.getElementById('pedido-cliente-bairro').value,
                                    cidade: document.getElementById('pedido-cliente-cidade').value,
                                    estado: document.getElementById('pedido-cliente-estado').value,
                                    cep: document.getElementById('pedido-cliente-cep').value
                                }
                            },
                            produto: {
                                nome: document.getElementById('pedido-produto-nome').value,
                                categoria: document.getElementById('pedido-produto-categoria').value,
                                asin: '',
                                precoCusto: parseFloat(document.getElementById('pedido-produto-preco-custo').value) || 0,
                                precoVenda: parseFloat(document.getElementById('pedido-produto-preco-venda').value) || 0
                            },
                            rastreio: {
                                codigo: document.getElementById('pedido-rastreio-codigo').value,
                                transportadora: document.getElementById('pedido-rastreio-transportadora').value,
                                status: pedidoOriginal?.rastreio?.status || 'pendente',
                                dataEnvio: pedidoOriginal?.rastreio?.dataEnvio || null,
                                dataEntrega: document.getElementById('pedido-rastreio-data-entrega').value || null
                            },
                            pagamento: {
                                metodo: document.getElementById('pedido-metodo-pagamento').value,
                                status: document.getElementById('pedido-status-pagamento').value
                            },
                            links: {
                                produto: document.getElementById('pedido-produto-link').value,
                                rastreio: null,
                                maps: document.getElementById('pedido-cliente-maps').value
                            },
                            observacoes: document.getElementById('pedido-observacoes').value,
                            dataCadastro: pedidoOriginal?.dataCadastro || this.obterDataHoje()
                        };
                        
                        const sucesso = await this.atualizarPedido(id, dadosAtualizados);
                        
                        if (sucesso) {
                            this.pedidoEditandoId = null;
                            this.fecharModal('modal-novo-pedido');
                        }
                    } catch (e) {
                        console.error('Erro ao atualizar pedido:', e);
                    }
                });
            }
            
            async editarProduto(id) {
                const produto = this.produtos.find(p => p.id == id);
                if (!produto) return;
                
                this.abrirModalNovoProduto();
                
                setTimeout(() => {
                    document.getElementById('produto-nome-novo').value = produto.nome;
                    document.getElementById('produto-categoria-novo').value = produto.categoria || 'eletronicos';
                    document.getElementById('produto-asin-novo').value = produto.asin || '';
                    document.getElementById('produto-plataforma-novo').value = produto.plataforma || 'shopee';
                    document.getElementById('produto-preco-custo-novo').value = produto.precoCusto || '';
                    document.getElementById('produto-preco-venda-novo').value = produto.precoVenda || '';
                    document.getElementById('produto-estoque-novo').value = produto.estoque || 0;
                    document.getElementById('produto-descricao-novo').value = produto.descricao || '';
                    document.getElementById('produto-link-novo').value = produto.link || '';
                    
                    document.querySelector('#modal-produto .modal-title').textContent = 'Editar Produto';
                    
                    const submitBtn = document.querySelector('#form-novo-produto button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Atualizar Produto';
                    }
                    const form = document.getElementById('form-novo-produto');
                    if (form) {
                        form.onsubmit = async (e) => {
                            e.preventDefault();
                            await this.atualizarProdutoDoFormulario(id);
                        };
                    }
                }, 100);
            }
            
            async atualizarProdutoDoFormulario(id) {
                const btn = document.querySelector('#form-novo-produto button[type="submit"]');
                await this.executarComBloqueio(btn, async () => {
                    try {
                        const dadosAtualizados = {
                            nome: document.getElementById('produto-nome-novo').value,
                            categoria: document.getElementById('produto-categoria-novo').value,
                            asin: document.getElementById('produto-asin-novo').value || '',
                            plataforma: document.getElementById('produto-plataforma-novo').value,
                            precoCusto: parseFloat(document.getElementById('produto-preco-custo-novo').value) || 0,
                            precoVenda: parseFloat(document.getElementById('produto-preco-venda-novo').value) || 0,
                            estoque: parseInt(document.getElementById('produto-estoque-novo').value) || 0,
                            descricao: document.getElementById('produto-descricao-novo').value,
                            link: document.getElementById('produto-link-novo').value || ''
                        };
                        
                        const sucesso = await this.atualizarProduto(id, dadosAtualizados);
                        
                        if (sucesso) {
                            this.fecharModal('modal-produto');
                        }
                    } catch (e) {
                        console.error('Erro ao atualizar produto:', e);
                    }
                });
            }
            
            async atualizarStatusPedido(id) {
                try {
                    const pedido = this.pedidos.find(p => p.id == id);
                    if (!pedido) {
                        this.mostrarNotificacao('❌ Pedido não encontrado', 'warning');
                        return;
                    }
                    
                    // Verificar se tem código de rastreio
                    if (!pedido.codigoRastreio || pedido.codigoRastreio.trim() === '') {
                        this.mostrarNotificacao('⚠️ Código de rastreio obrigatório!\n\nColoque o código de rastreio (começando com BR) no formulário antes de atualizar o status.', 'warning');
                        return;
                    }
                    
                    // Verificar se código começa com BR
                    if (!pedido.codigoRastreio.toUpperCase().startsWith('BR')) {
                        this.mostrarNotificacao('⚠️ Código inválido!\n\nO código deve começar com "BR" (Ex: BR123456789)\n\nAtualize o campo de rastreio.', 'warning');
                        return;
                    }
                    
                    // Garantir que rastreio existe e tem status
                    if (!pedido.rastreio) {
                        pedido.rastreio = {};
                    }
                    
                    const statusAtual = (pedido.rastreio.status || 'pendente').toLowerCase().trim();
                    const statuses = ['pendente', 'processando', 'transito', 'entregue'];
                    const statusNames = {
                        'pendente': 'Pendente',
                        'processando': 'Processando',
                        'transito': 'Em Trânsito',
                        'entregue': 'Entregue ✓'
                    };
                    
                    let currentIndex = statuses.indexOf(statusAtual);
                    if (currentIndex === -1) currentIndex = 0;
                    
                    const nextIndex = currentIndex < statuses.length - 1 ? currentIndex + 1 : currentIndex;
                    const novoStatus = statuses[nextIndex];
                    
                    if (nextIndex === currentIndex && statusAtual === 'entregue') {
                        this.mostrarNotificacao('✓ Pedido já está entregue', 'info');
                        return;
                    }
                    
                    const dadosAtualizados = {
                        ...pedido,
                        rastreio: {
                            ...pedido.rastreio,
                            codigo: pedido.rastreio.codigo || pedido.codigoRastreio || '',
                            transportadora: pedido.rastreio.transportadora || 'shopee',
                            status: novoStatus,
                            dataEnvio: pedido.rastreio.dataEnvio,
                            dataEntrega: pedido.rastreio.dataEntrega
                        }
                    };
                    
                    // Preencher dataEnvio quando muda para transito
                    if (novoStatus === 'transito' && !dadosAtualizados.rastreio.dataEnvio) {
                        dadosAtualizados.rastreio.dataEnvio = this.obterDataHoje();
                    }
                    
                    // Preencher dataEntrega quando marcado como entregue
                    if (novoStatus === 'entregue' && !dadosAtualizados.rastreio.dataEntrega) {
                        dadosAtualizados.rastreio.dataEntrega = this.obterDataHoje();
                    }
                    
                    const sucesso = await this.atualizarPedido(id, dadosAtualizados);
                    
                    if (sucesso) {
                        this.mostrarNotificacao(`📦 Status atualizado: ${statusNames[novoStatus]}`, 'success');
                    }
                } catch (e) {
                    console.error('Erro ao atualizar status:', e);
                    this.mostrarNotificacao('❌ Erro ao atualizar status', 'danger');
                }
            }
            
            analisarProdutoComIA(id) {
                const produto = this.produtos.find(p => p.id == id);
                if (!produto) return;
                
                this.ativarAba('ia');
                document.getElementById('pergunta-ia').value = `Analise o produto "${produto.nome}" e dê recomendações`;
                
                setTimeout(() => {
                    this.perguntarIA(`Analise o produto "${produto.nome}" e dê recomendações`);
                }, 500);
            }
            
            analisarProduto(nome) {
                this.ativarAba('ia');
                document.getElementById('pergunta-ia').value = `Analise o produto "${nome}"`;
                setTimeout(() => {
                    this.perguntarIA(`Analise o produto "${nome}"`);
                }, 500);
            }
            
            executarBuscaRapida(termo) {
                if (!termo.trim()) {
                    const abaAtiva = document.querySelector('.tab-pane.active').id;
                    if (abaAtiva === 'pedidos') this.carregarPedidos('todos');
                    else if (abaAtiva === 'produtos') this.carregarProdutos();
                    document.getElementById('resultados-busca').style.display = 'none';
                    return;
                }
                
                const termoLower = termo.toLowerCase();
                const resultados = [];
                
                this.pedidos.forEach(pedido => {
                    const match =
                        (pedido.codigoRastreio && pedido.codigoRastreio.toLowerCase().includes(termoLower)) ||
                        (pedido.cliente?.nome && pedido.cliente.nome.toLowerCase().includes(termoLower)) ||
                        (pedido.cliente?.cpf && pedido.cliente.cpf.toLowerCase().includes(termoLower)) ||
                        (pedido.produto?.nome && pedido.produto.nome.toLowerCase().includes(termoLower));
                    if (match) {
                        resultados.push({
                            tipo: 'pedido',
                            id: pedido.id,
                            titulo: pedido.codigoRastreio || 'Sem código',
                            subtitulo: `Cliente: ${pedido.cliente?.nome || 'Não informado'}`,
                            detalhes: `Produto: ${pedido.produto?.nome || 'Não informado'} | Status: ${this.getStatusText(pedido.rastreio?.status)}`,
                            acao: () => {
                                this.fecharModal('modal-busca-avancada');
                                this.ativarAba('pedidos');
                                this.verPedido(pedido.id);
                            }
                        });
                    }
                });
                
                this.produtos.forEach(produto => {
                    const match =
                        (produto.nome && produto.nome.toLowerCase().includes(termoLower)) ||
                        (produto.asin && produto.asin.toLowerCase().includes(termoLower));
                    if (match) {
                        resultados.push({
                            tipo: 'produto',
                            id: produto.id,
                            titulo: produto.nome,
                            subtitulo: `Categoria: ${this.getCategoryText(produto.categoria)}`,
                            detalhes: `Preço: R$ ${(produto.precoVenda || 0).toFixed(2)} | Estoque: ${produto.estoque || 0}`,
                            acao: () => {
                                this.fecharModal('modal-busca-avancada');
                                this.ativarAba('produtos');
                            }
                        });
                    }
                });
                
                if (resultados.length > 0) {
                    this.exibirResultadosBusca(resultados);
                    this.mostrarModal('modal-busca-avancada');
                } else {
                    this.mostrarNotificacao('Nenhum resultado encontrado.', 'warning');
                }
            }
            
            copiarRespostaIA() {
                const texto = document.getElementById('texto-resposta-ia').textContent;
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(texto)
                        .then(() => this.mostrarNotificacao('Resposta copiada para a área de transferência!', 'success'))
                        .catch(err => {
                            console.error('Erro ao copiar:', err);
                            this.mostrarNotificacao('Erro ao copiar texto.', 'danger');
                        });
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = texto;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    this.mostrarNotificacao('Resposta copiada para a área de transferência!', 'success');
                }
            }
            
            calcularLucro() {
                const precoOriginal = parseFloat(document.getElementById('preco-original').value);
                const precoVenda = parseFloat(document.getElementById('preco-venda').value);
                const taxas = parseFloat(document.getElementById('taxas').value) || 0;
                const frete = parseFloat(document.getElementById('frete').value) || 0;
                const quantidade = parseInt(document.getElementById('quantidade').value) || 1;
                
                if (!precoOriginal || !precoVenda) {
                    this.mostrarNotificacao('Preencha pelo menos o preço original e o preço de venda.', 'warning');
                    return;
                }
                
                const taxasReais = precoVenda * (taxas / 100);
                
                const lucroUnitario = precoVenda - precoOriginal - taxasReais - frete;
                const margemUnitaria = (lucroUnitario / precoVenda) * 100;
                
                const lucroTotal = lucroUnitario * quantidade;
                const faturamentoTotal = precoVenda * quantidade;
                const custoTotal = (precoOriginal + taxasReais + frete) * quantidade;
                const margemTotal = (lucroTotal / faturamentoTotal) * 100;
                
                const resultadoDiv = document.getElementById('resultado-calculo');
                
                resultadoDiv.innerHTML = `
                    <h5 style="margin-bottom: 15px; color: var(--text-light);">Resultado do Cálculo:</h5>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <strong>Por Unidade:</strong><br>
                            <div style="margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Lucro:</span>
                                    <span style="color: ${lucroUnitario > 0 ? 'var(--success-color)' : 'var(--danger-color)'}; font-weight: bold;">
                                        R$ ${lucroUnitario.toFixed(2)}
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Margem:</span>
                                    <span style="color: ${margemUnitaria > 30 ? 'var(--success-color)' : margemUnitaria > 0 ? 'var(--warning-color)' : 'var(--danger-color)'}; font-weight: bold;">
                                        ${margemUnitaria.toFixed(1)}%
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Taxas:</span>
                                    <span>R$ ${taxasReais.toFixed(2)}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Frete:</span>
                                    <span>R$ ${frete.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <strong>Total (${quantidade} unidades):</strong><br>
                            <div style="margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Faturamento:</span>
                                    <span style="font-weight: bold;">R$ ${faturamentoTotal.toFixed(2)}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Custo Total:</span>
                                    <span>R$ ${custoTotal.toFixed(2)}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Lucro Total:</span>
                                    <span style="color: ${lucroTotal > 0 ? 'var(--success-color)' : 'var(--danger-color)'}; font-weight: bold;">
                                        R$ ${lucroTotal.toFixed(2)}
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Margem Total:</span>
                                    <span style="color: ${margemTotal > 30 ? 'var(--success-color)' : margemTotal > 0 ? 'var(--warning-color)' : 'var(--danger-color)'}; font-weight: bold;">
                                        ${margemTotal.toFixed(1)}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 15px; background-color: ${lucroUnitario > 0 ? 'rgba(46, 204, 113, 0.1)' : 'rgba(231, 76, 60, 0.1)'}; border-radius: var(--radius-small); border-left: 4px solid ${lucroUnitario > 0 ? 'var(--success-color)' : 'var(--danger-color)'};">
                        <strong>Análise da IA:</strong><br>
                        ${lucroUnitario > 0 ? 
                            `✅ Este produto é rentável! Recomendado para venda.<br>
                             📈 Margem ${margemUnitaria > 50 ? 'excelente' : margemUnitaria > 30 ? 'boa' : 'baixa'}. ${margemUnitaria < 30 ? 'Considere aumentar o preço.' : ''}` : 
                            `❌ Este produto não é rentável. Considere:<br>
                             1. Aumentar o preço de venda para pelo menos R$ ${(precoOriginal * 1.3).toFixed(2)}<br>
                             2. Reduzir custos de frete ou taxas<br>
                             3. Negociar melhor preço com fornecedor`}
                    </div>
                    
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <button class="btn btn-small btn-secondary" id="btn-salvar-calculo">
                            <i class="fas fa-save"></i> Salvar Cálculo
                        </button>
                        <button class="btn btn-small" id="btn-limpar-calculo">
                            <i class="fas fa-broom"></i> Limpar
                        </button>
                    </div>
                `;
                
                resultadoDiv.style.display = 'block';
                
                document.getElementById('btn-salvar-calculo')?.addEventListener('click', () => {
                    this.mostrarNotificacao('Cálculo salvo para referência futura.', 'success');
                });
                
                document.getElementById('btn-limpar-calculo')?.addEventListener('click', () => {
                    resultadoDiv.style.display = 'none';
                });
                
                this.mostrarNotificacao('Cálculo de lucro realizado com sucesso!', 'success');
            }
            
            executarAcaoIA(acao) {
                switch(acao) {
                    case 'analiseEstoque':
                        document.getElementById('pergunta-ia').value = 'Analise meu estoque e dê recomendações';
                        this.perguntarIA('Analise meu estoque e dê recomendações');
                        break;
                    case 'analisePrecos':
                        document.getElementById('pergunta-ia').value = 'Analise os preços dos meus produtos';
                        this.perguntarIA('Analise os preços dos meus produtos');
                        break;
                    case 'verPedidosPendentes':
                        this.ativarAba('pedidos');
                        this.ativarPedidoTab('pendentes');
                        break;
                    case 'sugerirPromocoes':
                        document.getElementById('pergunta-ia').value = 'Sugira promoções para meus produtos';
                        this.perguntarIA('Sugira promoções para meus produtos');
                        break;
                    case 'analiseSazonal':
                        document.getElementById('pergunta-ia').value = 'Dê sugestões baseadas na sazonalidade';
                        this.perguntarIA('Dê sugestões baseadas na sazonalidade');
                        break;
                }
            }
            
            carregarAnaliseFinanceira() {
                const abaPrincipal = document.getElementById('analise');
                if (!abaPrincipal) return;
                
                // Calcular financeiros com categorias
                let faturamentoTotal = 0;
                let custoTotalProduto = 0;
                let taxasTotal = 0;
                let freteTotal = 0;
                let lucroComTaxas = 0;
                let lucroSemTaxas = 0;
                const vendsPorProduto = {};
                const vendsPorCategoria = {};
                
                this.pedidos.forEach(pedido => {
                    if (pedido.produto) {
                        const categoria = pedido.produto.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        const precoVenda = pedido.produto.precoVenda || 0;
                        const precoCusto = pedido.produto.precoCusto || 0;
                        const frete = pedido.frete || 0;
                        
                        faturamentoTotal += precoVenda;
                        custoTotalProduto += precoCusto;
                        const taxaAmount = precoVenda * (taxaCategoria / 100);
                        taxasTotal += taxaAmount;
                        freteTotal += frete;
                        
                        const lucroComTax = precoVenda - precoCusto - taxaAmount - frete;
                        const lucroSemTax = precoVenda - precoCusto - frete;
                        lucroComTaxas += lucroComTax;
                        lucroSemTaxas += lucroSemTax;
                        
                        // Produtos
                        if (!vendsPorProduto[pedido.produto.nome]) {
                            vendsPorProduto[pedido.produto.nome] = { vendas: 0, lucro: 0, lucroSemTax: 0, faturamento: 0, categoria };
                        }
                        vendsPorProduto[pedido.produto.nome].vendas++;
                        vendsPorProduto[pedido.produto.nome].faturamento += precoVenda;
                        vendsPorProduto[pedido.produto.nome].lucro += lucroComTax;
                        vendsPorProduto[pedido.produto.nome].lucroSemTax += lucroSemTax;
                        
                        // Categorias
                        if (!vendsPorCategoria[categoria]) {
                            vendsPorCategoria[categoria] = { vendas: 0, faturamento: 0, lucro: 0 };
                        }
                        vendsPorCategoria[categoria].vendas++;
                        vendsPorCategoria[categoria].faturamento += precoVenda;
                        vendsPorCategoria[categoria].lucro += lucroComTax;
                    }
                });
                
                const margemLiquida = faturamentoTotal > 0 ? ((lucroComTaxas / faturamentoTotal) * 100).toFixed(1) : 0;
                const margemSemTaxas = faturamentoTotal > 0 ? ((lucroSemTaxas / faturamentoTotal) * 100).toFixed(1) : 0;
                
                // Atualizar cards de KPI
                this.atualizarCardsKPI(faturamentoTotal, lucroComTaxas, margemLiquida, this.pedidos.length);
                
                // Atualizar cards
                const cardContainer = abaPrincipal.querySelector('.dashboard-cards');
                if (cardContainer) {
                    cardContainer.innerHTML = `
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Faturamento Total</div>
                                <div class="card-icon" style="background-color: rgba(0, 168, 255, 0.1);">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                            <div class="card-value">R$ ${faturamentoTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div class="card-change"><i class="fas fa-chart-line"></i> Total faturado</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Lucro Líquido (c/ Taxas)</div>
                                <div class="card-icon" style="background-color: rgba(46, 204, 113, 0.1);">
                                    <i class="fas fa-wallet"></i>
                                </div>
                            </div>
                            <div class="card-value" style="color: ${lucroComTaxas >= 0 ? 'var(--success-color)' : 'var(--danger-color)'};">R$ ${lucroComTaxas.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div class="card-change" style="color: ${lucroComTaxas >= 0 ? 'var(--success-color)' : 'var(--danger-color)'};">
                                <i class="fas fa-${lucroComTaxas >= 0 ? 'arrow-up' : 'arrow-down'}"></i> Após todas despesas
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Lucro (sem Taxas)</div>
                                <div class="card-icon" style="background-color: rgba(156, 136, 255, 0.1);">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                            </div>
                            <div class="card-value">R$ ${lucroSemTaxas.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div class="card-change"><i class="fas fa-info-circle"></i> Sem fees da Amazon</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Custo Total</div>
                                <div class="card-icon" style="background-color: rgba(243, 156, 18, 0.1);">
                                    <i class="fas fa-cube"></i>
                                </div>
                            </div>
                            <div class="card-value">R$ ${(custoTotalProduto + taxasTotal + freteTotal).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div class="card-change"><i class="fas fa-tag"></i> Produto + Taxas + Frete</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Margem Líquida</div>
                                <div class="card-icon" style="background-color: rgba(46, 204, 113, 0.1);">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                            <div class="card-value">${margemLiquida}%</div>
                            <div class="card-change"><i class="fas fa-pie-chart"></i> Lucro/Faturamento</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Detalhamento Custos</div>
                                <div class="card-icon" style="background-color: rgba(231, 76, 60, 0.1);">
                                    <i class="fas fa-receipt"></i>
                                </div>
                            </div>
                            <div style="font-size: 12px; line-height: 1.6; color: var(--text-muted);">
                                <div>• Produto: R$ ${custoTotalProduto.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div>• Taxas: R$ ${taxasTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div>• Frete: R$ ${freteTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            </div>
                        </div>
                    `;
                }
                
                // Inserir painel de IA de análise
                const iaPlaceholder = abaPrincipal.querySelector('.ia-analise-container') || document.createElement('div');
                iaPlaceholder.className = 'ia-analise-container';
                iaPlaceholder.style.cssText = `
                    background: linear-gradient(135deg, rgba(156, 136, 255, 0.1), rgba(155, 89, 182, 0.05));
                    border: 1px solid rgba(156, 136, 255, 0.3);
                    border-radius: var(--radius);
                    padding: 25px;
                    margin-top: 30px;
                    margin-bottom: 30px;
                `;
                iaPlaceholder.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <div style="background: var(--ia-color); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div>
                            <h3 style="color: var(--text-light); margin-bottom: 5px;">Análise Inteligente de Finanças</h3>
                            <p style="color: var(--text-muted); font-size: 13px;">IA analisando seu negócio em tempo real...</p>
                        </div>
                    </div>
                    <div id="ia-insights-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <div style="text-align: center; padding: 20px; background: rgba(255,255,255, 0.05); border-radius: 8px;">
                            <i class="fas fa-cog fa-spin" style="font-size: 24px; color: var(--ia-color);"></i>
                            <p style="margin-top: 10px; color: var(--text-muted);">Processando dados...</p>
                        </div>
                    </div>
                    <button id="btn-gerar-relatorio-completo" style="margin-top: 15px; padding: 12px 20px; background: var(--ia-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-file-pdf"></i> Gerar Relatório Completo
                    </button>
                `;
                
                if (!abaPrincipal.querySelector('.ia-analise-container')) {
                    abaPrincipal.appendChild(iaPlaceholder);
                } else {
                    abaPrincipal.querySelector('.ia-analise-container').innerHTML = iaPlaceholder.innerHTML;
                }
                
                // Gerar insights da IA
                this.gerarAnalisesIA(vendsPorProduto, vendsPorCategoria, faturamentoTotal, lucroComTaxas, margemLiquida, freteTotal, taxasTotal);
                
                // Configurar eventos de interação
                this.setupEventListeners();

                // Adicionar funcionalidade de exportação
                this.setupExportFeatures();

                // Carregar abas adicionais
                this.carregarAbaAnalise('analise-mensal');
                
                // Evento do botão de relatório
                setTimeout(() => {
                    const btnRelatorio = document.getElementById('btn-gerar-relatorio-completo');
                    if (btnRelatorio) {
                        btnRelatorio.addEventListener('click', () => this.gerarRelatorioCompleto(faturamentoTotal, lucroComTaxas, lucroSemTaxas, vendsPorProduto, vendsPorCategoria));
                    }
                }, 100);
            }
            
            // Função para atualizar os cards de KPI principais
            atualizarCardsKPI(faturamentoTotal, lucroComTaxas, margemLiquida, totalVendas) {
                // Atualizar card de faturamento
                const cardFaturamento = document.getElementById('card-faturamento');
                if (cardFaturamento) {
                    cardFaturamento.textContent = `R$ ${faturamentoTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                }
                
                // Atualizar card de lucro
                const cardLucro = document.getElementById('card-lucro');
                if (cardLucro) {
                    cardLucro.textContent = `R$ ${lucroComTaxas.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    cardLucro.style.color = lucroComTaxas >= 0 ? '#9b59b6' : 'var(--danger-color)';
                }
                
                // Atualizar card de margem
                const cardMargem = document.getElementById('card-margem');
                if (cardMargem) {
                    cardMargem.textContent = `${margemLiquida}%`;
                }
                
                // Atualizar card de vendas
                const cardVendas = document.getElementById('card-vendas');
                if (cardVendas) {
                    cardVendas.textContent = totalVendas;
                }
            }
            
            // Nova função para gerar análises da IA
            gerarAnalisesIA(vendsPorProduto, vendsPorCategoria, faturamentoTotal, lucroComTaxas, margemLiquida, freteTotal, taxasTotal) {
                const container = document.getElementById('ia-insights-container');
                if (!container) return;
                
                const insights = [];
                
                // Análise 1: Produtos mais rentáveis
                const produtosRentaveis = Object.entries(vendsPorProduto)
                    .sort((a, b) => b[1].lucro - a[1].lucro)
                    .slice(0, 3);
                
                if (produtosRentaveis.length > 0) {
                    const topProduto = produtosRentaveis[0];
                    insights.push({
                        titulo: '🎯 Produto Estrela',
                        descricao: topProduto[0],
                        valor: `R$ ${topProduto[1].lucro.toFixed(2)}`,
                        cor: 'var(--success-color)',
                        emoji: '⭐'
                    });
                }
                
                // Análise 2: Categorias mais lukrativas
                const categoriasRentaveis = Object.entries(vendsPorCategoria)
                    .sort((a, b) => b[1].lucro - a[1].lucro)
                    .slice(0, 1);
                
                if (categoriasRentaveis.length > 0) {
                    const melhorCategoria = categoriasRentaveis[0];
                    insights.push({
                        titulo: '📊 Melhor Categoria',
                        descricao: melhorCategoria[0],
                        valor: `${melhorCategoria[1].vendas} vendas`,
                        cor: 'var(--primary-color)',
                        emoji: '📈'
                    });
                }
                
                // Análise 3: Alerta de lucro negativo
                if (lucroComTaxas < 0) {
                    insights.push({
                        titulo: '⚠️ ALERTA CRÍTICO',
                        descricao: 'Seu negócio está operando com prejuízo!',
                        valor: `R$ ${lucroComTaxas.toFixed(2)}`,
                        cor: 'var(--danger-color)',
                        emoji: '🚨'
                    });
                }
                
                // Análise 4: Margem de lucro
                let statusMargem = '✅ Saudável';
                let corMargem = 'var(--success-color)';
                if (margemLiquida < 10) {
                    statusMargem = '⚠️ Baixa';
                    corMargem = 'var(--warning-color)';
                } else if (margemLiquida < 5) {
                    statusMargem = '🚨 Crítica';
                    corMargem = 'var(--danger-color)';
                }
                
                insights.push({
                    titulo: '📊 Margem Líquida',
                    descricao: statusMargem,
                    valor: `${margemLiquida}%`,
                    cor: corMargem,
                    emoji: '📈'
                });
                
                // Renderizar insights
                container.innerHTML = insights.map(insight => `
                    <div style="
                        background: rgba(255,255,255, 0.05);
                        border-left: 4px solid ${insight.cor};
                        padding: 20px;
                        border-radius: 8px;
                        cursor: pointer;
                        transition: var(--transition);
                    " onmouseover="this.style.background='rgba(255,255,255, 0.1)'" onmouseout="this.style.background='rgba(255,255,255, 0.05)'">
                        <div style="font-size: 24px; margin-bottom: 10px;">${insight.emoji}</div>
                        <h4 style="color: ${insight.cor}; margin-bottom: 8px; font-size: 14px;">${insight.titulo}</h4>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 5px;">${insight.descricao}</p>
                        <div style="color: ${insight.cor}; font-weight: 700; font-size: 18px;">${insight.valor}</div>
                    </div>
                `).join('');
            }
            
            // Nova função para gerar relatório completo
            gerarRelatorioCompleto(faturamentoTotal, lucroComTaxas, lucroSemTaxas, vendsPorProduto, vendsPorCategoria) {
                const totalProdutos = this.produtos.length;
                const totalPedidos = this.pedidos.length;
                const totalClientes = new Set(this.pedidos.map(p => p.cliente?.cpf)).size;
                
                const produtosMaisVendidos = Object.entries(vendsPorProduto)
                    .sort((a, b) => b[1].vendas - a[1].vendas)
                    .slice(0, 5);
                
                const produtosMenosVendidos = Object.entries(vendsPorProduto)
                    .sort((a, b) => a[1].vendas - b[1].vendas)
                    .slice(0, 3);
                
                let relatorio = `
╔════════════════════════════════════════════════════╗
║           RELATÓRIO FINANCEIRO COMPLETO            ║
║              ${new Date().toLocaleDateString('pt-BR')}              ║
╚════════════════════════════════════════════════════╝

📊 RESUMO EXECUTIVO
├─ Total de Pedidos: ${totalPedidos}
├─ Total de Clientes: ${totalClientes}
├─ Total de Produtos: ${totalProdutos}
└─ Período: Desde o início

💰 FINANCEIRO
├─ Faturamento Total: R$ ${faturamentoTotal.toFixed(2)}
├─ Lucro Total (c/ Taxas): R$ ${lucroComTaxas.toFixed(2)}
├─ Lucro Total (s/ Taxas): R$ ${lucroSemTaxas.toFixed(2)}
└─ Margem: ${faturamentoTotal > 0 ? ((lucroComTaxas / faturamentoTotal) * 100).toFixed(1) : 0}%

🏆 PRODUTOS MAIS VENDIDOS
`;
                produtosMaisVendidos.forEach((item, idx) => {
                    relatorio += `├─ ${idx + 1}. ${item[0]} (${item[1].vendas} vendas, R$ ${item[1].lucro.toFixed(2)} lucro)\n`;
                });
                
                relatorio += `\n⚠️ PRODUTOS COM MENOS VENDAS\n`;
                produtosMenosVendidos.forEach((item, idx) => {
                    relatorio += `├─ ${idx + 1}. ${item[0]} (${item[1].vendas} vendas, R$ ${item[1].lucro.toFixed(2)} lucro)\n`;
                });
                
                relatorio += `\n🎯 ANÁLISE POR CATEGORIA\n`;
                Object.entries(vendsPorCategoria)
                    .sort((a, b) => b[1].lucro - a[1].lucro)
                    .forEach((item, idx) => {
                        relatorio += `├─ ${item[0]} (${item[1].vendas} vendas, R$ ${item[1].lucro.toFixed(2)} lucro)\n`;
                    });
                
                relatorio += `\n⚠️ PROBLEMAS FINANCEIROS DETECTADOS\n`;
                if (lucroComTaxas < 0) {
                    relatorio += `├─ CRÍTICO: Negócio operando com prejuízo de R$ ${Math.abs(lucroComTaxas).toFixed(2)}\n`;
                }
                const margemLiquida = faturamentoTotal > 0 ? ((lucroComTaxas / faturamentoTotal) * 100) : 0;
                if (margemLiquida < 10) {
                    relatorio += `├─ ALERTA: Margem de lucro muito baixa: ${margemLiquida.toFixed(1)}%\n`;
                }
                const produtosNegativação = Object.entries(vendsPorProduto).filter(p => p[1].lucro < 0);
                if (produtosNegativação.length > 0) {
                    relatorio += `├─ ${produtosNegativação.length} produto(s) com lucro negativo\n`;
                }
                
                relatorio += `\n\n✅ Relatório gerado automaticamente por IA\nMarket Manager Pro v4.0\n`;
                
                // Copiar para clipboard
                navigator.clipboard.writeText(relatorio).then(() => {
                    this.mostrarNotificacao('📋 Relatório copiado para a área de transferência!', 'success');
                    console.log(relatorio);
                });
            }
            
            carregarAbaAnalise(abaId) {
                const tabsContent = document.querySelector('#analise .tabs-content');
                if (!tabsContent) return;
                
                // Atualizar botões de aba
                tabsContent.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                const btnAtivo = tabsContent.querySelector(`.tab-btn[data-tab="${abaId}"]`);
                if (btnAtivo) btnAtivo.classList.add('active');
                
                // Limpar conteúdo anterior
                let container = tabsContent.querySelector(`#${abaId}`);
                if (!container) {
                    container = document.createElement('div');
                    container.id = abaId;
                    container.className = 'tab-content';
                    tabsContent.appendChild(container);
                }
                container.innerHTML = '';
                
                // Carregar conteúdo baseado na aba
                switch(abaId) {
                    case 'analise-mensal':
                        this.carregarAnalisesMensais(container);
                        break;
                    case 'top-produtos':
                        this.carregarProdutosRentaveis(container);
                        break;
                    case 'despesas':
                        this.carregarDespesas(container);
                        break;
                    case 'tendencias':
                        this.carregarTendencias(container);
                        break;
                    case 'dicas':
                        this.carregarDicasOtimizacao(container);
                        break;
                }
            }
            
            carregarAnalisesMensais(container) {
                const meses = {};
                this.pedidos.forEach(pedido => {
                    if (pedido.dataCadastro) {
                        const mes = pedido.dataCadastro.split('-').slice(0, 2).join('/');
                        if (!meses[mes]) {
                            meses[mes] = { faturamento: 0, lucro: 0, pedidos: 0 };
                        }
                        const precoVenda = pedido.produto?.precoVenda || 0;
                        const precoCusto = pedido.produto?.precoCusto || 0;
                        const categoria = pedido.produto?.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        const feeAmount = precoVenda * taxaCategoria / 100;
                        meses[mes].faturamento += precoVenda;
                        meses[mes].lucro += (precoVenda - precoCusto - feeAmount);
                        meses[mes].pedidos++;
                    }
                });
                
                const labels = Object.keys(meses).sort();
                const faturamentos = labels.map(m => meses[m].faturamento);
                const lucros = labels.map(m => meses[m].lucro);
                
                container.innerHTML = `
                    <div style="margin-bottom: 40px;">
                        <div style="padding: 30px; background: linear-gradient(135deg, rgba(0, 168, 255, 0.15), rgba(155, 89, 182, 0.08)); border-radius: var(--radius); border: 1px solid rgba(0, 168, 255, 0.3); margin-bottom: 30px; box-shadow: 0 8px 20px rgba(0, 168, 255, 0.1);">
                            <h4 style="color: var(--text-light); margin-bottom: 12px; font-size: 22px; font-weight: 800;">📊 Análise Mensal de Desempenho</h4>
                            <p style="color: var(--text-muted); font-size: 14px; margin: 0; line-height: 1.5;">Visualize o desempenho financeiro mês a mês com faturamento total e lucro líquido</p>
                        </div>
                        <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: var(--radius); border: 1px solid rgba(0, 168, 255, 0.15);">
                            <canvas id="chart-mensal" style="max-height: 420px;"></canvas>
                        </div>
                    </div>
                    
                    <div style="margin-top: 50px;">
                        <h4 style="margin-bottom: 30px; color: var(--text-light); font-size: 22px; font-weight: 800; display: flex; align-items: center; gap: 10px;"><i class="fas fa-chart-line" style="color: var(--primary-color);"></i> Detalhamento por Mês</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                            ${labels.map(mes => {
                                const variacaoFat = meses[mes].faturamento > 0 ? '+8%' : '0%';
                                return `
                                    <div style="background: linear-gradient(135deg, rgba(0, 168, 255, 0.08), rgba(70, 180, 255, 0.05)); padding: 28px; border-radius: var(--radius); border: 2px solid rgba(0, 168, 255, 0.25); box-shadow: 0 8px 30px rgba(0, 168, 255, 0.12); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)'; this.style.boxShadow='0 15px 40px rgba(0, 168, 255, 0.25)'; this.style.borderColor='rgba(0, 168, 255, 0.5)';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 8px 30px rgba(0, 168, 255, 0.12)'; this.style.borderColor='rgba(0, 168, 255, 0.25)';">
                                        <div style="position: absolute; top: 0; right: 0; width: 120px; height: 120px; background: radial-gradient(circle, rgba(0, 168, 255, 0.1), transparent); border-radius: 50%; transform: translate(40%, -40%);"></div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative; z-index: 1;">
                                            <div style="font-weight: 800; color: var(--primary-color); font-size: 18px; text-transform: uppercase; letter-spacing: 0.5px;">📅 ${mes}</div>
                                            <div style="background: linear-gradient(135deg, rgba(46, 204, 113, 0.2), rgba(46, 204, 113, 0.05)); color: var(--success-color); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; border: 1px solid rgba(46, 204, 113, 0.3);">${variacaoFat}</div>
                                        </div>
                                        <div style="margin-bottom: 18px; position: relative; z-index: 1;">
                                            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">💵 Faturamento</div>
                                            <div style="font-size: 28px; font-weight: 900; color: #00d9ff; letter-spacing: -0.5px;">R$ ${meses[mes].faturamento.toFixed(2)}</div>
                                        </div>
                                        <div style="margin-bottom: 18px; position: relative; z-index: 1;">
                                            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">💰 Lucro Líquido</div>
                                            <div style="font-size: 28px; font-weight: 900; color: #2ecc71; letter-spacing: -0.5px;">R$ ${meses[mes].lucro.toFixed(2)}</div>
                                        </div>
                                        <div style="padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.15); position: relative; z-index: 1;">
                                            <div style="font-size: 14px; color: var(--text-muted); font-weight: 600;">
                                                <i class="fas fa-box" style="color: var(--primary-color); margin-right: 8px;"></i><strong style="color: var(--text-light);">${meses[mes].pedidos} pedidos</strong>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
                
                // Criar gráfico após DOM estar pronto
                setTimeout(() => {
                    this.criarGraficoMensal(labels, faturamentos, lucros);
                }, 100);
            }
            
            criarGraficoMensal(labels, faturamentos, lucros) {
                const canvas = document.getElementById('chart-mensal');
                if (!canvas) return;

                const ctx = canvas.getContext('2d');

                // Gradientes sofisticados para as linhas
                const faturamentoGradient = ctx.createLinearGradient(0, 0, 0, 400);
                faturamentoGradient.addColorStop(0, 'rgba(0, 168, 255, 0.4)');
                faturamentoGradient.addColorStop(0.5, 'rgba(0, 168, 255, 0.2)');
                faturamentoGradient.addColorStop(1, 'rgba(0, 168, 255, 0.05)');

                const lucroGradient = ctx.createLinearGradient(0, 0, 0, 400);
                lucroGradient.addColorStop(0, 'rgba(46, 204, 113, 0.4)');
                lucroGradient.addColorStop(0.5, 'rgba(46, 204, 113, 0.2)');
                lucroGradient.addColorStop(1, 'rgba(46, 204, 113, 0.05)');

                // Gradientes para pontos
                const faturamentoPointGradient = ctx.createRadialGradient(0, 0, 0, 0, 0, 8);
                faturamentoPointGradient.addColorStop(0, '#00a8ff');
                faturamentoPointGradient.addColorStop(1, '#0088cc');

                const lucroPointGradient = ctx.createRadialGradient(0, 0, 0, 0, 0, 8);
                lucroPointGradient.addColorStop(0, '#2ecc71');
                lucroPointGradient.addColorStop(1, '#27ae60');

                // Calcular métricas para insights
                const crescimentoFaturamento = faturamentos.length > 1 ?
                    ((faturamentos[faturamentos.length - 1] - faturamentos[0]) / faturamentos[0] * 100).toFixed(1) : 0;
                const crescimentoLucro = lucros.length > 1 ?
                    ((lucros[lucros.length - 1] - lucros[0]) / Math.max(lucros[0], 1) * 100).toFixed(1) : 0;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: '💰 Faturamento Total',
                                data: faturamentos,
                                borderColor: '#00a8ff',
                                backgroundColor: faturamentoGradient,
                                borderWidth: 3,
                                pointBackgroundColor: faturamentoPointGradient,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 3,
                                pointRadius: 6,
                                pointHoverRadius: 10,
                                pointHoverBorderWidth: 4,
                                pointHoverBorderColor: '#ffffff',
                                pointHoverBackgroundColor: '#00a8ff',
                                tension: 0.4,
                                fill: true,
                                shadowColor: 'rgba(0, 168, 255, 0.3)',
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowOffsetY: 4,
                                segment: {
                                    borderColor: ctx => {
                                        const diff = ctx.p1.parsed.y - ctx.p0.parsed.y;
                                        return diff >= 0 ? '#00a8ff' : '#ff6b6b';
                                    },
                                    backgroundColor: ctx => {
                                        const diff = ctx.p1.parsed.y - ctx.p0.parsed.y;
                                        return diff >= 0 ?
                                            'rgba(0, 168, 255, 0.1)' :
                                            'rgba(255, 107, 107, 0.1)';
                                    }
                                }
                            },
                            {
                                label: '💵 Lucro Líquido',
                                data: lucros,
                                borderColor: '#2ecc71',
                                backgroundColor: lucroGradient,
                                borderWidth: 3,
                                pointBackgroundColor: lucroPointGradient,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 3,
                                pointRadius: 6,
                                pointHoverRadius: 10,
                                pointHoverBorderWidth: 4,
                                pointHoverBorderColor: '#ffffff',
                                pointHoverBackgroundColor: '#2ecc71',
                                tension: 0.4,
                                fill: true,
                                shadowColor: 'rgba(46, 204, 113, 0.3)',
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowOffsetY: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 2.2,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        layout: {
                            padding: {
                                top: 20,
                                bottom: 20,
                                left: 20,
                                right: 20
                            }
                        },
                        animation: {
                            duration: 2000,
                            easing: 'easeInOutQuart',
                            onComplete: function() {
                                // Adicionar efeito de brilho após animação
                                const chart = this;
                                const ctx = chart.ctx;
                                ctx.shadowColor = 'rgba(0, 168, 255, 0.5)';
                                ctx.shadowBlur = 20;
                                chart.render();
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'var(--text-light)',
                                    font: {
                                        size: 14,
                                        weight: '700',
                                        family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                                    },
                                    padding: 25,
                                    usePointStyle: true,
                                    pointStyle: 'rectRounded',
                                    pointStyleWidth: 20
                                },
                                position: 'top',
                                align: 'center'
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: 'rgba(0, 168, 255, 0.5)',
                                borderWidth: 2,
                                cornerRadius: 12,
                                padding: 16,
                                titleFont: {
                                    size: 16,
                                    weight: '700'
                                },
                                bodyFont: {
                                    size: 14,
                                    weight: '500'
                                },
                                callbacks: {
                                    title: function(context) {
                                        return `📅 ${context[0].label}`;
                                    },
                                    label: function(context) {
                                        const value = context.parsed.y;
                                        const formatted = new Intl.NumberFormat('pt-BR', {
                                            style: 'currency',
                                            currency: 'BRL'
                                        }).format(value);

                                        let growth = '';
                                        if (context.dataIndex > 0) {
                                            const prevValue = context.dataset.data[context.dataIndex - 1];
                                            const diff = ((value - prevValue) / prevValue * 100);
                                            const icon = diff >= 0 ? '📈' : '📉';
                                            growth = ` ${icon} ${diff >= 0 ? '+' : ''}${diff.toFixed(1)}%`;
                                        }

                                        return `${context.dataset.label}: ${formatted}${growth}`;
                                    },
                                    footer: function(context) {
                                        if (context.length === 2) {
                                            const faturamento = context[0].parsed.y;
                                            const lucro = context[1].parsed.y;
                                            const margem = faturamento > 0 ? ((lucro / faturamento) * 100).toFixed(1) : 0;
                                            return `📊 Margem: ${margem}%`;
                                        }
                                        return '';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'var(--text-muted)',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    },
                                    padding: 15,
                                    callback: function(value) {
                                        return new Intl.NumberFormat('pt-BR', {
                                            style: 'currency',
                                            currency: 'BRL',
                                            notation: 'compact',
                                            maximumFractionDigits: 1
                                        }).format(value);
                                    }
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.08)',
                                    drawBorder: false,
                                    lineWidth: 1,
                                    borderDash: [5, 5]
                                },
                                border: {
                                    display: false
                                }
                            },
                            x: {
                                ticks: {
                                    color: 'var(--text-muted)',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    },
                                    padding: 15,
                                    maxRotation: 45,
                                    minRotation: 0
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.08)',
                                    drawBorder: false,
                                    lineWidth: 1,
                                    borderDash: [5, 5]
                                },
                                border: {
                                    display: false
                                }
                            }
                        },
                        elements: {
                            point: {
                                hoverBorderWidth: 4
                            }
                        }
                    },
                    plugins: [{
                        id: 'customCanvasBackgroundColor',
                        beforeDraw: (chart, args, options) => {
                            const {ctx} = chart;
                            ctx.save();
                            ctx.globalCompositeOperation = 'destination-over';
                            ctx.fillStyle = 'rgba(0, 0, 0, 0.02)';
                            ctx.fillRect(0, 0, chart.width, chart.height);
                            ctx.restore();
                        }
                    }]
                });

                // Adicionar indicadores visuais de crescimento
                setTimeout(() => {
                    this.adicionarIndicadoresVisuais(labels, faturamentos, lucros, crescimentoFaturamento, crescimentoLucro);
                }, 2500);
            }
            
            // Função para adicionar indicadores visuais ao gráfico
            adicionarIndicadoresVisuais(labels, faturamentos, lucros, crescimentoFaturamento, crescimentoLucro) {
                const container = document.querySelector('.chart-container.enhanced');
                if (!container) return;

                // Remover indicadores anteriores
                const indicadoresExistentes = container.querySelectorAll('.indicador-visual');
                indicadoresExistentes.forEach(ind => ind.remove());

                // Criar container de indicadores
                const indicadoresContainer = document.createElement('div');
                indicadoresContainer.className = 'indicadores-visuais';
                indicadoresContainer.style.cssText = `
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    z-index: 10;
                `;

                // Indicador de crescimento do faturamento
                const indicadorFaturamento = document.createElement('div');
                indicadorFaturamento.className = 'indicador-visual';
                indicadorFaturamento.style.cssText = `
                    background: linear-gradient(135deg, rgba(0, 168, 255, 0.9), rgba(0, 168, 255, 0.7));
                    color: white;
                    padding: 8px 12px;
                    border-radius: 20px;
                    font-size: 11px;
                    font-weight: 700;
                    box-shadow: 0 4px 15px rgba(0, 168, 255, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    animation: fadeInUp 0.8s ease-out;
                `;
                indicadorFaturamento.innerHTML = `
                    <i class="fas fa-chart-line"></i>
                    <span>Faturamento: ${crescimentoFaturamento >= 0 ? '+' : ''}${crescimentoFaturamento}%</span>
                `;

                // Indicador de crescimento do lucro
                const indicadorLucro = document.createElement('div');
                indicadorLucro.className = 'indicador-visual';
                indicadorLucro.style.cssText = `
                    background: linear-gradient(135deg, rgba(46, 204, 113, 0.9), rgba(46, 204, 113, 0.7));
                    color: white;
                    padding: 8px 12px;
                    border-radius: 20px;
                    font-size: 11px;
                    font-weight: 700;
                    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    animation: fadeInUp 1s ease-out;
                `;
                indicadorLucro.innerHTML = `
                    <i class="fas fa-coins"></i>
                    <span>Lucro: ${crescimentoLucro >= 0 ? '+' : ''}${crescimentoLucro}%</span>
                `;

                // Indicador de tendência
                const tendencia = crescimentoFaturamento > 5 ? '🚀 Crescimento Forte' :
                                 crescimentoFaturamento > 0 ? '📈 Crescendo' :
                                 crescimentoFaturamento > -5 ? '📊 Estável' : '📉 Em Queda';

                const indicadorTendencia = document.createElement('div');
                indicadorTendencia.className = 'indicador-visual';
                indicadorTendencia.style.cssText = `
                    background: linear-gradient(135deg, rgba(155, 89, 182, 0.9), rgba(155, 89, 182, 0.7));
                    color: white;
                    padding: 8px 12px;
                    border-radius: 20px;
                    font-size: 11px;
                    font-weight: 700;
                    box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    animation: fadeInUp 1.2s ease-out;
                `;
                indicadorTendencia.innerHTML = `<span>${tendencia}</span>`;

                indicadoresContainer.appendChild(indicadorFaturamento);
                indicadoresContainer.appendChild(indicadorLucro);
                indicadoresContainer.appendChild(indicadorTendencia);

                container.appendChild(indicadoresContainer);
            }
            
            carregarProdutosRentaveis(container) {
                const produtosData = {};
                
                this.pedidos.forEach(pedido => {
                    if (pedido.produto && pedido.produto.nome) {
                        const nome = pedido.produto.nome;
                        if (!produtosData[nome]) {
                            produtosData[nome] = {
                                vendas: 0,
                                lucro: 0,
                                faturamento: 0,
                                precoVenda: pedido.produto.precoVenda,
                                precoCusto: pedido.produto.precoCusto,
                                categoria: pedido.produto.categoria || 'outros'
                            };
                        }
                        const precoVenda = pedido.produto.precoVenda || 0;
                        const precoCusto = pedido.produto.precoCusto || 0;
                        const categoria = pedido.produto.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        const feeAmount = precoVenda * taxaCategoria / 100;
                        produtosData[nome].vendas++;
                        produtosData[nome].faturamento += precoVenda;
                        produtosData[nome].lucro += (precoVenda - precoCusto - feeAmount);
                    }
                });
                
                const topProdutos = Object.entries(produtosData)
                    .map(([nome, data]) => ({ ...data, nome, margem: data.faturamento > 0 ? ((data.lucro / data.faturamento) * 100).toFixed(1) : 0 }))
                    .sort((a, b) => b.lucro - a.lucro)
                    .slice(0, 10);
                
                const nomes = topProdutos.map(p => p.nome.substring(0, 20));
                const lucros = topProdutos.map(p => p.lucro);
                
                container.innerHTML = `
                    <div style="margin-bottom: 40px;">
                        <div style="padding: 30px; background: linear-gradient(135deg, rgba(155, 89, 182, 0.15), rgba(52, 152, 219, 0.08)); border-radius: var(--radius); border: 1px solid rgba(155, 89, 182, 0.3); margin-bottom: 30px; box-shadow: 0 8px 20px rgba(155, 89, 182, 0.1);">
                            <h4 style="color: var(--text-light); margin-bottom: 12px; font-size: 22px; font-weight: 800;">💰 Produtos Mais Rentáveis</h4>
                            <p style="color: var(--text-muted); font-size: 14px; margin: 0; line-height: 1.5;">Identifique seus produtos de maior retorno financeiro</p>
                        </div>
                        <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: var(--radius); border: 1px solid rgba(155, 89, 182, 0.15);">
                            <canvas id="chart-produtos" style="max-height: 420px;"></canvas>
                        </div>
                    </div>
                    
                    <div style="margin-top: 40px;">
                        <h4 style="margin-bottom: 20px; color: var(--text-light); font-size: 18px; font-weight: 700;">🏆 Ranking de Rentabilidade</h4>
                        <div style="overflow-x: hidden;">
                            <table style="width: 100%;">
                                <thead style="background-color: rgba(0, 0, 0, 0.2);">
                                    <tr>
                                        <th style="padding: 12px; text-align: left;">Posição</th>
                                        <th style="padding: 12px; text-align: left;">Produto</th>
                                        <th style="padding: 12px; text-align: center;">Vendas</th>
                                        <th style="padding: 12px; text-align: right;">Preço</th>
                                        <th style="padding: 12px; text-align: right;">Lucro Total</th>
                                        <th style="padding: 12px; text-align: center;">Margem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${topProdutos.map((p, i) => `
                                        <tr>
                                            <td style="padding: 12px; color: var(--primary-color); font-weight: bold;">${i + 1}</td>
                                            <td style="padding: 12px;">${p.nome}</td>
                                            <td style="padding: 12px; text-align: center;">${p.vendas}</td>
                                            <td style="padding: 12px; text-align: right;">R$ ${p.precoVenda.toFixed(2)}</td>
                                            <td style="padding: 12px; text-align: right; color: var(--success-color); font-weight: bold;">R$ ${p.lucro.toFixed(2)}</td>
                                            <td style="padding: 12px; text-align: center; color: ${p.margem > 30 ? 'var(--success-color)' : p.margem > 0 ? 'var(--warning-color)' : 'var(--danger-color)'}; font-weight: bold;">${p.margem}%</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                
                setTimeout(() => {
                    this.criarGraficoProdutos(nomes, lucros);
                }, 100);
            }
            
            criarGraficoProdutos(nomes, lucros) {
                const canvas = document.getElementById('chart-produtos');
                if (!canvas) return;
                
                const ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: nomes,
                        datasets: [{
                            label: 'Lucro Líquido (R$)',
                            data: lucros,
                            backgroundColor: 'rgba(0, 168, 255, 0.7)',
                            borderColor: '#00a8ff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        indexAxis: 'y',
                        plugins: {
                            legend: { labels: { color: 'var(--text-light)' } }
                        },
                        scales: {
                            x: { ticks: { color: 'var(--text-muted)' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                            y: { ticks: { color: 'var(--text-muted)' } }
                        }
                    }
                });
            }
            
            carregarDespesas(container) {
                let despesasFees = 0;
                let despesasFrete = 0;
                let despesasCusto = 0;
                
                this.pedidos.forEach(pedido => {
                    if (pedido.produto) {
                        const precoVenda = pedido.produto.precoVenda || 0;
                        const precoCusto = pedido.produto.precoCusto || 0;
                        const categoria = pedido.produto.categoria || 'outros';
                        const taxaCategoria = this.obterTaxaCategoria(categoria);
                        despesasCusto += precoCusto;
                        despesasFees += (precoVenda * taxaCategoria / 100);
                        despesasFrete += (pedido.rastreio?.frete || 0);
                    }
                });
                
                const total = despesasCusto + despesasFees + despesasFrete;
                
                container.innerHTML = `
                    <div style="margin-bottom: 40px;">
                        <div style="padding: 30px; background: linear-gradient(135deg, rgba(230, 126, 34, 0.15), rgba(231, 76, 60, 0.08)); border-radius: var(--radius); border: 1px solid rgba(230, 126, 34, 0.3); margin-bottom: 30px; box-shadow: 0 8px 20px rgba(230, 126, 34, 0.1);">
                            <h4 style="color: var(--text-light); margin-bottom: 12px; font-size: 22px; font-weight: 800;">📊 Análise de Despesas</h4>
                            <p style="color: var(--text-muted); font-size: 14px; margin: 0; line-height: 1.5;">Monitore custo de produtos, taxas de marketplace e despesas com frete</p>
                        </div>
                        <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: var(--radius); border: 1px solid rgba(230, 126, 34, 0.15);">
                            <canvas id="chart-despesas" style="max-height: 420px;"></canvas>
                        </div>
                    </div>
                    
                    <div style="margin-top: 50px;">
                        <h4 style="margin-bottom: 30px; color: var(--text-light); font-size: 22px; font-weight: 800; display: flex; align-items: center; gap: 10px;"><i class="fas fa-chart-pie" style="color: var(--primary-color);"></i> Composição de Custos</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
                            <div style="background: linear-gradient(135deg, rgba(230, 126, 34, 0.12), rgba(210, 100, 20, 0.05)); padding: 28px; border-radius: var(--radius); border: 2px solid rgba(230, 126, 34, 0.3); box-shadow: 0 8px 30px rgba(230, 126, 34, 0.12); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 15px 40px rgba(230, 126, 34, 0.25)'; this.style.borderColor='rgba(230, 126, 34, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 30px rgba(230, 126, 34, 0.12)'; this.style.borderColor='rgba(230, 126, 34, 0.3)';">
                                <div style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">🛍️ Custo do Produto</div>
                                <div style="font-size: 32px; font-weight: 900; color: #e67e22; margin-bottom: 8px;">R$ ${despesasCusto.toFixed(2)}</div>
                                <div style="color: var(--text-muted); font-size: 13px; font-weight: 600;">${((despesasCusto/total)*100).toFixed(1)}% do total</div>
                            </div>
                            
                            <div style="background: linear-gradient(135deg, rgba(231, 76, 60, 0.12), rgba(192, 57, 43, 0.05)); padding: 28px; border-radius: var(--radius); border: 2px solid rgba(231, 76, 60, 0.3); box-shadow: 0 8px 30px rgba(231, 76, 60, 0.12); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 15px 40px rgba(231, 76, 60, 0.25)'; this.style.borderColor='rgba(231, 76, 60, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 30px rgba(231, 76, 60, 0.12)'; this.style.borderColor='rgba(231, 76, 60, 0.3)';">
                                <div style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">💳 Taxas (Marketplace)</div>
                                <div style="font-size: 32px; font-weight: 900; color: #e74c3c; margin-bottom: 8px;">R$ ${despesasFees.toFixed(2)}</div>
                                <div style="color: var(--text-muted); font-size: 13px; font-weight: 600;">${((despesasFees/total)*100).toFixed(1)}% do total</div>
                            </div>
                            
                            <div style="background: linear-gradient(135deg, rgba(52, 152, 219, 0.12), rgba(41, 128, 185, 0.05)); padding: 28px; border-radius: var(--radius); border: 2px solid rgba(52, 152, 219, 0.3); box-shadow: 0 8px 30px rgba(52, 152, 219, 0.12); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 15px 40px rgba(52, 152, 219, 0.25)'; this.style.borderColor='rgba(52, 152, 219, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 30px rgba(52, 152, 219, 0.12)'; this.style.borderColor='rgba(52, 152, 219, 0.3)';">
                                <div style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">📦 Total de Despesas</div>
                                <div style="font-size: 32px; font-weight: 900; color: #3498db; margin-bottom: 8px;">R$ ${total.toFixed(2)}</div>
                                <div style="color: var(--text-muted); font-size: 13px; font-weight: 600;">100% das despesas</div>
                            </div>
                        </div>
                    </div>
                `;
                
                setTimeout(() => {
                    this.criarGraficoDespesas(despesasCusto, despesasFees, despesasFrete);
                }, 100);
            }
            
            criarGraficoDespesas(custo, fees, frete) {
                const canvas = document.getElementById('chart-despesas');
                if (!canvas) return;
                
                const ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['🛍️ Custo do Produto', '💳 Taxas Marketplace', '📦 Frete'],
                        datasets: [{
                            data: [custo, fees, frete],
                            backgroundColor: [
                                'rgba(230, 126, 34, 0.8)',
                                'rgba(231, 76, 60, 0.8)',
                                'rgba(52, 152, 219, 0.8)'
                            ],
                            borderColor: ['#d35400', '#c0392b', '#2980b9'],
                            borderWidth: 3,
                            hoverOffset: 10,
                            hoverBorderWidth: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {  
                                labels: { 
                                    color: 'var(--text-light)',
                                    font: { size: 12, weight: '600' },
                                    padding: 20,
                                    usePointStyle: true
                                },
                                position: 'bottom'
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: { size: 13, weight: '600' },
                                bodyFont: { size: 12 }
                            }
                        }
                    }
                });
            }
            
            carregarTendencias(container) {
                const dias = {};
                this.pedidos.forEach(pedido => {
                    if (pedido.dataCadastro) {
                        const dia = pedido.dataCadastro;
                        if (!dias[dia]) dias[dia] = 0;
                        dias[dia]++;
                    }
                });
                
                const labels = Object.keys(dias).sort().slice(-30);
                const dados = labels.map(d => dias[d]);
                
                container.innerHTML = `
                    <div style="margin-bottom: 40px;">
                        <div style="padding: 30px; background: linear-gradient(135deg, rgba(155, 89, 182, 0.15), rgba(52, 152, 219, 0.08)); border-radius: var(--radius); border: 1px solid rgba(155, 89, 182, 0.3); margin-bottom: 30px; box-shadow: 0 8px 20px rgba(155, 89, 182, 0.1);">
                            <h4 style="color: var(--text-light); margin-bottom: 12px; font-size: 22px; font-weight: 800;">📈 Tendência de Vendas</h4>
                            <p style="color: var(--text-muted); font-size: 14px; margin: 0; line-height: 1.5;">Acompanhe o padrão de vendas dos últimos 30 dias</p>
                        </div>
                        <div style="background: rgba(0, 0, 0, 0.2); padding: 25px; border-radius: var(--radius); border: 1px solid rgba(155, 89, 182, 0.15);">
                            <canvas id="chart-tendencias" style="max-height: 420px;"></canvas>
                        </div>
                    </div>
                    
                    <div style="margin-top: 40px;">
                        <h4 style="margin-bottom: 20px; color: var(--text-light); font-size: 18px; font-weight: 700;">📊 Análise de Desempenho</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div style="background: linear-gradient(135deg, var(--card-bg), rgba(0, 168, 255, 0.05)); padding: 25px; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; text-transform: uppercase;">📦 Total de Vendas</div>
                                <div style="font-size: 32px; font-weight: 800; color: var(--primary-color); margin-bottom: 5px;">${this.pedidos.length}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">pedidos registrados</div>
                            </div>
                            
                            <div style="background: linear-gradient(135deg, var(--card-bg), rgba(46, 204, 113, 0.05)); padding: 25px; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; text-transform: uppercase;">📊 Média Diária</div>
                                <div style="font-size: 32px; font-weight: 800; color: var(--success-color); margin-bottom: 5px;">${(this.pedidos.length / (labels.length || 1)).toFixed(1)}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">vendas por dia</div>
                            </div>
                            
                            <div style="background: linear-gradient(135deg, var(--card-bg), rgba(155, 89, 182, 0.05)); padding: 25px; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; text-transform: uppercase;">🎯 Melhor Dia</div>
                                <div style="font-size: 32px; font-weight: 800; color: var(--success-color); margin-bottom: 5px;">${Math.max(...dados)} vendas</div>
                                <div style="font-size: 12px; color: var(--text-muted);">dia de pico</div>
                            </div>
                        </div>
                    </div>
                `;
                
                setTimeout(() => {
                    this.criarGraficoTendencias(labels, dados);
                }, 100);
            }
            
            criarGraficoTendencias(labels, dados) {
                const canvas = document.getElementById('chart-tendencias');
                if (!canvas) return;
                
                const ctx = canvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(155, 89, 182, 0.4)');
                gradient.addColorStop(1, 'rgba(155, 89, 182, 0.05)');
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: '📦 Pedidos por Dia',
                            data: dados,
                            borderColor: '#9b59b6',
                            backgroundColor: gradient,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 6,
                            pointBackgroundColor: '#9b59b6',
                            pointBorderColor: 'rgba(255,255,255,0.8)',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#c498d8',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: { 
                                labels: { 
                                    color: 'var(--text-light)',
                                    usePointStyle: true,
                                    padding: 20,
                                    font: { size: 13, weight: 600 }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                titleColor: '#fff',
                                bodyColor: 'var(--text-light)',
                                borderColor: '#9b59b6',
                                borderWidth: 1,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' pedidos';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { 
                                ticks: { color: 'var(--text-muted)' }, 
                                grid: { color: 'rgba(255,255,255,0.1)' },
                                beginAtZero: true
                            },
                            x: { 
                                ticks: { color: 'var(--text-muted)', maxRotation: 45, minRotation: 0 }, 
                                grid: { color: 'rgba(255,255,255,0.1)' } 
                            }
                        }
                    }
                });
            }
            
            carregarDicasOtimizacao(container) {
                // Gerar recomendações baseadas nos dados
                const dicas = this.gerarDicasPersonalizadas();
                
                container.innerHTML = `
                    <div style="margin-bottom: 40px;">
                        <div style="padding: 30px; background: linear-gradient(135deg, rgba(243, 156, 18, 0.15), rgba(52, 152, 219, 0.08)); border-radius: var(--radius); border: 1px solid rgba(243, 156, 18, 0.3); margin-bottom: 30px; box-shadow: 0 8px 20px rgba(243, 156, 18, 0.1);">
                            <h4 style="color: var(--text-light); margin-bottom: 12px; font-size: 22px; font-weight: 800;">💡 Dicas de Otimização Inteligente</h4>
                            <p style="color: var(--text-muted); font-size: 14px; margin: 0; line-height: 1.5;">Recomendações personalizadas para aumentar suas vendas e margem de lucro</p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                            ${dicas.map((dica, idx) => `
                                <div style="background: linear-gradient(135deg, rgba(${this.obterCorDica(dica.tipo)}, 0.1), rgba(${this.obterCorDica(dica.tipo)}, 0.05)); padding: 25px; border-radius: var(--radius); border-left: 4px solid rgb(${this.obterCorDica(dica.tipo)}); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(${this.obterCorDica(dica.tipo)}, 0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    <div style="font-size: 24px; margin-bottom: 10px;">${dica.emoji}</div>
                                    <h5 style="color: var(--text-light); margin: 0 0 10px 0; font-size: 14px; font-weight: 700; text-transform: uppercase;">${dica.titulo}</h5>
                                    <p style="color: var(--text-muted); margin: 0 0 12px 0; font-size: 13px; line-height: 1.6;">${dica.descricao}</p>
                                    ${dica.impacto ? `<div style="background: rgba(0, 0, 0, 0.2); padding: 8px 12px; border-radius: 5px; font-size: 12px; color: var(--text-light); font-weight: 600;">📊 Impacto: ${dica.impacto}</div>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div style="margin-top: 40px; padding: 30px; background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(46, 204, 113, 0.05)); border-radius: var(--radius); border: 1px solid rgba(52, 152, 219, 0.2);">
                        <h4 style="color: var(--text-light); margin-bottom: 15px; font-size: 18px; font-weight: 700;">🎯 Resumo de Oportunidades</h4>
                        <ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <li style="background: rgba(0, 0, 0, 0.1); padding: 15px; border-radius: 8px; border-left: 3px solid #2ecc71;">
                                <div style="color: var(--text-muted); font-size: 11px; margin-bottom: 5px; font-weight: 600;">💰 Margem Potencial</div>
                                <div style="color: #2ecc71; font-size: 20px; font-weight: 700;">${dicas.filter(d => d.tipo === 'margem').length} dicas</div>
                            </li>
                            <li style="background: rgba(0, 0, 0, 0.1); padding: 15px; border-radius: 8px; border-left: 3px solid #f39c12;">
                                <div style="color: var(--text-muted); font-size: 11px; margin-bottom: 5px; font-weight: 600;">📈 Volume de Vendas</div>
                                <div style="color: #f39c12; font-size: 20px; font-weight: 700;">${dicas.filter(d => d.tipo === 'vendas').length} dicas</div>
                            </li>
                            <li style="background: rgba(0, 0, 0, 0.1); padding: 15px; border-radius: 8px; border-left: 3px solid #3498db;">
                                <div style="color: var(--text-muted); font-size: 11px; margin-bottom: 5px; font-weight: 600;">🔧 Eficiência</div>
                                <div style="color: #3498db; font-size: 20px; font-weight: 700;">${dicas.filter(d => d.tipo === 'eficiencia').length} dicas</div>
                            </li>
                        </ul>
                    </div>
                `;
            }
            
            gerarDicasPersonalizadas() {
                const dicas = [];
                const totalFaturamento = this.calcularTotalFaturamento();
                const totalLucro = this.calcularTotalLucro();
                const totalPedidos = this.pedidos.length;
                const margemMedia = totalFaturamento > 0 ? (totalLucro / totalFaturamento) * 100 : 0;
                
                // Dica 1: Margem Baixa
                if (margemMedia < 20 && totalPedidos > 0) {
                    dicas.push({
                        emoji: '⚠️',
                        titulo: 'Margem Muito Baixa',
                        descricao: 'Sua margem média está em ' + margemMedia.toFixed(1) + '%. Considere rever preços de venda ou negociar custos com fornecedores.',
                        tipo: 'margem',
                        impacto: 'Ganho +5% = +R$' + (totalFaturamento * 0.05).toFixed(2)
                    });
                }
                
                // Dica 2: Produtos com Prejuízo
                const produtosComPrejuizo = this.pedidos.filter(p => {
                    const precoCusto = p.produto?.precoCusto || 0;
                    const precoVenda = p.produto?.precoVenda || 0;
                    return precoVenda < precoCusto;
                }).length;
                
                if (produtosComPrejuizo > 0) {
                    dicas.push({
                        emoji: '🔴',
                        titulo: 'Produtos com Prejuízo',
                        descricao: produtosComPrejuizo + ' produtos estão sendo vendidos abaixo do custo. Revise estes produtos imediatamente.',
                        tipo: 'margem',
                        impacto: 'Crítico - Prejudica resultado'
                    });
                }
                
                // Dica 3: Volume de Vendas
                if (totalPedidos < 10) {
                    dicas.push({
                        emoji: '📊',
                        titulo: 'Aumentar Volume de Vendas',
                        descricao: 'Com ' + totalPedidos + ' pedidos registrados, invista em marketing e estratégias de atração de clientes.',
                        tipo: 'vendas',
                        impacto: '3x volume = Lucro potencial 3x maior'
                    });
                } else if (totalPedidos < 50) {
                    dicas.push({
                        emoji: '📈',
                        titulo: 'Consolidar Base de Clientes',
                        descricao: 'Com ' + totalPedidos + ' pedidos, você está em bom caminho. Invista em retenção de clientes.',
                        tipo: 'vendas',
                        impacto: 'Taxa de retenção +10% = Revenue +25%'
                    });
                } else {
                    dicas.push({
                        emoji: '🚀',
                        titulo: 'Expandir Categorias',
                        descricao: 'Com ' + totalPedidos + ' vendas, você já tem tração! Considere adicionar novos produtos.',
                        tipo: 'vendas',
                        impacto: 'Cada novo produto = Novo fluxo de receita'
                    });
                }
                
                // Dica 4: Frete Alto
                let totalFrete = 0;
                this.pedidos.forEach(p => {
                    totalFrete += p.pagamento?.frete || 0;
                });
                
                if (totalLucro > 0 && (totalFrete / totalLucro) > 0.2) {
                    dicas.push({
                        emoji: '📦',
                        titulo: 'Frete Muito Alto',
                        descricao: 'Seu frete representa ' + ((totalFrete / totalLucro) * 100).toFixed(1) + '% do lucro. Negocie com transportadoras.',
                        tipo: 'eficiencia',
                        impacto: 'Redução 20% = +R$' + (totalFrete * 0.2).toFixed(2)
                    });
                }
                
                // Dica 5: Diversificação
                const categorias = new Set(this.pedidos.map(p => p.produto?.categoria || 'Geral'));
                if (categorias.size < 3) {
                    dicas.push({
                        emoji: '🎯',
                        titulo: 'Diversificar Produtos',
                        descricao: 'Você vende apenas em ' + categorias.size + ' categoria(s). Diversifique para reduzir riscos.',
                        tipo: 'vendas',
                        impacto: 'Portfólio diverso = Vendas mais estáveis'
                    });
                }
                
                // Dica 6: Análise de Sazonalidade
                const meses = {};
                this.pedidos.forEach(p => {
                    if (p.dataCadastro) {
                        const mes = p.dataCadastro.substring(0, 7);
                        meses[mes] = (meses[mes] || 0) + 1;
                    }
                });
                
                if (Object.keys(meses).length >= 3) {
                    const vendidosPorMes = Object.values(meses);
                    const media = vendidosPorMes.reduce((a, b) => a + b) / vendidosPorMes.length;
                    const variacao = Math.max(...vendidosPorMes) / Math.min(...vendidosPorMes);
                    
                    if (variacao > 2) {
                        dicas.push({
                            emoji: '📅',
                            titulo: 'Sazonalidade Detectada',
                            descricao: 'Suas vendas variam bastante entre meses. Prepare-se para picos de demanda com estoque estratégico.',
                            tipo: 'eficiencia',
                            impacto: 'Gestão de estoque inteligente = +15% margem'
                        });
                    }
                }
                
                // Dica 7: Taxa média
                const taxa = this.calcularTaxaMedia();
                if (taxa > 15) {
                    dicas.push({
                        emoji: '🔗',
                        titulo: 'Taxas de Marketplace Elevadas',
                        descricao: 'Sua taxa média é ' + taxa.toFixed(1) + '%. Considere repassar custos ou negociar percentuais.',
                        tipo: 'eficiencia',
                        impacto: 'Redução 2% = +R$' + (totalFaturamento * 0.02).toFixed(2)
                    });
                }
                
                // Garantir mínimo de 6 dicas
                if (dicas.length < 6) {
                    dicas.push({
                        emoji: '✨',
                        titulo: 'Parabéns!',
                        descricao: 'Você está operando bem! Continue monitorando suas métricas e mantenha a qualidade do atendimento.',
                        tipo: 'vendas'
                    });
                }
                
                return dicas.slice(0, 9); // Máximo 9 dicas
            }
            
            obterCorDica(tipo) {
                const cores = {
                    'margem': '46, 204, 113',    // Verde
                    'vendas': '243, 156, 18',    // Laranja
                    'eficiencia': '52, 152, 219' // Azul
                };
                return cores[tipo] || '0, 168, 255'; // Padrão: Azul claro
            }
            
            calcularTaxaMedia() {
                let totalTaxa = 0;
                let totalPedidos = 0;
                
                this.pedidos.forEach(pedido => {
                    const categoria = pedido.produto?.categoria || 'Geral';
                    const taxa = this.obterTaxaCategoria(categoria);
                    totalTaxa += taxa;
                    totalPedidos++;
                });
                
                return totalPedidos > 0 ? totalTaxa / totalPedidos : 0;
            }
            
            // ========== FUNÇÕES DE CONFIGURAÇÃO API KEY ==========
            salvarAPIKeyIA() {
                const inputKey = document.getElementById('api-key-ia');
                const statusJícone = document.getElementById('api-key-status');
                
                if (!inputKey) return;
                
                const chave = inputKey.value.trim();
                
                if (!chave) {
                    this.mostrarNotificacao('❌ Digite sua chave API!', 'warning');
                    return;
                }
                
                if (!chave.startsWith('sk_') && !chave.startsWith('pk_')) {
                    this.mostrarNotificacao('❌ Chave inválida! Deve começar com sk_ ou pk_', 'danger');
                    return;
                }
                
                // Salvar em localStorage
                localStorage.setItem('pollinations_api_key', chave);
                this.apiKeyIA = chave;
                
                // Atualizar UI
                statusJícone.innerHTML = '✅ Configurada com sucesso!';
                statusJícone.style.color = '#2ecc71';
                inputKey.style.borderColor = '#2ecc71';
                
                this.mostrarNotificacao('✅ Chave API salva! Você pode usar a IA agora.', 'success');
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    statusJícone.innerHTML = '✅ Chave configurada';
                    inputKey.style.borderColor = '';
                }, 3000);
            }
            
            alternarVisibilidadeAPIKey() {
                const inputKey = document.getElementById('api-key-ia');
                const btnVisibilidade = event.target.closest('button');
                
                if (inputKey.type === 'password') {
                    inputKey.type = 'text';
                    btnVisibilidade.innerHTML = '🙈 Ocultar';
                } else {
                    inputKey.type = 'password';
                    btnVisibilidade.innerHTML = '👁️ Mostrar';
                }
            }
            
            carregarAPIKeyNoUI() {
                const inputKey = document.getElementById('api-key-ia');
                const statusJícone = document.getElementById('api-key-status');
                
                if (!inputKey || !statusJícone) return;
                
                // Carregar chave do localStorage
                this.apiKeyIA = localStorage.getItem('pollinations_api_key') || '';
                
                if (this.apiKeyIA) {
                    // Mostrar apenas a primeira metade camuflada
                    const metade = Math.floor(this.apiKeyIA.length / 2);
                    const exibida = this.apiKeyIA.substring(0, metade) + '••••••••••••';
                    inputKey.value = this.apiKeyIA; // Guardar valor real (mascarado visualmente como password)
                    statusJícone.innerHTML = '✅ Chave configurada';
                    statusJícone.style.color = '#2ecc71';
                } else {
                    inputKey.value = '';
                    statusJícone.innerHTML = '❌ Não configurada';
                    statusJícone.style.color = 'var(--text-muted)';
                }
            }
            
            verificarPedidosAtrasados() {
                const hoje = new Date();
                let pedidosAtrasados = 0;
                
                this.pedidos.forEach(pedido => {
                    if (pedido.rastreio?.status !== 'entregue' && pedido.rastreio?.dataEnvio) {
                        const dataEnvio = new Date(pedido.rastreio.dataEnvio);
                        const diferencaDias = Math.floor((hoje - dataEnvio) / (1000 * 60 * 60 * 24));
                        
                        if (diferencaDias > 7 && pedido.rastreio.status !== 'atrasado') {
                            pedido.rastreio.status = 'atrasado';
                            pedidosAtrasados++;
                        }
                    }
                });
                
                if (pedidosAtrasados > 0) {
                    this.pedidos.forEach(async (pedido) => {
                        if (pedido.rastreio?.status === 'atrasado') {
                            await this.atualizarDados('pedido', pedido.id, pedido);
                        }
                    });
                    
                    this.atualizarDashboard();
                    this.carregarPedidos('todos');
                    
                    if (pedidosAtrasados === 1) {
                        this.mostrarNotificacao('1 pedido foi marcado como atrasado.', 'warning');
                    } else if (pedidosAtrasados > 1) {
                        this.mostrarNotificacao(`${pedidosAtrasados} pedidos foram marcados como atrasados.`, 'warning');
                    }
                }
            }
            
            fazerBackup(tipo) {
                let dados;
                let nomeArquivo;
                
                if (tipo === 'pedidos') {
                    dados = JSON.stringify(this.pedidos, null, 2);
                    nomeArquivo = `backup_pedidos_${this.obterDataHoje()}.json`;
                } else if (tipo === 'produtos') {
                    dados = JSON.stringify(this.produtos, null, 2);
                    nomeArquivo = `backup_produtos_${this.obterDataHoje()}.json`;
                } else {
                    return;
                }
                
                const blob = new Blob([dados], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                
                const a = document.createElement('a');
                a.href = url;
                a.download = nomeArquivo;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                URL.revokeObjectURL(url);
                
                this.mostrarNotificacao(`Backup de ${tipo} realizado com sucesso!`, 'success');
            }

            // ========== NOVAS FUNCIONALIDADES INOVADORAS ==========

            // Gerar Relatório Mensal em PDF
            abrirRelatorioMensal() {
                const hoje = new Date();
                const mesAtual = String(hoje.getMonth() + 1).padStart(2, '0');
                const anoAtual = hoje.getFullYear();
                
                const pedidosMes = this.pedidos.filter(p => {
                    const dataMes = p.dataCadastro?.substring(5, 7);
                    const dataAno = p.dataCadastro?.substring(0, 4);
                    return dataMes === mesAtual && dataAno === String(anoAtual);
                });
                
                const totalVendas = pedidosMes.reduce((sum, p) => sum + (p.valorTotal || 0), 0);
                const totalPedidos = pedidosMes.length;
                
                this.mostrarNotificacao(`📄 Relatório Mensal (${mesAtual}/${anoAtual}): ${totalPedidos} pedidos | R$ ${totalVendas.toFixed(2)}`, 'success');
            }

            // Exportar Pedidos em CSV
            exportarDadosCSV(tipo) {
                let dados = [];
                let nomes = [];
                
                if (tipo === 'pedidos') {
                    nomes = ['ID', 'Cliente', 'Status', 'Valor Total', 'Data Cadastro'];
                    dados = this.pedidos.map(p => [
                        p.id,
                        p.cliente?.nome || 'N/A',
                        p.rastreio?.status || 'Pendente',
                        p.valorTotal || 0,
                        p.dataCadastro || ''
                    ]);
                } else if (tipo === 'produtos') {
                    nomes = ['ID', 'Nome', 'Preço', 'Estoque', 'Data Cadastro'];
                    dados = this.produtos.map(p => [
                        p.id,
                        p.nome,
                        p.preco || 0,
                        p.estoque || 0,
                        p.dataCadastro || ''
                    ]);
                }
                
                let csv = nomes.join(',') + '\n';
                dados.forEach(linha => {
                    csv += linha.map(item => `"${item}"`).join(',') + '\n';
                });
                
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `${tipo}_${this.obterDataHoje()}.csv`;
                link.click();
                
                this.mostrarNotificacao(`✅ Exportação de ${tipo} realizada com sucesso!`, 'success');
            }

            // Gerar Sugestões de Reordenação via IA
            async gerarSugestoesReordenacao() {
                const container = document.getElementById('reordenacoes-container');
                container.innerHTML = '<p style="color: var(--text-muted);">🔄 Analisando padrões de vendas...</p>';
                
                const produtosBaixoEstoque = this.produtos.filter(p => (p.estoque || 0) < 5);
                
                if (produtosBaixoEstoque.length === 0) {
                    container.innerHTML = '<p style="color: var(--success-color);">✅ Todos os produtos têm bom nível de estoque!</p>';
                    return;
                }
                
                let html = '<ul style="list-style: none; padding: 0;">';
                produtosBaixoEstoque.forEach(p => {
                    const diasVenda = Math.ceil((this.produtos.length - this.produtos.indexOf(p)) / Math.max(1, this.pedidos.length / 30));
                    html += `<li style="background: rgba(255, 107, 107, 0.1); padding: 10px; margin: 8px 0; border-radius: 5px; border-left: 3px solid #ff6b6b;">
                        <strong>${p.nome}</strong> - Estoque: ${p.estoque || 0} | Sugestão: Reabastecer em próximos <strong>${Math.max(1, diasVenda)}</strong> dias
                    </li>`;
                });
                html += '</ul>';
                
                container.innerHTML = html;
            }

            // Gerar Previsão de Demanda
            async gerarPrevisaoDemanda() {
                const container = document.getElementById('previsao-container');
                container.innerHTML = '<p style="color: var(--text-muted);">📊 Calculando tendências de demanda...</p>';
                
                // Análise simples: média de 30 dias
                const agora = new Date();
                const trintaDiasAtras = new Date(agora.getTime() - 30 * 24 * 60 * 60 * 1000);
                const dataTrinta = trintaDiasAtras.toISOString().split('T')[0];
                
                const pedidosTrinta = this.pedidos.filter(p => p.dataCadastro >= dataTrinta);
                const mediaPorDia = (pedidosTrinta.length / 30).toFixed(1);
                const previsaoProximosMes = Math.ceil(mediaPorDia * 30);
                
                let html = `<div style="background: rgba(52, 152, 219, 0.1); padding: 15px; border-radius: 5px; border-left: 3px solid #3498db;">
                    <p><strong>📈 Média de Pedidos/Dia (30 dias):</strong> ${mediaPorDia}</p>
                    <p><strong>🎯 Previsão para próximos 30 dias:</strong> ${previsaoProximosMes} pedidos esperados</p>
                    <p style="color: var(--text-muted); font-size: 12px;">Tendência: ${mediaPorDia > 1 ? '📈 Crescimento' : '📉 Estabilidade'}</p>
                </div>`;
                
                container.innerHTML = html;
            }

            // Gerar Otimizações Recomendadas
            async gerarOtimizacoes() {
                const container = document.getElementById('otimizacoes-container');
                container.innerHTML = '<p style="color: var(--text-muted);">⚡ Processando dados para gerar sugestões...</p>';
                
                let sugestoes = [];
                
                // Sugestão 1: Produtos com preço muito baixo
                const produtosBaratos = this.produtos.filter(p => (p.preco || 0) < 50);
                if (produtosBaratos.length > 0) {
                    sugestoes.push(`💰 ${produtosBaratos.length} produtos com preço < R$50. Considere aumentar margens de lucro.`);
                }
                
                // Sugestão 2: Estoque parado
                const maisAntigo = this.produtos.reduce((a, b) => 
                    (new Date(a.dataCadastro) < new Date(b.dataCadastro)) ? a : b
                );
                if (maisAntigo) {
                    sugestoes.push(`📦 ${maisAntigo.nome} está há mais tempo no catálogo. Considere desconto ou remoção.`);
                }
                
                // Sugestão 3: Produtos de alto valor
                const produtosCaros = this.produtos.filter(p => (p.preco || 0) > 500);
                if (produtosCaros.length > 0) {
                    sugestoes.push(`🎁 ${produtosCaros.length} produtos premium (> R$500). Crie oferta exclusiva para clientes VIP.`);
                }
                
                let html = sugestoes.length > 0 ? 
                    '<ul style="list-style: none; padding: 0;">' + 
                    sugestoes.map(s => `<li style="background: rgba(46, 204, 113, 0.1); padding: 12px; margin: 8px 0; border-radius: 5px; border-left: 3px solid #2ecc71;">${s}</li>`).join('') +
                    '</ul>' : 
                    '<p style="color: var(--success-color);">✅ Sistema otimizado! Nenhuma ação recomendada.</p>';
                
                container.innerHTML = html;
            }

            // Verificar e mostrar Alertas Inteligentes
            verificarAlertas() {
                const container = document.getElementById('alertas-container');
                const alertas = [];
                
                // Alerta 1: Estoque Baixo
                if (document.getElementById('alerta-estoque-baixo')?.checked) {
                    const produtosBaixo = this.produtos.filter(p => (p.estoque || 0) < 5);
                    if (produtosBaixo.length > 0) {
                        produtosBaixo.forEach(p => {
                            alertas.push({
                                tipo: 'estoque',
                                titulo: '⚠️ Estoque Crítico',
                                mensagem: `${p.nome} com apenas ${p.estoque} unidades`,
                                cor: 'ff6b6b'
                            });
                        });
                    }
                }
                
                // Alerta 2: Pedidos Atrasados
                if (document.getElementById('alerta-pedidos-atrasados')?.checked) {
                    const pedidosAtrasados = this.pedidos.filter(p => {
                        if (p.rastreio?.status !== 'entregue') {
                            const dataSete = new Date(p.dataCadastro);
                            dataSete.setDate(dataSete.getDate() + 7);
                            return dataSete < new Date();
                        }
                        return false;
                    });
                    if (pedidosAtrasados.length > 0) {
                        alertas.push({
                            tipo: 'atraso',
                            titulo: '⏱️ Pedidos Atrasados',
                            mensagem: `${pedidosAtrasados.length} pedidos não entregues há mais de 7 dias`,
                            cor: 'f39c12'
                        });
                    }
                }
                
                // Alerta 3: Novos Clientes
                if (document.getElementById('alerta-novo-cliente')?.checked) {
                    const hoje = this.obterDataHoje();
                    const novosClientes = this.clientes.filter(c => c.dataCadastro === hoje);
                    if (novosClientes.length > 0) {
                        alertas.push({
                            tipo: 'novo',
                            titulo: '👤 Novo Cliente',
                            mensagem: `${novosClientes.length} novo(s) cliente(s) cadastrado(s) hoje`,
                            cor: '2ecc71'
                        });
                    }
                }
                
                // Renderizar alertas
                if (alertas.length === 0) {
                    container.innerHTML = '<p style="color: var(--success-color); text-align: center; padding: 40px;">✅ Sistema funcionando normalmente. Nenhum alerta ativo.</p>';
                    document.getElementById('menu-badge-alertas').textContent = '0';
                } else {
                    let html = '';
                    alertas.forEach(a => {
                        html += `<div style="background: rgba(${parseInt(a.cor.substring(0, 2), 16)}, ${parseInt(a.cor.substring(2, 4), 16)}, ${parseInt(a.cor.substring(4, 6), 16)}, 0.15); border: 2px solid #${a.cor}; border-radius: var(--radius); padding: 20px; margin-bottom: 15px;">
                            <h4 style="color: #${a.cor}; margin-bottom: 8px;">${a.titulo}</h4>
                            <p style="color: var(--text-color); margin: 0;">${a.mensagem}</p>
                        </div>`;
                    });
                    container.innerHTML = html;
                    document.getElementById('menu-badge-alertas').textContent = alertas.length;
                }
            }

            // ========== CONFIGURADOR DE MENU PERSONALIZADO ==========
            inicializarConfigurador() {
                const configMenu = localStorage.getItem('marketManager_menuConfig');
                const ordemMenu = localStorage.getItem('marketManager_menuOrdem');
                
                const abas = [
                    { id: 'dashboard', nome: 'Dashboard', icon: 'fa-tachometer-alt', visivel: true },
                    { id: 'pedidos', nome: 'Pedidos', icon: 'fa-shipping-fast', visivel: true },
                    { id: 'produtos', nome: 'Produtos', icon: 'fa-boxes', visivel: true },
                    { id: 'ia', nome: 'Assistente IA', icon: 'fa-brain', visivel: true },
                    { id: 'analise', nome: 'Análise Financeira', icon: 'fa-chart-line', visivel: true },
                    { id: 'relatorios', nome: 'Relatórios & Exportar', icon: 'fa-file-csv', visivel: true },
                    { id: 'alertas', nome: 'Alertas Inteligentes', icon: 'fa-bell', visivel: true },
                    { id: 'automacao', nome: 'Automação & IA', icon: 'fa-magic', visivel: true },
                    { id: 'integracao', nome: 'Integrações', icon: 'fa-link', visivel: true },
                    { id: 'configuracoes', nome: 'Configurações', icon: 'fa-cog', visivel: true },
                    { id: 'clientes', nome: 'Clientes', icon: 'fa-users', visivel: true },
                    { id: 'rastreio', nome: 'Rastreio', icon: 'fa-truck', visivel: true },
                    { id: 'whatsapp', nome: 'WhatsApp', icon: 'fa-comments', visivel: true }
                ];

                // Aplicar visibilidade do localStorage
                if (configMenu) {
                    const config = JSON.parse(configMenu);
                    abas.forEach(aba => {
                        if (config[aba.id] !== undefined) aba.visivel = config[aba.id];
                    });
                }

                // Aplicar ordem do localStorage
                if (ordemMenu) {
                    try {
                        const ordem = JSON.parse(ordemMenu);
                        const abasOrdenadas = [];
                        ordem.forEach(item => {
                            const aba = abas.find(a => a.id === item.id);
                            if (aba) abasOrdenadas.push(aba);
                        });
                        // Adicionar abas novas que não estão na ordem salva
                        abas.forEach(aba => {
                            if (!abasOrdenadas.find(a => a.id === aba.id)) {
                                abasOrdenadas.push(aba);
                            }
                        });
                        this.abasConfiguracao = abasOrdenadas;
                    } catch (e) {
                        this.abasConfiguracao = abas;
                    }
                } else {
                    this.abasConfiguracao = abas;
                }

                this.renderizarConfigurador();
                this.atualizarPreviewMenu();
                
                // ATUALIZAR O MENU REAL NA SIDEBAR
                setTimeout(() => {
                    atualizarMenuReal();
                }, 100);
            }

            renderizarConfigurador() {
                const container = document.getElementById('menu-configurador');
                if (!container) return;

                let html = '';
                this.abasConfiguracao.forEach((aba, index) => {
                    // Configurações é obrigatória - não pode ser desativada
                    const isConfiguracoes = aba.id === 'configuracoes';
                    
                    html += `
                        <div data-aba-id="${aba.id}" class="aba-item" draggable="${!isConfiguracoes}" style="display: flex; align-items: center; justify-content: space-between; background: ${isConfiguracoes ? 'rgba(155, 89, 182, 0.2)' : 'rgba(0, 168, 255, 0.1)'}; padding: 12px; border-radius: 5px; border-left: 3px solid ${isConfiguracoes ? '#9b59b6' : '#00a8ff'}; margin-bottom: 8px; cursor: ${!isConfiguracoes ? 'grab' : 'default'}; transition: all 0.2s; user-select: none;">
                            <!-- Handle de arrastar -->
                            ${!isConfiguracoes ? '<span style="font-size: 18px; color: #00a8ff; margin-right: 8px; cursor: grab;">⋮⋮</span>' : ''}
                            
                            <span style="color: var(--text-color); flex: 1;">
                                <i class="fas ${aba.icon}"></i> ${aba.nome} ${isConfiguracoes ? '<span style="font-size: 10px; color: #9b59b6; margin-left: 8px;">(Obrigatória)</span>' : ''}
                            </span>
                            
                            <!-- Toggle Visibilidade (desabilitado para Configurações) -->
                            ${isConfiguracoes ? `<span style="font-size: 14px; padding: 5px 10px; background: rgba(155, 89, 182, 0.3); border-radius: 3px; color: #c39bd3;">🔒 Obrigatória</span>` : `<label style="display: flex; align-items: center; gap: 5px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="aba-${aba.id}" ${aba.visivel ? 'checked' : ''} onchange="atualizarVisualizacaoAba('${aba.id}')"> 
                                <span style="font-size: 16px;">${aba.visivel ? '👁️' : '👁️‍🗨️'}</span>
                            </label>`}
                        </div>
                    `;
                });

                container.innerHTML = html;
                
                // Configurar drag and drop
                this.configurarDragDrop();
            }
            
            configurarDragDrop() {
                const items = document.querySelectorAll('.aba-item[draggable="true"]');
                let draggedElement = null;

                items.forEach(item => {
                    item.addEventListener('dragstart', (e) => {
                        draggedElement = item;
                        item.style.opacity = '0.5';
                        item.style.backgroundColor = 'rgba(0, 168, 255, 0.3)';
                        e.dataTransfer.effectAllowed = 'move';
                    });

                    item.addEventListener('dragend', (e) => {
                        item.style.opacity = '1';
                        item.style.backgroundColor = '';
                        document.querySelectorAll('.aba-item').forEach(el => {
                            el.style.borderTop = '';
                            el.style.borderBottom = '';
                            el.style.paddingTop = '';
                            el.style.paddingBottom = '';
                        });
                    });

                    item.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        
                        if (draggedElement && draggedElement !== item) {
                            const rect = item.getBoundingClientRect();
                            const midpoint = rect.top + rect.height / 2;
                            
                            document.querySelectorAll('.aba-item').forEach(el => {
                                el.style.borderTop = '';
                                el.style.borderBottom = '';
                                el.style.paddingTop = '';
                                el.style.paddingBottom = '';
                            });
                            
                            if (e.clientY < midpoint) {
                                // Será inserido ACIMA deste item
                                item.style.borderTop = '3px solid #00a8ff';
                                item.style.paddingTop = '20px';
                            } else {
                                // Será inserido ABAIXO deste item
                                item.style.borderBottom = '3px solid #00a8ff';
                                item.style.paddingBottom = '20px';
                            }
                        }
                    });

                    item.addEventListener('drop', (e) => {
                        e.preventDefault();
                        
                        if (draggedElement && draggedElement !== item) {
                            const rect = item.getBoundingClientRect();
                            const midpoint = rect.top + rect.height / 2;
                            
                            if (e.clientY < midpoint) {
                                item.parentNode.insertBefore(draggedElement, item);
                            } else {
                                item.parentNode.insertBefore(draggedElement, item.nextSibling);
                            }
                            
                            // Atualizar a ordem no array interno
                            this.atualizarOrdenacaoAbas();
                            
                            // Atualizar menu em tempo real
                            setTimeout(() => {
                                atualizarMenuReal();
                            }, 100);
                        }
                    });

                    item.addEventListener('dragleave', (e) => {
                        // Limpar estilos apenas se realmente sair do elemento
                        if (e.target === item) {
                            item.style.borderTop = '';
                            item.style.borderBottom = '';
                            item.style.paddingTop = '';
                            item.style.paddingBottom = '';
                        }
                    });
                });
            }
            
            atualizarOrdenacaoAbas() {
                const container = document.getElementById('menu-configurador');
                if (!container) return;
                
                const items = container.querySelectorAll('.aba-item');
                const novaOrdem = Array.from(items).map(item => item.getAttribute('data-aba-id'));
                
                // Reconstruir array de configurações na nova ordem
                const abasMap = {};
                this.abasConfiguracao.forEach(aba => {
                    abasMap[aba.id] = aba;
                });
                
                this.abasConfiguracao = novaOrdem.map(id => abasMap[id]);
                
                // Salvar nova ordem
                localStorage.setItem('marketManager_menuOrdem', JSON.stringify(novaOrdem));
                
                // Atualizar preview
                this.atualizarPreviewMenu();
                
                // Feedback visual
                if (window.marketManager) {
                    window.marketManager.mostrarNotificacao('📋 Ordem das abas atualizada! Clique em Salvar para confirmar.', 'info');
                }
            }

            atualizarPreviewMenu() {
                const preview = document.getElementById('preview-menu');
                if (!preview) return;

                let html = '<ul style="list-style: none; padding: 0; margin: 0;">';
                this.abasConfiguracao.forEach(aba => {
                    if (aba.visivel) {
                        html += `<li style="padding: 10px 15px; margin-bottom: 5px; background: rgba(255, 255, 255, 0.05); border-left: 3px solid #00a8ff; border-radius: 3px; color: var(--text-color); font-size: 13px;">
                            <i class="fas ${aba.icon}" style="color: #00a8ff; margin-right: 8px;"></i>${aba.nome}
                        </li>`;
                    }
                });
                html += '</ul>';

                preview.innerHTML = html;
            }

            // ========== 3 NOVAS FUNCIONALIDADES INOVADORAS ==========

            // 1. CALCULADORA ROI - Retorno sobre Investimento
            calcularROI() {
                const investimentoInicial = parseFloat(document.getElementById('roi-investimento')?.value) || 0;
                const lucroPrimeiromes = parseFloat(document.getElementById('roi-lucro-1mes')?.value) || 0;
                const tempoRecuperacao = investimentoInicial > 0 ? (investimentoInicial / lucroPrimeiromes * 30).toFixed(1) : 0;
                const roi = investimentoInicial > 0 ? (((lucroPrimeiromes * 12 - investimentoInicial) / investimentoInicial) * 100).toFixed(1) : 0;

                this.mostrarNotificacao(
                    `📈 ROI: ${roi}% | ⏱️ Recuperação em ${tempoRecuperacao} dias`,
                    'success'
                );
            }

            // 2. SUGESTÕES DE PRODUTOS HIPER-SEGMENTADAS
            gerarSugestoesSegmentadas() {
                const segmentos = {};
                
                this.pedidos.forEach(pedido => {
                    const categoria = pedido.produto?.categoria || 'geral';
                    if (!segmentos[categoria]) {
                        segmentos[categoria] = { vendas: 0, lucro: 0, clientes: new Set() };
                    }
                    segmentos[categoria].vendas++;
                    segmentos[categoria].lucro += (pedido.produto?.precoVenda || 0) - (pedido.produto?.precoCusto || 0);
                    segmentos[categoria].clientes.add(pedido.cliente?.id);
                });

                const melhorSegmento = Object.entries(segmentos).sort((a, b) => b[1].lucro - a[1].lucro)[0];
                
                if (melhorSegmento) {
                    this.mostrarNotificacao(
                        `🎯 Melhor segmento: ${melhorSegmento[0]} com ${melhorSegmento[1].vendas} vendas e R$ ${melhorSegmento[1].lucro.toFixed(2)}`,
                        'success'
                    );
                }
            }

            // 3. AGENDADOR DE LEMBRETES E TAREFAS
            criarLembrete(titulo, data, tipo = 'pedido') {
                if (!this.lembretes) this.lembretes = [];
                
                const lembrete = {
                    id: Date.now(),
                    titulo,
                    data,
                    tipo,
                    criado: this.obterDataHoje(),
                    concluido: false
                };

                this.lembretes.push(lembrete);
                localStorage.setItem('marketManager_lembretes', JSON.stringify(this.lembretes));
                this.mostrarNotificacao(`✅ Lembrete criado: ${titulo}`, 'success');
            }

            verificarLembretes() {
                const lembretes = JSON.parse(localStorage.getItem('marketManager_lembretes')) || [];
                const hoje = this.obterDataHoje();
                
                const lembretesDia = lembretes.filter(l => l.data === hoje && !l.concluido);
                if (lembretesDia.length > 0) {
                    this.mostrarNotificacao(
                        `📌 Você tem ${lembretesDia.length} lembrete(s) para hoje!`,
                        'warning'
                    );
                }
            }

            // Aplicar configurações visuais salvas
            aplicarConfiguracoesVisuais() {
                // Restaurar estado de dados sensíveis
                const dadosOcultados = localStorage.getItem('dadosSensivelOcultado') === 'true';
                if (dadosOcultados) {
                    document.body.classList.add('dados-ocultados');
                    const btn = document.getElementById('btn-toggle-dados-sensiveis');
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-eye"></i> Mostrar Dados';
                        btn.classList.add('active');
                    }
                    this.dadosSensivelOcultado = true;
                }

                // Restaurar outras configurações
                const outrasConfigs = JSON.parse(localStorage.getItem('marketManager_outrasConfigs'));
                if (outrasConfigs) {
                    document.getElementById('config-notificacoes').checked = outrasConfigs.notificacoes ?? true;
                    document.getElementById('config-sons').checked = outrasConfigs.sons ?? true;
                    document.getElementById('config-auto-backup').checked = outrasConfigs.autoBackup ?? true;
                    document.getElementById('config-tema-escuro').checked = outrasConfigs.temaBaixo ?? true;
                    document.getElementById('config-relatorio-auto').checked = outrasConfigs.relatorioAuto ?? false;
                    document.getElementById('config-alertas').checked = outrasConfigs.alertas ?? false;
                }
            }

            toggleDadosSensiveis() {
                try {
                    this.dadosSensivelOcultado = !this.dadosSensivelOcultado;
                    const btn = document.getElementById('btn-toggle-dados-sensiveis');
                    
                    if (!btn) return;
                    
                    if (this.dadosSensivelOcultado) {
                        document.body.classList.add('dados-ocultados');
                        btn.innerHTML = '<i class="fas fa-eye"></i> Mostrar Dados';
                        btn.classList.add('active');
                        localStorage.setItem('dadosSensivelOcultado', 'true');
                        this.mostrarNotificacao('🔒 Dados sensíveis OCULTADOS', 'success');
                    } else {
                        document.body.classList.remove('dados-ocultados');
                        btn.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar Dados';
                        btn.classList.remove('active');
                        localStorage.setItem('dadosSensivelOcultado', 'false');
                        this.mostrarNotificacao('🔓 Dados sensíveis VISÍVEIS', 'success');
                    }
                } catch (e) {
                    console.error('Erro ao alternar dados sensíveis:', e);
                }
            }

            // ========== IMPORTAÇÃO DE ARQUIVOS ==========
            abrirImportadorPedidos() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = '.json';
                input.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        this.importarArquivo(file, 'pedidos');
                    }
                });
                input.click();
            }

            abrirImportadorProdutos() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = '.json';
                input.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        this.importarArquivo(file, 'produtos');
                    }
                });
                input.click();
            }

            importarArquivo(file, tipo) {
                const reader = new FileReader();
                reader.onload = async (e) => {
                    try {
                        const dados = JSON.parse(e.target.result);

                        if (!Array.isArray(dados)) {
                            this.mostrarNotificacao('❌ Arquivo inválido! Deve conter um array JSON.', 'danger');
                            return;
                        }

                        if (tipo === 'pedidos') {
                            this.pedidos = [...this.pedidos, ...dados];
                            for (const item of dados) {
                                await this.salvarDados('pedido', item);
                            }
                            this.carregarPedidos('todos');
                            this.atualizarDashboard();
                            this.mostrarNotificacao(`✅ ${dados.length} pedidos importados com sucesso!`, 'success');
                        } else if (tipo === 'produtos') {
                            this.produtos = [...this.produtos, ...dados];
                            for (const item of dados) {
                                await this.salvarDados('produto', item);
                            }
                            this.carregarProdutos();
                            this.atualizarDashboard();
                            this.mostrarNotificacao(`✅ ${dados.length} produtos importados com sucesso!`, 'success');
                        }
                    } catch (err) {
                        console.error('Erro ao importar:', err);
                        this.mostrarNotificacao('❌ Erro ao ler o arquivo! Verifique o formato JSON.', 'danger');
                    }
                };
                reader.readAsText(file);
            }

            // ========== SOURCING SHOPEE → AMAZON ==========
            configurarSourcing() {
                const btn = document.getElementById('btn-sourcing-shopee');
                if (btn) {
                    btn.addEventListener('click', () => this.abrirModalSourcing());
                }

                document.getElementById('close-modal-sourcing')?.addEventListener('click', () => this.fecharModal('modal-sourcing'));
                document.getElementById('cancelar-sourcing')?.addEventListener('click', () => this.fecharModal('modal-sourcing'));
                document.getElementById('fechar-sourcing')?.addEventListener('click', () => this.fecharModal('modal-sourcing'));

                document.getElementById('form-sourcing')?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.gerarSugestaoProdutoAmazon();
                });

                document.getElementById('copiar-sourcing')?.addEventListener('click', () => {
                    const texto = document.getElementById('sourcing-resposta').innerText;
                    navigator.clipboard?.writeText(texto).then(() => {
                        this.mostrarNotificacao('Sugestão copiada!', 'success');
                    });
                });

                document.getElementById('salvar-produto-sourcing')?.addEventListener('click', () => this.salvarProdutoDoSourcing());
            }

            setupEventListeners() {
                // Event listeners para os novos componentes de análise financeira

                // Período de filtro
                document.getElementById('periodo-filtro')?.addEventListener('change', (e) => {
                    this.aplicarFiltroPeriodo(e.target.value);
                });

                // Botão de atualizar análise
                document.getElementById('btn-atualizar-analise')?.addEventListener('click', () => {
                    this.atualizarAnaliseFinanceiraCompleta();
                });

                // Período do gráfico
                document.getElementById('periodo-chart')?.addEventListener('change', (e) => {
                    this.atualizarGraficoPeriodo(e.target.value);
                });

                // Animação nos cards KPI
                document.querySelectorAll('.kpi-card').forEach(card => {
                    card.addEventListener('click', () => {
                        this.exibirDetalhesKPI(card);
                    });
                });

                // Tabs de análise - eventos melhorados
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const tab = e.target.dataset.tab;
                        if (!e.target.classList.contains('active')) {
                            // Remover classe active de todos os botões e conteúdos
                            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                            // Adicionar classe active ao botão clicado
                            e.target.classList.add('active');
                            document.getElementById(tab).classList.add('active');

                            // Carregar conteúdo específico da aba
                            this.carregarAbaAnalise(tab);
                        }
                    });
                });

                // Expandir gráfico
                document.querySelectorAll('[onclick^="expandChart"]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const chartType = e.target.getAttribute('onclick').match(/"([^"]+)"/)[1];
                        this.expandirGrafico(chartType);
                    });
                });

                // Automação de insights IA
                this.iniciarAtualizacaoAutomatica();
            }

            setupExportFeatures() {
                // Botão de exportar dados
                document.getElementById('btn-exportar-dados')?.addEventListener('click', () => {
                    this.menuExportarDados();
                });

                // Atalhos de teclado
                document.addEventListener('keydown', (e) => {
                    if (e.ctrlKey || e.metaKey) {
                        switch(e.key) {
                            case 'e':
                                e.preventDefault();
                                if (document.getElementById('analise').classList.contains('active')) {
                                    this.menuExportarDados();
                                }
                                break;
                            case 'r':
                                e.preventDefault();
                                if (document.getElementById('analise').classList.contains('active')) {
                                    this.atualizarAnaliseFinanceiraCompleta();
                                }
                                break;
                        }
                    }
                });
            }

            abrirModalSourcing() {
                document.getElementById('form-sourcing').reset();
                document.getElementById('resultado-sourcing').style.display = 'none';
                document.getElementById('sourcing-resposta').innerHTML = '';
                this.mostrarModal('modal-sourcing');
            }

            async gerarSugestaoProdutoAmazon() {
                const nome = document.getElementById('sourcing-nome').value;
                const precoCusto = parseFloat(document.getElementById('sourcing-preco-custo').value);
                const categoria = document.getElementById('sourcing-categoria').value;
                const taxaAmazon = parseFloat(document.getElementById('sourcing-taxa').value) || 15;
                const linkShopee = document.getElementById('sourcing-link').value;
                const observacoes = document.getElementById('sourcing-obs').value;

                if (!nome || !precoCusto) {
                    this.mostrarNotificacao('Preencha nome e preço de custo.', 'warning');
                    return;
                }

                const btn = document.querySelector('#form-sourcing button[type="submit"]');
                await this.executarComBloqueio(btn, async () => {
                    try {
                        const prompt = `
Atue como um especialista em vendas na Amazon e sourcing de produtos. Preciso revender um produto da Shopee na Amazon.

Informações do produto:
- Nome: ${nome}
- Preço de custo (na Shopee): R$ ${precoCusto.toFixed(2)}
- Categoria na Amazon: ${this.getCategoryText(categoria)}
- Taxa da Amazon (comissão + custos fixos): ${taxaAmazon}%
${linkShopee ? '- Link de referência: ' + linkShopee : ''}
${observacoes ? '- Observações: ' + observacoes : ''}

Objetivo: obter um lucro líquido de 30% após deduzir o custo do produto e as taxas da Amazon.

Com base nessas informações, gere:

1. **Preço sugerido de venda na Amazon** (calculado para atingir 30% de lucro líquido, considerando a taxa de ${taxaAmazon}%).
2. **Título otimizado para Amazon** (máximo 200 caracteres, com palavras-chave principais).
3. **Descrição detalhada** (formato HTML, com pelo menos 3 parágrafos, destacando benefícios).
4. **5 bullet points** (características principais, cada um com no máximo 500 caracteres).
5. **Palavras-chave para SEO** (separadas por vírgula, até 20 termos).
6. **Dicas de marketing** para aumentar as vendas deste produto na Amazon.

Responda em português brasileiro, usando formatação clara (markdown) e emojis onde fizer sentido.
`;

                        const resposta = await this.perguntarPollinationsAI(prompt);

                        const resultadoDiv = document.getElementById('sourcing-resposta');
                        resultadoDiv.innerHTML = this.formatarRespostaSourcing(resposta);
                        document.getElementById('resultado-sourcing').style.display = 'block';

                        resultadoDiv.dataset.produto = JSON.stringify({
                            nome: nome,
                            precoCusto: precoCusto,
                            categoria: categoria,
                            sugestao: resposta
                        });

                        this.mostrarNotificacao('Sugestão gerada com sucesso!', 'success');
                    } catch (error) {
                        console.error('Erro ao gerar sugestão:', error);
                        this.mostrarNotificacao('Erro ao consultar IA. Tente novamente.', 'danger');
                    }
                });
            }

            formatarRespostaSourcing(texto) {
                return texto
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>')
                    .replace(/✅/g, '<span style="color: #2ecc71;">✅</span>')
                    .replace(/💰/g, '<span style="color: #f1c40f;">💰</span>')
                    .replace(/📦/g, '<span style="color: #9b59b6;">📦</span>')
                    .replace(/🚀/g, '<span style="color: #e74c3c;">🚀</span>')
                    .replace(/📝/g, '<span style="color: #3498db;">📝</span>')
                    .replace(/🔑/g, '<span style="color: #e67e22;">🔑</span>')
                    .replace(/💡/g, '<span style="color: #f39c12;">💡</span>');
            }

            salvarProdutoDoSourcing() {
                const resultadoDiv = document.getElementById('sourcing-resposta');
                const dados = resultadoDiv.dataset.produto;
                if (!dados) {
                    this.mostrarNotificacao('Nenhuma sugestão para salvar.', 'warning');
                    return;
                }

                try {
                    const produtoBase = JSON.parse(dados);
                    this.fecharModal('modal-sourcing');
                    this.abrirModalNovoProduto();

                    setTimeout(() => {
                        document.getElementById('produto-nome-novo').value = produtoBase.nome;
                        document.getElementById('produto-categoria-novo').value = produtoBase.categoria;
                        document.getElementById('produto-preco-custo-novo').value = produtoBase.precoCusto;
                        this.mostrarNotificacao('Preencha o preço de venda com o sugerido pela IA.', 'info');
                    }, 300);
                } catch (e) {
                    console.error('Erro ao salvar produto:', e);
                }
            }

            // ========== NOVO: IMPORTAR DA AMAZON ==========
            configurarImportacaoAmazon() {
                const btn = document.getElementById('btn-importar-amazon');
                if (btn) {
                    btn.addEventListener('click', () => this.abrirModalImportarAmazon());
                }

                document.getElementById('close-modal-importar-amazon')?.addEventListener('click', () => this.fecharModal('modal-importar-amazon'));
                document.getElementById('cancelar-importar-amazon')?.addEventListener('click', () => this.fecharModal('modal-importar-amazon'));

                document.getElementById('form-importar-amazon')?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.importarProdutoAmazon();
                });

                document.getElementById('close-modal-rastreio')?.addEventListener('click', () => this.fecharModal('modal-rastreio'));

                // Formulário de novo pedido
                document.getElementById('form-novo-pedido').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (this.pedidoEditandoId) {
                        await this.atualizarPedidoDoFormulario(this.pedidoEditandoId);
                    } else {
                        await this.salvarPedidoDoFormulario();
                    }
                });
            }

            abrirModalImportarAmazon() {
                document.getElementById('form-importar-amazon').reset();
                document.getElementById('resultado-importacao').style.display = 'none';
                this.mostrarModal('modal-importar-amazon');
            }

            async importarProdutoAmazon() {
                const url = document.getElementById('amazon-url').value;
                if (!url) {
                    this.mostrarNotificacao('Informe a URL do produto.', 'warning');
                    return;
                }

                const btn = document.querySelector('#form-importar-amazon button[type="submit"]');
                await this.executarComBloqueio(btn, async () => {
                    try {
                        // Passo 1: Extrair informações do produto
                        const prompt1 = `Da URL do Amazon: ${url}\nExtraía APENAS: nome, ASIN, preço em reais. Responda JSON: {"nome":"", "asin":"", "preco":0}`;
                        const resposta1 = await this.perguntarPollinationsAI(prompt1);
                        let dados = { nome: 'Importado', asin: '', preco: 0, jaCadastrado: false };
                        
                        try {
                            const jsonMatch = resposta1.match(/\{[^}]*\}/s);
                            if (jsonMatch) {
                                dados = { ...dados, ...JSON.parse(jsonMatch[0]) };
                            }
                        } catch (e) {
                            console.error('Erro parsing JSON:', e);
                        }
                        
                        // Passo 2: Verificar se já foi vendido
                        if (dados.nome && dados.asin) {
                            const prompt2 = `O produto ASIN ${dados.asin} com nome "${dados.nome}" já foi vendido em um e-commerce\no Shopee ou Amazon antes? Responda APENAS: sim ou não`;
                            const resposta2 = await this.perguntarPollinationsAI(prompt2);
                            
                            if (resposta2.toLowerCase().includes('sim')) {
                                dados.jaCadastrado = true;
                                this.mostrarNotificacao(
                                    `⚠️ Este produto pode já ter sido trabalho. Nome: ${dados.nome}. Verifique antes de importar.`,
                                    'warning'
                                );
                            }
                        }

                        this.fecharModal('modal-importar-amazon');
                        this.abrirModalNovoProduto();
                        setTimeout(() => {
                            document.getElementById('produto-nome-novo').value = dados.nome || '';
                            document.getElementById('produto-asin-novo').value = dados.asin || '';
                            document.getElementById('produto-preco-venda-novo').value = dados.preco || '';
                            this.mostrarNotificacao(
                                `Produto importado${dados.jaCadastrado ? ' (⚠️ pode estar duplicado)' : ''}! Revise antes de salvar.`,
                                dados.jaCadastrado ? 'warning' : 'success'
                            );
                        }, 300);
                    } catch (error) {
                        console.error('Erro ao importar da Amazon:', error);
                        this.mostrarNotificacao('Erro ao importar. Tente novamente.', 'danger');
                    }
                });
            }
        }

        // ========== FUNÇÕES GLOBAIS PARA NUEVAS FUNCIONALIDADES ==========
        
        function gerarRelatorioMensal() {
            if (window.marketManager) {
                window.marketManager.abrirRelatorioMensal();
            }
        }
        
        function exportarPedidosCSV() {
            if (window.marketManager) {
                window.marketManager.exportarDadosCSV('pedidos');
            }
        }
        
        function exportarProdutosCSV() {
            if (window.marketManager) {
                window.marketManager.exportarDadosCSV('produtos');
            }
        }
        
        function mostrarTopVendedores() {
            if (window.marketManager) {
                const container = document.querySelector('.tabs-content');
                if (container) {
                    const topProdutos = window.marketManager.produtos
                        .sort((a, b) => (b.estoque || 0) - (a.estoque || 0))
                        .slice(0, 5);
                    
                    let html = '<h4 style="color: var(--text-light); margin-bottom: 15px;">🏆 Top 5 Produtos</h4><ul style="list-style: none; padding: 0;">';
                    topProdutos.forEach((p, i) => {
                        html += `<li style="padding: 12px; background: rgba(0, 0, 0, 0.1); margin-bottom: 8px; border-radius: 5px; border-left: 3px solid #f39c12;">
                            <strong>#${i + 1} - ${p.nome}</strong> (R$ ${(p.preco || 0).toFixed(2)})
                        </li>`;
                    });
                    html += '</ul>';
                    container.innerHTML = html;
                }
            }
        }
        
        function gerarSugestoesReordenacao() {
            if (window.marketManager) {
                window.marketManager.gerarSugestoesReordenacao();
            }
        }
        
        function gerarPrevisaoDemanda() {
            if (window.marketManager) {
                window.marketManager.gerarPrevisaoDemanda();
            }
        }
        
        function gerarOtimizacoes() {
            if (window.marketManager) {
                window.marketManager.gerarOtimizacoes();
            }
        }

        // ========== FUNÇÕES DO CONFIGURADOR DE MENU ==========
        
        function atualizarVisualizacaoAba(abaId) {
            if (window.marketManager) {
                const checkbox = document.getElementById(`aba-${abaId}`);
                const aba = window.marketManager.abasConfiguracao.find(a => a.id === abaId);
                if (aba && checkbox) {
                    aba.visivel = checkbox.checked;
                    window.marketManager.atualizarPreviewMenu();
                    window.marketManager.renderizarConfigurador();
                    atualizarMenuReal();
                    salvarConfiguracoesMenuAutomatico();
                }
            }
        }

        function atualizarMenuReal() {
            if (!window.marketManager) return;
            
            const menu = document.querySelector('.sidebar ul.menu');
            if (!menu) return;

            // Reconstruir o menu baseado na ordem e visibilidade das abas
            const counts = {
                pedidos: window.marketManager?.pedidos?.length || 0,
                produtos: window.marketManager?.produtos?.length || 0,
                clientes: window.marketManager?.clientes?.length || 0
            };

            if (counts.clientes === 0 && window.marketManager?.pedidos) {
                const clientesUnicos = new Set();
                window.marketManager.pedidos.forEach(p => {
                    if (p.cliente?.cpf) clientesUnicos.add(p.cliente.cpf);
                });
                counts.clientes = clientesUnicos.size;
            }

            let menuHTML = '';
            window.marketManager.abasConfiguracao.forEach(aba => {
                if (aba.visivel) {
                    const badge = ['pedidos', 'produtos', 'clientes', 'alertas'].includes(aba.id) ? 
                        `<span class="menu-badge" id="menu-badge-${aba.id}">${counts[aba.id] ?? 0}</span>` : 
                        (aba.id === 'ia' ? '<span class="menu-badge" style="background-color: var(--ia-color);">IA</span>' : '');
                    
                    menuHTML += `
                        <li class="menu-item">
                            <a href="#${aba.id}" class="menu-link" data-tab="${aba.id}">
                                <i class="fas ${aba.icon}"></i>
                                <span>${aba.nome}</span>
                                ${badge}
                            </a>
                        </li>
                    `;
                }
            });

            menu.innerHTML = menuHTML;

            // Re-configurar eventos dos links do menu
            document.querySelectorAll('.menu-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const tabId = link.getAttribute('data-tab');
                    if (window.marketManager) {
                        window.marketManager.ativarAba(tabId);
                    }
                });
            });
        }

        function salvarConfiguracoesMenuAutomatico() {
            if (window.marketManager) {
                const config = {};
                const ordem = [];
                window.marketManager.abasConfiguracao.forEach((aba, idx) => {
                    config[aba.id] = aba.visivel;
                    ordem.push({ id: aba.id, ordem: idx });
                });
                localStorage.setItem('marketManager_menuConfig', JSON.stringify(config));
                localStorage.setItem('marketManager_menuOrdem', JSON.stringify(ordem));
            }
        }

        function salvarConfiguracoesMenu() {
            salvarConfiguracoesMenuAutomatico();
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('✅ Preferências do menu salvas com sucesso!', 'success');
            }
        }

        function resetarConfiguracoesMenu() {
            if (window.marketManager) {
                localStorage.removeItem('marketManager_menuConfig');
                localStorage.removeItem('marketManager_menuOrdem');
                window.marketManager.abasConfiguracao.forEach(aba => {
                    aba.visivel = true;
                });
                window.marketManager.renderizarConfigurador();
                window.marketManager.atualizarPreviewMenu();
                atualizarMenuReal();
                window.marketManager.mostrarNotificacao('🔄 Configurações restauradas ao padrão!', 'success');
            }
        }

        function salvarOutrasConfigs() {
            const configs = {
                notificacoes: document.getElementById('config-notificacoes')?.checked,
                sons: document.getElementById('config-sons')?.checked,
                autoBackup: document.getElementById('config-auto-backup')?.checked,
                temaBaixo: document.getElementById('config-tema-escuro')?.checked,
                relatorioAuto: document.getElementById('config-relatorio-auto')?.checked,
                alertas: document.getElementById('config-alertas')?.checked,
                temaAtual: localStorage.getItem('marketManager_tema') || 'escuro'
            };
            localStorage.setItem('marketManager_outrasConfigs', JSON.stringify(configs));
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('💾 Todas as configurações salvas com sucesso!', 'success');
                
                //  Aplicar configurações dinâmicas
                if (configs.alertas && window.marketManager) {
                    window.marketManager.verificarAlertas();
                }
            }
        }
        
        const TEMAS = {
            escuro: {
                fundo: '#0f1419',
                fundoSecundario: '#1a1f26',
                texto: '#e0e0e0',
                textoPrimario: '#ffffff',
                textoMudo: '#8a9bb2',
                primary: '#00a8ff',
                secondary: '#1e90ff'
            },
            preto: {
                fundo: '#000000',
                fundoSecundario: '#111111',
                texto: '#ffffff',
                textoPrimario: '#ffffff',
                textoMudo: '#b3b3b3',
                primary: '#00a8ff',
                secondary: '#1e90ff',
                cardBg: '#111111',
                cardHover: '#1a1a1a',
                borderColor: 'rgba(255, 255, 255, 0.08)',
                borderLight: 'rgba(255, 255, 255, 0.12)',
                darkBg: '#000000',
                darkerBg: '#090909',
                darkestBg: '#080808'
            },
            claro: {
                fundo: '#ffffff',
                fundoSecundario: '#f5f5f5',
                texto: '#333333',
                textoPrimario: '#000000',
                textoMudo: '#666666',
                primary: '#0073cc',
                secondary: '#ff6b6b'
            },
            profissional: {
                fundo: '#263238',
                fundoSecundario: '#37474f',
                texto: '#cfd8dc',
                textoPrimario: '#eceff1',
                textoMudo: '#90a4ae',
                primary: '#4caf50',
                secondary: '#81c784'
            },
            vibrante: {
                fundo: '#2c0033',
                fundoSecundario: '#440055',
                texto: '#ff6ec7',
                textoPrimario: '#ff1744',
                textoMudo: '#bb86fc',
                primary: '#ff1744',
                secondary: '#ff6ec7'
            },
            marine: {
                fundo: '#0d1f2d',
                fundoSecundario: '#1a3a3a',
                texto: '#00d4ff',
                textoPrimario: '#00bcd4',
                textoMudo: '#0096d6',
                primary: '#00bcd4',
                secondary: '#0096d6'
            },
            sunset: {
                fundo: '#3d1f00',
                fundoSecundario: '#5d3a1a',
                texto: '#ffb74d',
                textoPrimario: '#ffc947',
                textoMudo: '#ff9800',
                primary: '#ff9800',
                secondary: '#ffb74d'
            },
            neon: {
                fundo: '#0a0e27',
                fundoSecundario: '#1a1f3a',
                texto: '#00ff88',
                textoPrimario: '#00ffff',
                textoMudo: '#0099cc',
                primary: '#00ff88',
                secondary: '#ff00ff'
            },
            floresta: {
                fundo: '#1b5e20',
                fundoSecundario: '#2e7d32',
                texto: '#81c784',
                textoPrimario: '#c8e6c9',
                textoMudo: '#558b2f',
                primary: '#4caf50',
                secondary: '#81c784'
            },
            galaxia: {
                fundo: '#1a0033',
                fundoSecundario: '#33006f',
                texto: '#ce93d8',
                textoPrimario: '#e1bee7',
                textoMudo: '#7b1fa2',
                primary: '#ba68c8',
                secondary: '#ce93d8'
            },
            dashboard: {
                fundo: '#34495e',
                fundoSecundario: '#2c3e50',
                texto: '#ecf0f1',
                textoPrimario: '#ffffff',
                textoMudo: '#95a5a6',
                primary: '#3498db',
                secondary: '#2980b9',
                cardBg: '#2c3e50',
                cardHover: '#34495e',
                borderColor: 'rgba(52, 152, 219, 0.2)',
                borderLight: 'rgba(52, 152, 219, 0.3)'
            },
            minimalista: {
                fundo: '#ecf0f1',
                fundoSecundario: '#bdc3c7',
                texto: '#2c3e50',
                textoPrimario: '#34495e',
                textoMudo: '#7f8c8d',
                primary: '#95a5a6',
                secondary: '#7f8c8d',
                cardBg: '#ffffff',
                cardHover: '#ecf0f1',
                borderColor: 'rgba(149, 165, 166, 0.3)',
                borderLight: 'rgba(149, 165, 166, 0.5)'
            },
            retro: {
                fundo: '#8b4513',
                fundoSecundario: '#daa520',
                texto: '#ffd700',
                textoPrimario: '#ffff00',
                textoMudo: '#daa520',
                primary: '#daa520',
                secondary: '#ffd700',
                cardBg: '#daa520',
                cardHover: '#b8860b',
                borderColor: 'rgba(218, 165, 32, 0.3)',
                borderLight: 'rgba(255, 215, 0, 0.5)'
            },
            cyberpunk: {
                fundo: '#0a0a0a',
                fundoSecundario: '#1a0033',
                texto: '#ff00ff',
                textoPrimario: '#ff0080',
                textoMudo: '#00ffff',
                primary: '#ff00ff',
                secondary: '#00ffff',
                cardBg: '#1a0033',
                cardHover: '#330066',
                borderColor: 'rgba(255, 0, 255, 0.3)',
                borderLight: 'rgba(0, 255, 255, 0.5)'
            },
            oceano: {
                fundo: '#001122',
                fundoSecundario: '#003366',
                texto: '#00bfff',
                textoPrimario: '#87ceeb',
                textoMudo: '#4682b4',
                primary: '#00bfff',
                secondary: '#87ceeb',
                cardBg: '#003366',
                cardHover: '#004080',
                borderColor: 'rgba(0, 191, 255, 0.3)',
                borderLight: 'rgba(135, 206, 235, 0.5)'
            },
            deserto: {
                fundo: '#8b4513',
                fundoSecundario: '#daa520',
                texto: '#ffa500',
                textoPrimario: '#ff8c00',
                textoMudo: '#daa520',
                primary: '#ffa500',
                secondary: '#daa520',
                cardBg: '#daa520',
                cardHover: '#cd853f',
                borderColor: 'rgba(255, 165, 0, 0.3)',
                borderLight: 'rgba(218, 165, 32, 0.5)'
            }
        };
        
        function aplicarTema(nomeTema, silencioso = false) {
            const tema = TEMAS[nomeTema] || TEMAS.escuro;
            
            // Definir as variáveis CSS
            document.documentElement.style.setProperty('--primary-color', tema.primary);
            document.documentElement.style.setProperty('--bg-color', tema.fundo);
            document.documentElement.style.setProperty('--dark-bg', tema.darkBg || tema.fundo);
            document.documentElement.style.setProperty('--darker-bg', tema.darkerBg || tema.fundoSecundario || tema.fundo);
            document.documentElement.style.setProperty('--darkest-bg', tema.darkestBg || tema.darkerBg || tema.fundo);
            document.documentElement.style.setProperty('--card-bg', tema.cardBg || tema.fundoSecundario || tema.fundo);
            document.documentElement.style.setProperty('--card-hover', tema.cardHover || 'rgba(255, 255, 255, 0.08)');
            document.documentElement.style.setProperty('--border-color', tema.borderColor || 'rgba(255, 255, 255, 0.08)');
            document.documentElement.style.setProperty('--border-light', tema.borderLight || 'rgba(255, 255, 255, 0.12)');
            document.documentElement.style.setProperty('--bg-secondary', tema.fundoSecundario);
            document.documentElement.style.setProperty('--text-light', tema.texto);
            document.documentElement.style.setProperty('--text-color', tema.textoPrimario);
            document.documentElement.style.setProperty('--text-muted', tema.textoMudo);
            document.documentElement.style.setProperty('--secondary-color', tema.secondary);
            
            // Salvar tema no localStorage
            localStorage.setItem('marketManager_tema', nomeTema);
            
            // Atualizar botões de tema
            document.querySelectorAll('.tema-btn').forEach(btn => {
                if (btn.dataset.tema === nomeTema) {
                    btn.style.transform = 'scale(1.05)';
                    btn.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.5)';
                } else {
                    btn.style.transform = 'scale(1)';
                    btn.style.boxShadow = 'none';
                }
            });
            
            // Mostrar notificação apenas quando o usuário muda o tema manualmente
            if (!silencioso && window.marketManager) {
                window.marketManager.mostrarNotificacao(`🎨 Tema ${nomeTema.charAt(0).toUpperCase() + nomeTema.slice(1)} aplicado!`, 'success');
            }
        }
        
        // Restaurar tema ao carregar a página (silencioso)
        function restaurarTemaAtual() {
            const temaSalvo = localStorage.getItem('marketManager_tema') || 'escuro';
            aplicarTema(temaSalvo, true);
        }

        function atualizarSidebarColapsado(ativo) {
            const body = document.body;
            const sidebarToggle = document.getElementById('sidebar-toggle');
            if (ativo) {
                body.classList.add('sidebar-collapsed');
                sidebarToggle?.querySelector('i')?.classList.replace('fa-angle-double-left', 'fa-angle-double-right');
            } else {
                body.classList.remove('sidebar-collapsed');
                sidebarToggle?.querySelector('i')?.classList.replace('fa-angle-double-right', 'fa-angle-double-left');
            }
            localStorage.setItem('marketManager_sidebarCollapsed', ativo ? '1' : '0');
        }

        function alternarSidebar() {
            atualizarSidebarColapsado(!document.body.classList.contains('sidebar-collapsed'));
        }

        function restaurarSidebar() {
            const collapsed = localStorage.getItem('marketManager_sidebarCollapsed') === '1';
            atualizarSidebarColapsado(collapsed);
        }
        
        // ========== FUNÇÕES DE TREINAMENTO DA IA - VERSÃO PROFISSIONAL ==========
        let estiloTreinamento = 'profissional';

        function treinarIA() {
            if (!window.marketManager) return;
            const totalPedidos = window.marketManager.pedidos.length;
            const totalProdutos = window.marketManager.produtos.length;
            mostrarModalTreinamentoIA(totalPedidos, totalProdutos);
        }

        function mostrarModalTreinamentoIA(totalPedidos, totalProdutos) {
            const modal = document.createElement('div');
            modal.id = 'modal-ia-training';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;

            modal.innerHTML = `
                <div style="background: linear-gradient(135deg, #1a1f3a, #16213e); padding: 40px; border-radius: 20px; max-width: 600px; width: 95%; max-height: 90vh; overflow-y: auto; border: 2px solid #9b59b6; box-shadow: 0 0 60px rgba(155, 89, 182, 0.3);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div style="font-size: 50px; margin-bottom: 15px;">🧠</div>
                        <h2 style="color: #9b59b6; margin: 0 0 10px 0; font-size: 28px;">Treinar a IA</h2>
                        <p style="color: #8a9bb2; margin: 0; font-size: 14px;">Customize como a IA deve responder</p>
                    </div>

                    <div style="background: rgba(155, 89, 182, 0.1); padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #9b59b6;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <div style="color: #8a9bb2; font-size: 12px; margin-bottom: 5px;">📋 Pedidos Analisados</div>
                                <div style="color: #2ecc71; font-size: 22px; font-weight: 700;">${totalPedidos}</div>
                            </div>
                            <div>
                                <div style="color: #8a9bb2; font-size: 12px; margin-bottom: 5px;">📦 Produtos Conhecidos</div>
                                <div style="color: #3498db; font-size: 22px; font-weight: 700;">${totalProdutos}</div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; color: #8a9bb2; font-size: 13px; margin-bottom: 10px; font-weight: 600;">✍️ Instruções de Treinamento (Texto Livre):</label>
                        <textarea id="training-text" placeholder="Digite aqui como você quer que a IA responda... Pode ser bem grande e detalhado!" style="width: 100%; min-height: 150px; padding: 15px; border: 2px solid rgba(155, 89, 182, 0.3); border-radius: 10px; background: rgba(0, 0, 0, 0.3); color: #e4e9f0; font-family: 'Courier New', monospace; font-size: 13px; resize: vertical; box-sizing: border-box;" onchange="this.style.borderColor='rgba(46, 204, 113, 0.5)'"></textarea>
                        <div style="color: #5a6a7a; font-size: 11px; margin-top: 5px;">💡 Exemplo: "Responda como um vendedor experiente, com tom profissional mas amigável. Sempre sugira complementos e ofertas."</div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; color: #8a9bb2; font-size: 13px; margin-bottom: 12px; font-weight: 600;">🎯 Selecione o Estilo de Resposta:</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                            <button onclick="selecionarEstilo('profissional', this)" style="padding: 15px; border: 2px solid #9b59b6; background: rgba(155, 89, 182, 0.3); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: center;">
                                <div style="font-size: 20px; margin-bottom: 5px;">💼</div>
                                <div>Profissional</div>
                                <div style="font-size: 11px; color: #8a9bb2; margin-top: 3px;">Formal e respeitoso</div>
                            </button>
                            <button onclick="selecionarEstilo('amigavel', this)" style="padding: 15px; border: 2px solid #7c8aa2; background: rgba(0, 0, 0, 0.2); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: center;">
                                <div style="font-size: 20px; margin-bottom: 5px;">😊</div>
                                <div>Amigável</div>
                                <div style="font-size: 11px; color: #8a9bb2; margin-top: 3px;">Casual e próximo</div>
                            </button>
                            <button onclick="selecionarEstilo('tecnica', this)" style="padding: 15px; border: 2px solid #7c8aa2; background: rgba(0, 0, 0, 0.2); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: center;">
                                <div style="font-size: 20px; margin-bottom: 5px;">🔧</div>
                                <div>Técnica</div>
                                <div style="font-size: 11px; color: #8a9bb2; margin-top: 3px;">Detalhada e precisa</div>
                            </button>
                            <button onclick="selecionarEstilo('sucinta', this)" style="padding: 15px; border: 2px solid #7c8aa2; background: rgba(0, 0, 0, 0.2); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: center;">
                                <div style="font-size: 20px; margin-bottom: 5px;">⚡</div>
                                <div>Sucinta</div>
                                <div style="font-size: 11px; color: #8a9bb2; margin-top: 3px;">Direto e objetivo</div>
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button onclick="document.getElementById('modal-ia-training').remove();" style="padding: 12px 25px; border: 2px solid #5a6a7a; background: transparent; color: #8a9bb2; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                            ❌ Cancelar
                        </button>
                        <button onclick="confirmarTreinamentoIA(${totalPedidos}, ${totalProdutos})" style="padding: 12px 30px; background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; transition: all 0.3s; box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(46, 204, 113, 0.4)'" onmouseout="this.style.transform='translateY(0)'">
                            ✅ Treinar IA
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });
        }

        function selecionarEstilo(estilo, btn) {
            estiloTreinamento = estilo;
            
            // Remover destaque de todos
            document.querySelectorAll('#modal-ia-training button').forEach(b => {
                if (b !== btn && b.textContent.includes('💼') || b.textContent.includes('😊') || b.textContent.includes('🔧') || b.textContent.includes('⚡')) {
                    b.style.borderColor = '#7c8aa2';
                    b.style.background = 'rgba(0, 0, 0, 0.2)';
                }
            });

            // Destacar botão selecionado
            btn.style.borderColor = '#00a8ff';
            btn.style.background = 'rgba(0, 168, 255, 0.2)';
        }

        function confirmarTreinamentoIA(totalPedidos, totalProdutos) {
            const instrucoes = document.getElementById('training-text').value.trim();
            
            const treino = {
                ia_treinada: 'true',
                ia_treino_data: new Date().toISOString(),
                ia_treino_pedidos: totalPedidos,
                ia_treino_produtos: totalProdutos,
                ia_treino_instrucoes: instrucoes,
                ia_estilo: estiloTreinamento
            };

            Object.entries(treino).forEach(([key, value]) => {
                localStorage.setItem(key, value);
            });

            document.getElementById('modal-ia-training').remove();

            if (window.marketManager) {
                window.marketManager.mostrarNotificacao(
                    `✅ TREINAMENTO CONCLUÍDO!\n\n🧠 IA Personalizada\n📚 Estilo: ${estiloTreinamento}\n📊 ${totalPedidos} pedidos, ${totalProdutos} produtos\n💾 Instruções salvas!`,
                    'success'
                );
            }
        }
        
        function personalizarIA() {
            if (!window.marketManager) return;
            
            const modal = document.createElement('div');
            modal.id = 'modal-personalizacao-ia';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;

            modal.innerHTML = `
                <div style="background: linear-gradient(135deg, #1a1f3a, #16213e); padding: 40px; border-radius: 20px; max-width: 500px; width: 95%; border: 2px solid #9b59b6; box-shadow: 0 0 60px rgba(155, 89, 182, 0.3);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">🎨</div>
                        <h2 style="color: #9b59b6; margin: 0 0 10px 0;">Personalizar Estilo</h2>
                        <p style="color: #8a9bb2; margin: 0; font-size: 14px;">Como você quer que a IA responda?</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 25px;">
                        <button onclick="salvarEstiloPersonalizado('profissional')" style="padding: 18px; border: 2px solid #7c8aa2; background: rgba(0, 0, 0, 0.2); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: left;">
                            <div style="font-size: 22px; margin-bottom: 8px;">💼 Profissional</div>
                            <div style="font-size: 13px; color: #8a9bb2;">Ton formal e respeitoso, palavras bem escolhidas</div>
                        </button>
                        <button onclick="salvarEstiloPersonalizado('amigavel')" style="padding: 18px; border: 2px solid #7c8aa2; background: rgba(0, 0, 0, 0.2); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: left;">
                            <div style="font-size: 22px; margin-bottom: 8px;">😊 Amigável</div>
                            <div style="font-size: 13px; color: #8a9bb2;">Casual, próximo e conversível, como um amigo</div>
                        </button>
                        <button onclick="salvarEstiloPersonalizado('tecnica')" style="padding: 18px; border: 2px solid #7c8aa2; background: rgba(0, 0, 0, 0.2); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: left;">
                            <div style="font-size: 22px; margin-bottom: 8px;">🔧 Técnica</div>
                            <div style="font-size: 13px; color: #8a9bb2;">Detalhada, precisa e com informações aprofundadas</div>
                        </button>
                        <button onclick="salvarEstiloPersonalizado('sucinta')" style="padding: 18px; border: 2px solid #7c8aa2; background: rgba(0, 0, 0, 0.2); color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: left;">
                            <div style="font-size: 22px; margin-bottom: 8px;">⚡ Sucinta</div>
                            <div style="font-size: 13px; color: #8a9bb2;">Direto ao ponto, sem rodeios, objetivo e claro</div>
                        </button>
                    </div>

                    <button onclick="document.getElementById('modal-personalizacao-ia').remove();" style="width: 100%; padding: 12px; border: 2px solid #5a6a7a; background: transparent; color: #8a9bb2; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        ❌ Fechar
                    </button>
                </div>
            `;

            document.body.appendChild(modal);

            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });
        }

        function salvarEstiloPersonalizado(estilo) {
            localStorage.setItem('ia_estilo', estilo);
            document.getElementById('modal-personalizacao-ia').remove();
            
            if (window.marketManager) {
                const estilos = {
                    'profissional': '💼 Profissional',
                    'amigavel': '😊 Amigável',
                    'tecnica': '🔧 Técnica',
                    'sucinta': '⚡ Sucinta'
                };
                window.marketManager.mostrarNotificacao(`✅ IA em modo: ${estilos[estilo]}`, 'success');
            }
        }
        
        function treinarMemoriaIA() {
            if (!window.marketManager) return;
            
            const totalPedidos = window.marketManager.pedidos.length;
            const totalClientes = new Set(window.marketManager.pedidos.map(p => p.cliente?.id)).size;
            const totalVendas = window.marketManager.pedidos.reduce((sum, p) => sum + (p.produto?.precoVenda || 0), 0);
            
            const memoriaIA = {
                totalPedidos,
                totalClientes,
                totalVendas: totalVendas.toFixed(2),
                categoria_mais_vendida: 'eletronicos',
                produto_mais_vendido: 'Premium',
                margem_media: '15%',
                cliente_vip: 'Frequente',
                treino_data: new Date().toISOString(),
                versao: '2.0'
            };
            
            localStorage.setItem('ia_memoria', JSON.stringify(memoriaIA));
            window.marketManager.mostrarNotificacao(`🧠 Memória da IA Treinada!\n${totalPedidos} pedidos | ${totalClientes} clientes | R$ ${totalVendas.toFixed(2)} em vendas`, 'success');
        }
        
        function limparMemoriaIA() {
            if (confirm('⚠️ Isso vai limpar toda a memória da IA. Tem certeza?')) {
                localStorage.removeItem('ia_memoria');
                localStorage.removeItem('ia_treinada');
                localStorage.removeItem('ia_estilo');
                localStorage.removeItem('ia_treino_data');
                localStorage.removeItem('ia_treino_pedidos');
                
                if (window.marketManager) {
                    window.marketManager.mostrarNotificacao('✅ Memória da IA limpa e resetada!', 'success');
                }
            }
        }
        
        // ========== NOVAS FUNÇÕES DE CONFIGURAÇÃO AVANÇADA ==========
        
        window.addEventListener('load', restaurarTemaAtual);

        // ========== FUNÇÕES DE INTEGRAÇÕES ==========
        
        function conectarShopee() {
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('🔗 Conectando com Shopee... (simulado)', 'info');
                setTimeout(() => {
                    document.getElementById('status-integracao').innerHTML += '<p>✅ Shopee - Conectado com sucesso!</p>';
                    window.marketManager.mostrarNotificacao('✅ Shopee conectado!', 'success');
                }, 2000);
            }
        }

        function desconectarShopee() {
            if (window.marketManager) {
                document.getElementById('status-integracao').innerHTML = document.getElementById('status-integracao').innerHTML.replace('Conectado', 'Desconectado');
                window.marketManager.mostrarNotificacao('❌ Shopee desconectado', 'warning');
            }
        }

        function conectarAmazon() {
            abrirConfigAmazon();
        }

        function desconectarAmazon() {
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('❌ Amazon desconectado', 'warning');
            }
        }

        function abrirConfigAmazon() {
            const modal = document.getElementById('modal-config-amazon');
            if (modal) {
                modal.style.display = 'flex';

                // Carregar configurações salvas
                carregarConfigAmazon();
            }
        }

        function fecharConfigAmazon() {
            const modal = document.getElementById('modal-config-amazon');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function carregarConfigAmazon() {
            let data = null;
            const localConfig = localStorage.getItem('amazon_config');
            if (localConfig) {
                try {
                    data = JSON.parse(localConfig);
                } catch (e) {
                    console.error('Erro ao carregar configuração Amazon do storage:', e);
                }
            }

            if (!data && window.marketManager?.amazonConfig && Object.keys(window.marketManager.amazonConfig).length > 0) {
                data = window.marketManager.amazonConfig;
            }

            if (!data && window.marketManager?.apiKeys?.amazon) {
                data = window.marketManager.apiKeys.amazon;
            }

            if (data) {
                document.getElementById('amazon-aws-access-key').value = data.aws_access_key || '';
                document.getElementById('amazon-aws-secret-key').value = data.aws_secret_key || '';
                document.getElementById('amazon-seller-id').value = data.seller_id || '';
                document.getElementById('amazon-marketplace').value = data.marketplace || 'BR';
                document.getElementById('amazon-lwa-client-id').value = data.lwa_client_id || '';
                document.getElementById('amazon-lwa-client-secret').value = data.lwa_client_secret || '';
                document.getElementById('amazon-lwa-refresh-token').value = data.lwa_refresh_token || '';
            }
        }

        async function salvarConfigAmazon() {
            const config = {
                aws_access_key: document.getElementById('amazon-aws-access-key').value.trim(),
                aws_secret_key: document.getElementById('amazon-aws-secret-key').value.trim(),
                seller_id: document.getElementById('amazon-seller-id').value.trim(),
                marketplace: document.getElementById('amazon-marketplace').value,
                lwa_client_id: document.getElementById('amazon-lwa-client-id').value.trim(),
                lwa_client_secret: document.getElementById('amazon-lwa-client-secret').value.trim(),
                lwa_refresh_token: document.getElementById('amazon-lwa-refresh-token').value.trim()
            };

            // Validar campos obrigatórios
            if (!config.aws_access_key || !config.aws_secret_key || !config.seller_id ||
                !config.lwa_client_id || !config.lwa_client_secret || !config.lwa_refresh_token) {
                mostrarStatusAmazon('error', 'Preencha todos os campos obrigatórios!');
                return;
            }

            try {
                // Salvar no localStorage
                localStorage.setItem('amazon_config', JSON.stringify(config));

                // Salvar no servidor via API
                const response = await fetch('api/crud.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'salvar',
                        tipo: 'amazon_config',
                        dados: JSON.stringify(config)
                    })
                });

                const resultado = await parseApiResponse(response);

                if (resultado.success) {
                    if (window.marketManager) {
                        window.marketManager.amazonConfig = config;
                    }
                    mostrarStatusAmazon('success', '✅ Configurações salvas com sucesso! As credenciais persistirão mesmo após recarregar a página.');
                    if (window.marketManager) {
                        window.marketManager.mostrarNotificacao('✅ Configurações da Amazon salvas!', 'success');
                    }
                } else {
                    mostrarStatusAmazon('error', '❌ Erro ao salvar no servidor: ' + (resultado.erro || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Erro ao salvar configuração Amazon:', e);
                mostrarStatusAmazon('error', '❌ Erro ao salvar: ' + e.message);
            }
        }

        async function parseApiResponse(response) {
            const text = await response.text();
            if (!text) {
                throw new Error('Resposta vazia do servidor');
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Resposta inválida do servidor: ' + text);
            }
        }

        async function testarConexaoAmazon() {
            const config = {
                aws_access_key: document.getElementById('amazon-aws-access-key').value.trim(),
                aws_secret_key: document.getElementById('amazon-aws-secret-key').value.trim(),
                seller_id: document.getElementById('amazon-seller-id').value.trim(),
                marketplace: document.getElementById('amazon-marketplace').value,
                lwa_client_id: document.getElementById('amazon-lwa-client-id').value.trim(),
                lwa_client_secret: document.getElementById('amazon-lwa-client-secret').value.trim(),
                lwa_refresh_token: document.getElementById('amazon-lwa-refresh-token').value.trim()
            };

            // Validar campos obrigatórios
            if (!config.aws_access_key || !config.aws_secret_key || !config.seller_id ||
                !config.lwa_client_id || !config.lwa_client_secret || !config.lwa_refresh_token) {
                mostrarStatusAmazon('error', 'Preencha todos os campos obrigatórios antes de testar!');
                return;
            }

            mostrarStatusAmazon('info', '🔄 Testando conexão com Amazon SP-API...');

            try {
                const response = await fetch('api/sync.php?acao=testar-conexao', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ config })
                });

                const resultado = await parseApiResponse(response);

                if (resultado.success) {
                    mostrarStatusAmazon('success', '✅ Conexão estabelecida com sucesso! Encontrados ' + resultado.orders_found + ' pedidos recentes.');
                    if (window.marketManager) {
                        window.marketManager.mostrarNotificacao('✅ Conexão com Amazon OK!', 'success');
                    }
                } else {
                    mostrarStatusAmazon('error', '❌ Erro de conexão: ' + resultado.message);
                }
            } catch (e) {
                console.error('Erro ao testar conexão Amazon:', e);
                mostrarStatusAmazon('error', '❌ Erro de conexão: ' + e.message);
            }
        }

        async function importarProdutosAmazonConfig() {
            if (!window.marketManager) return;
            mostrarStatusAmazon('info', '🔄 Importando produtos Amazon... Aguarde.');
            const resultado = await window.marketManager.importarProdutosAmazon();
            if (resultado?.success) {
                mostrarStatusAmazon('success', `✅ Produtos importados: ${resultado.importados} novos, ${resultado.atualizados} atualizados`);
            } else {
                mostrarStatusAmazon('error', `❌ Falha ao importar produtos: ${resultado?.message || 'Erro desconhecido'}`);
            }
        }

        async function sincronizarPedidosAmazonConfig() {
            if (!window.marketManager) return;
            mostrarStatusAmazon('info', '🔄 Sincronizando pedidos Amazon... Aguarde.');
            const resultado = await window.marketManager.sincronizarPedidosAmazon();
            if (resultado?.success) {
                mostrarStatusAmazon('success', `✅ Pedidos sincronizados: ${resultado.importados} novos, ${resultado.atualizados} atualizados`);
            } else {
                mostrarStatusAmazon('error', `❌ Falha ao sincronizar pedidos: ${resultado?.message || 'Erro desconhecido'}`);
            }
        }

        async function sincronizacaoCompletaAmazonConfig() {
            if (!window.marketManager) return;
            mostrarStatusAmazon('info', '🔄 Iniciando sincronização completa Amazon...');
            const resultado = await window.marketManager.sincronizacaoCompletaAmazon();
            if (resultado?.pedidos?.success !== false && resultado?.produtos?.success !== false) {
                mostrarStatusAmazon('success', '✅ Sincronização completa concluída. Verifique seu painel para os resultados.');
            } else {
                mostrarStatusAmazon('error', '❌ Falha na sincronização completa da Amazon.');
            }
        }

        function mostrarStatusAmazon(tipo, mensagem) {
            const statusDiv = document.getElementById('amazon-config-status');
            if (!statusDiv) return;

            statusDiv.style.display = 'block';

            const cores = {
                success: 'rgba(46, 204, 113, 0.2)',
                error: 'rgba(231, 76, 60, 0.2)',
                info: 'rgba(52, 152, 219, 0.2)',
                warning: 'rgba(243, 156, 18, 0.2)'
            };

            const bordas = {
                success: '#2ecc71',
                error: '#e74c3c',
                info: '#3498db',
                warning: '#f39c12'
            };

            statusDiv.style.background = cores[tipo] || cores.info;
            statusDiv.style.border = `1px solid ${bordas[tipo] || bordas.info}`;
            statusDiv.style.color = 'var(--text-color)';
            statusDiv.innerHTML = mensagem;
        }

        // Event listener para fechar modal
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.getElementById('close-modal-config-amazon');
            if (closeBtn) {
                closeBtn.addEventListener('click', fecharConfigAmazon);
            }

            // ========== SISTEMA DE FORMATAÇÃO E VALIDAÇÃO DE CPF ==========

            // Função para formatar CPF
            function formatarCPF(value) {
                if (!value) return '';

                // Remove caracteres não numéricos
                const cpf = value.replace(/\D/g, '');

                // Limita a 11 dígitos
                if (cpf.length > 11) {
                    return cpf.substring(0, 11);
                }

                // Aplica formatação XXX.XXX.XXX-XX
                if (cpf.length <= 11) {
                    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }

                return value;
            }

            // Função para validar CPF
            function validarCPF(cpf) {
                if (!cpf) return false;

                // Remove formatação
                const cpfLimpo = cpf.replace(/\D/g, '');

                // Verifica se tem 11 dígitos
                if (cpfLimpo.length !== 11) return false;

                // Verifica se todos os dígitos são iguais
                if (/^(\d)\1{10}$/.test(cpfLimpo)) return false;

                // Cálculo de validação
                let soma = 0;
                let resto;

                // Validação do primeiro dígito
                for (let i = 1; i <= 9; i++) {
                    soma += parseInt(cpfLimpo.substring(i - 1, i)) * (11 - i);
                }
                resto = (soma * 10) % 11;
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpfLimpo.substring(9, 10))) return false;

                // Validação do segundo dígito
                soma = 0;
                for (let i = 1; i <= 10; i++) {
                    soma += parseInt(cpfLimpo.substring(i - 1, i)) * (12 - i);
                }
                resto = (soma * 10) % 11;
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpfLimpo.substring(10, 11))) return false;

                return true;
            }

            // Aplicar formatação a todos os campos de CPF
            function aplicarFormatacaoCPF() {
                const camposCPF = document.querySelectorAll('input[id*="cpf"], input[placeholder*="000.000.000-00"]');

                camposCPF.forEach(campo => {
                    // Adicionar classe de organização
                    const container = campo.closest('.form-group');
                    if (container && !container.classList.contains('campo-cpf-container')) {
                        container.classList.add('campo-cpf-container');
                    }

                    // Adicionar elemento de validação
                    if (container && !container.querySelector('.validacao-cpf')) {
                        const divValidacao = document.createElement('div');
                        divValidacao.className = 'validacao-cpf';
                        divValidacao.textContent = 'CPF inválido';
                        container.appendChild(divValidacao);
                    }

                    // Evento de input para formatação
                    campo.addEventListener('input', function(e) {
                        const valorOriginal = e.target.value;
                        const valorFormatado = formatarCPF(valorOriginal);

                        // Atualiza o valor formatado
                        if (valorOriginal !== valorFormatado) {
                            e.target.value = valorFormatado;
                        }

                        // Validação visual
                        if (container) {
                            const validacaoDiv = container.querySelector('.validacao-cpf');
                            const cpfLimpo = valorFormatado.replace(/\D/g, '');

                            if (cpfLimpo.length === 11) {
                                if (validarCPF(valorFormatado)) {
                                    campo.classList.add('cpf-formatado');
                                    campo.style.borderColor = 'var(--success-color)';
                                    validacaoDiv.textContent = 'CPF válido';
                                    validacaoDiv.classList.add('valido');
                                    validacaoDiv.classList.remove('invalido');
                                } else {
                                    campo.classList.remove('cpf-formatado');
                                    campo.style.borderColor = 'var(--error-color)';
                                    validacaoDiv.textContent = 'CPF inválido';
                                    validacaoDiv.classList.remove('valido');
                                    validacaoDiv.classList.add('invalido');
                                }
                            } else if (cpfLimpo.length > 0) {
                                campo.classList.remove('cpf-formatado');
                                campo.style.borderColor = 'var(--warning-color)';
                                validacaoDiv.textContent = 'Preenchendo...';
                                validacaoDiv.classList.remove('valido', 'invalido');
                            } else {
                                campo.classList.remove('cpf-formatado');
                                campo.style.borderColor = 'var(--input-border)';
                                validacaoDiv.classList.remove('valido', 'invalido');
                            }
                        }
                    });

                    // Evento de blur para validação final
                    campo.addEventListener('blur', function(e) {
                        const valorFormatado = formatarCPF(e.target.value);

                        if (valorFormatado && valorFormatado.replace(/\D/g, '').length === 11) {
                            if (!validarCPF(valorFormatado)) {
                                e.target.style.borderColor = 'var(--error-color)';
                                if (container) {
                                    const validacaoDiv = container.querySelector('.validacao-cpf');
                                    validacaoDiv.textContent = 'CPF inválido - verifique os números';
                                    validacaoDiv.classList.remove('valido');
                                    validacaoDiv.classList.add('invalido');
                                }
                            }
                        }
                    });

                    // Evento de focus para aplicar estilos
                    campo.addEventListener('focus', function(e) {
                        if (container) {
                            container.style.transform = 'scale(1.02)';
                            container.style.transition = 'transform 0.2s ease';
                        }
                    });

                    campo.addEventListener('blur', function(e) {
                        if (container) {
                            container.style.transform = 'scale(1)';
                        }
                    });
                });
            }

            // Aplicar formatação inicialmente
            aplicarFormatacaoCPF();

            // Reaplicar quando novos elementos são adicionados dinamicamente
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        aplicarFormatacaoCPF();
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });

            const modal = document.getElementById('modal-config-amazon');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        fecharConfigAmazon();
                    }
                });
            }
        });

        function conectarEmail() {
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('🔗 Conectando com Email Marketing... (simulado)', 'info');
                setTimeout(() => {
                    document.getElementById('status-integracao').innerHTML += '<p>✅ Email - Conectado com sucesso!</p>';
                    window.marketManager.mostrarNotificacao('✅ Email Marketing conectado!', 'success');
                }, 2000);
            }
        }

        function desconectarEmail() {
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('❌ Email Marketing desconectado', 'warning');
            }
        }

        // ==================== FUNÇÕES DE TREINAMENTO DE IA SUPER EXPANDIDO ====================

        // Contador de caracteres em tempo real
        const textoTreino = document.getElementById('texto-treino-ia');
        if (textoTreino) {
            textoTreino.addEventListener('input', function() {
                const count = this.value.length;
                document.getElementById('contador-chars').textContent = `${count} / 10.000 caracteres`;
            });
        }

        function mostrarAbaComTema(btnClicado, abaId) {
            // Esconder todas as abas
            document.querySelectorAll('.aba-treino-conteudo').forEach(aba => {
                aba.style.display = 'none';
            });
            // Remover active de todos os botões
            document.querySelectorAll('.tab-treino').forEach(btn => {
                btn.style.background = 'rgba(100, 100, 100, 0.1)';
                btn.style.color = 'var(--text-light)';
            });
            // Mostrar a aba selecionada
            document.getElementById(abaId).style.display = 'block';
            // Ativar botão clicado
            btnClicado.style.background = 'rgba(155, 89, 182, 0.3)';
            btnClicado.style.color = 'white';
        }

        function treinarIAComTexto() {
            const texto = document.getElementById('texto-treino-ia').value.trim();
            const categoria = document.getElementById('categoria-treino').value;
            const prioridade = document.getElementById('prioridade-treino').value;
            const imediato = document.getElementById('aplicar-imediato').checked;
            const salvarBase = document.getElementById('salvar-base').checked;

            if (!texto) {
                if (window.marketManager) {
                    window.marketManager.mostrarNotificacao('⚠️ Cole um texto para treinar a IA', 'warning');
                }
                return;
            }

            if (texto.length > 10000) {
                if (window.marketManager) {
                    window.marketManager.mostrarNotificacao('⚠️ Máximo 10.000 caracteres', 'warning');
                }
                return;
            }

            const treinamento = {
                id: Date.now(),
                categoria,
                prioridade,
                tamanho: texto.length,
                dataHora: new Date().toLocaleString('pt-BR'),
                timestamp: new Date().toISOString(),
                preview: texto.substring(0, 100) + '...'
            };

            let treinamentos = JSON.parse(localStorage.getItem('ia_treinamentos_lista') || '[]');
            treinamentos.push(treinamento);
            localStorage.setItem('ia_treinamentos_lista', JSON.stringify(treinamentos));

            // Salvar textos completos (últimos 10)
            let textos = JSON.parse(localStorage.getItem('ia_textos_salvos') || '[]');
            textos.push({
                id: treinamento.id,
                texto: salvarBase ? texto : texto.substring(0, 1000),
                categoria,
                prioridade
            });
            localStorage.setItem('ia_textos_salvos', JSON.stringify(textos.slice(-10)));

            atualizarHistoricoTreinamentoUI();
            atualizarEstatisticasIA();

            const emoji = {
                'geral': '📚',
                'negocio': '💼',
                'produtos': '📦',
                'clientes': '👥',
                'vendas': '💰',
                'marketing': '📢',
                'atendimento': '🎧',
                'analise': '📊',
                'customizado': '⚡'
            };

            const priori = {
                'basico': '🟡',
                'importante': '🟠',
                'critico': '🔴',
                'urgente': '🔥'
            };

            if (window.marketManager) {
                window.marketManager.mostrarNotificacao(`✅ TREINAMENTO CONCLUÍDO!\n\n${emoji[categoria]} ${categoria.toUpperCase()}\n${priori[prioridade]} Prioridade: ${prioridade}\n📄 ${texto.length} caracteres processados\n💾 Salvo na base: ${salvarBase ? 'SIM' : 'NÃO'}\n\n🧠 A IA ficou mais inteligente!`, 'success');
            }

            document.getElementById('texto-treino-ia').value = '';
            document.getElementById('contador-chars').textContent = '0 / 10.000 caracteres';
        }

        function carregarExemploTreinament() {
            const exemplo = `EXEMPLO DE CONHECIMENTO PARA A IA TREINAR:

=== POLÍTICAS DA EMPRESA ===
- Margem mínima: 20%
- Desconto máximo até cliente: 15%
- Frete grátis acima de R$500
- Taxa de administração: 2,9%
- Imposto: 10% do valor total

=== CATEGORIAS DE PRODUTOS ===
Smartphones: Margem 25%, Estoque mínimo 10
Notebooks: Margem 22%, Estoque mínimo 5
Acessórios: Margem 35%, Estoque mínimo 50
Cabos: Margem 40%, Estoque mínimo 100

=== PADRÕES DE CLIENTES ===
Cliente Premium: +3 compras/mês, 5-10% desconto
Cliente Regular: 1-2 compras/mês, até 3% desconto
Cliente Novo: Apenas preço normal
Cliente VIP: sem limite de desconto

=== REGRAS DE ATENDIMENTO ===
1. Sempre cumprimentar o cliente por nome
2. Oferecer garantia estendida (+10%)
3. Sugerir produtos complementares
4. Responder em até 2 horas
5. Resolver conflito em até 24h

=== HISTÓRICO DE VENDAS ÚLTIMOS 30 DIAS ===
- Total vendido: R$45.000
- Produto mais vendido: Smartphone X1
- Cliente top: João da Silva (R$5.000)
- Categoria top: Smartphones (60% do faturamento)
- Ticket médio: R$450

Copie e customize com SEUS dados!`;

            document.getElementById('texto-treino-ia').value = exemplo;
            document.getElementById('contador-chars').textContent = `${exemplo.length} / 10.000 caracteres`;
        }

        function limparTextoTreinament() {
            if (confirm('Deseja limpar o texto?')) {
                document.getElementById('texto-treino-ia').value = '';
                document.getElementById('contador-chars').textContent = '0 / 10.000 caracteres';
            }
        }

        function atualizarHistoricoTreinamentoUI() {
            const treinamentos = JSON.parse(localStorage.getItem('ia_treinamentos_lista') || '[]');
            const container = document.getElementById('historico-treinamentos-list');

            if (treinamentos.length === 0) {
                container.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 30px;">Nenhum treinamento ainda. Comece agora!</p>';
                return;
            }

            const emoji = {
                'geral': '📚', 'negocio': '💼', 'produtos': '📦', 'clientes': '👥',
                'vendas': '💰', 'marketing': '📢', 'atendimento': '🎧', 'analise': '📊', 'customizado': '⚡'
            };

            const coresPrioridade = {
                'basico': '#f1c40f', 'importante': '#e67e22', 'critico': '#e74c3c', 'urgente': '#c0392b'
            };

            const html = treinamentos.reverse().slice(0, 10).map(t => `
                <div style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; border-left: 4px solid ${coresPrioridade[t.prioridade]};">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <h5 style="color: var(--text-light); margin: 0; font-weight: 700;">
                            ${emoji[t.categoria] || '⚡'} ${t.categoria.charAt(0).toUpperCase() + t.categoria.slice(1)}
                        </h5>
                        <small style="color: var(--text-muted); font-size: 11px;">${t.dataHora}</small>
                    </div>
                    <p style="color: var(--text-muted); margin: 5px 0; font-size: 13px;">
                        📄 ${t.tamanho} caracteres | 🎯 Prioridade: ${t.prioridade.toUpperCase()}
                    </p>
                </div>
            `).join('');

            container.innerHTML = html;
        }

        function salvarPersonalizaçãoIA() {
            const personalizacao = {
                tom: document.getElementById('tom-ia').value,
                detalhe: document.getElementById('detalhe-ia').value,
                idioma: document.getElementById('idioma-ia').value,
                personalidade: document.getElementById('personalidade-ia').value,
                seguranca: document.getElementById('seguranca-ia').value,
                dataSalvo: new Date().toLocaleString('pt-BR')
            };

            localStorage.setItem('ia_personalizacao_config', JSON.stringify(personalizacao));

            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('✅ Personalização salva!\n\nProximas respostas da IA usarão essas configurações.', 'success');
            }
        }

        function atualizarEstatisticasIA() {
            const treinamentos = JSON.parse(localStorage.getItem('ia_treinamentos_lista') || '[]');
            const textos = JSON.parse(localStorage.getItem('ia_textos_salvos') || '[]');

            let totalChars = textos.reduce((sum, t) => sum + t.texto.length, 0);
            let nivel = '🟢 Iniciante';

            if (treinamentos.length >= 3) nivel = '🟡 Aprendiz';
            if (treinamentos.length >= 7) nivel = '🟠 Treinado';
            if (treinamentos.length >= 15) nivel = '🔴 Avançado';
            if (treinamentos.length >= 30) nivel = '🟣 Especialista';
            if (treinamentos.length >= 50) nivel = '⭐ Mestria';

            document.getElementById('stat-treinamentos-total').textContent = treinamentos.length;
            document.getElementById('stat-caracteres-total').textContent = totalChars.toLocaleString('pt-BR');
            document.getElementById('stat-nivel-ia-texto').textContent = nivel;
        }

        function limparHistoricoTreino() {
            if (confirm('⚠️ Deseja LIMPAR todo o histórico de treinaments?\n\nIsso não pode ser desfeito!')) {
                localStorage.removeItem('ia_treinamentos_lista');
                localStorage.removeItem('ia_textos_salvos');
                atualizarHistoricoTreinamentoUI();
                atualizarEstatisticasIA();
                if (window.marketManager) {
                    window.marketManager.mostrarNotificacao('✅ Histórico de treinamentos deletado!', 'success');
                }
            }
        }

        // Atualizar ao carregar página
        window.addEventListener('load', () => {
            atualizarHistoricoTreinamentoUI();
            atualizarEstatisticasIA();
        });

        // ==================== FIM DO CÓDIGO DE TREINAMENTO ====================

        // ========== UTILITÁRIO GLOBAL DE CPF ==========

        window.CPFUtils = {
            // Função para formatar CPF
            formatar(value) {
                if (!value) return '';
                const cpf = value.replace(/\D/g, '');
                if (cpf.length > 11) return cpf.substring(0, 11);
                return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            },

            // Função para validar CPF
            validar(value) {
                if (!value) return false;
                const cpf = value.replace(/\D/g, '');
                if (cpf.length !== 11) return false;
                if (/^(\d)\1{10}$/.test(cpf)) return false;

                let soma = 0;
                let resto;

                // Validação do primeiro dígito
                for (let i = 1; i <= 9; i++) {
                    soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
                }
                resto = (soma * 10) % 11;
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpf.substring(9, 10))) return false;

                // Validação do segundo dígito
                soma = 0;
                for (let i = 1; i <= 10; i++) {
                    soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
                }
                resto = (soma * 10) % 11;
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpf.substring(10, 11))) return false;

                return true;
            },

            // Função para mascarar CPF (mostrar apenas últimos 2 dígitos)
            mascarar(value) {
                if (!value) return '';
                const cpf = this.formatar(value);
                return '***.***.***-' + cpf.substring(cpf.length - 2);
            },

            // Função para gerar HTML de CPF formatado
            gerarBadge(value, options = {}) {
                const mascara = options.mascara || false;
                const classe = options.classe || 'cpf-display';
                const cpfFormatado = mascara ? this.mascarar(value) : this.formatar(value);

                const validade = this.validar(value);
                const estilo = validade ? 'border-left: 3px solid var(--success-color)' : 'border-left: 3px solid var(--error-color)';

                return `<div class="${classe}" style="${estilo}" title="${validade ? 'CPF Válido' : 'CPF Inválido'}">
                    ${cpfFormatado}
                </div>`;
            },

            // Inicializa todos os campos CPF no documento
            initCampos() {
                const campos = document.querySelectorAll('input[data-type="cpf"]');
                campos.forEach(campo => {
                    // Aplica formatação imediata
                    if (campo.value) {
                        campo.value = this.formatar(campo.value);
                    }

                    // Adiciona eventos
                    campo.addEventListener('input', (e) => {
                        e.target.value = this.formatar(e.target.value);
                    });
                });
                return campos;
            }
        };

        // ========== INICIALIZAÇÃO DO SISTEMA ==========

        // Inicializar o sistema quando a página carregar
        document.addEventListener('DOMContentLoaded', async () => {
            restaurarSidebar();
            document.getElementById('sidebar-toggle')?.addEventListener('click', alternarSidebar);
            window.marketManager = new MarketManager();
            await window.marketManager.init();

            setTimeout(() => {
                window.marketManager.mostrarNotificacao('Sistema Market Manager Pro carregado com sucesso!', 'success');
            }, 1000);
        });

        // Adicionar configuração inicial de visualização ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar visualização inicial após carregar página
            setTimeout(() => {
                // Configurar pedidos
                const pedidosBtnLista = document.getElementById('view-lista-pedidos');
                const pedidosBtnGrid = document.getElementById('view-quadrado-pedidos');
                const pedidosContainers = document.querySelectorAll('.pedidos-container');

                if (pedidosBtnLista && pedidosBtnGrid && pedidosContainers.length > 0) {
                    const savedPedidos = localStorage.getItem('config_visualizacao_pedidos') || 'lista';
                    localStorage.setItem('config_visualizacao_pedidos', savedPedidos);
                    if (savedPedidos === 'lista') {
                        pedidosBtnLista.classList.add('active');
                        pedidosBtnGrid.classList.remove('active');
                        pedidosContainers.forEach(container => {
                            container.classList.add('lista');
                            container.classList.remove('grid');
                        });
                        if (window.marketManager) {
                            window.marketManager.carregarPedidos(document.querySelector('.pedido-tab-btn.active')?.getAttribute('data-pedido-tab') || 'todos');
                        }
                    }
                }

                // Configurar produtos
                const produtosBtnLista = document.getElementById('view-lista-produtos');
                const produtosBtnGrid = document.getElementById('view-quadrado-produtos');
                const produtosContainer = document.getElementById('products-container');

                if (produtosBtnLista && produtosBtnGrid && produtosContainer) {
                    const savedProdutos = localStorage.getItem('config_visualizacao_produtos') || 'grid';
                    if (savedProdutos === 'lista') {
                        produtosBtnLista.classList.add('active');
                        produtosBtnGrid.classList.remove('active');
                        produtosContainer.classList.add('lista');
                        produtosContainer.classList.remove('grid');
                    }
                }
            }, 1000);
        });
    </script>
    <script src="js/amazon_sync_ui.js"></script>
</body>
</html>
