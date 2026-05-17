<?php
/**
 * API CRUD - Endpoint separado para operacoes de dados
 * Evita que o HTML do index.php interfira nas respostas JSON
 */

header('Content-Type: application/json;charset=UTF-8');
header('Cache-Control: no-cache');

// Limpar qualquer output anterior
while (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../includes/functions.php';

// Le JSON POST
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$acao = $input['acao'] ?? '';
$tipo = $input['tipo'] ?? '';
$dados = $input['dados'] ?? '';

$pedidos = carregarJSON(PEDIDOS_FILE);
$produtos = carregarJSON(PRODUTOS_FILE);
$clientes = carregarJSON(CLIENTES_FILE);
$config = carregarJSON(CONFIG_FILE);
$apiKeys = carregarJSON(API_KEYS_FILE);

$retorno = ['success' => false];

switch ($acao) {
    case 'salvar':
        if ($tipo === 'pedido') {
            $item = json_decode($dados, true);
            if ($item) {
                // Se ID fornecido, verificar se é atualização ou novo
                if (isset($item['id']) && !empty($item['id'])) {
                    $novoId = (int)$item['id'];
                    $encontrado = false;
                    foreach ($pedidos as $key => $p) {
                        if ((int)$p['id'] === $novoId) {
                            // Atualizar pedido existente
                            $pedidos[$key] = $item;
                            $encontrado = true;
                            break;
                        }
                    }
                    // Se não encontrou, é um novo pedido com ID específico
                    if (!$encontrado) {
                        $pedidos[] = $item;
                    }
                } else {
                    // Gerar novo ID automaticamente
                    $ids = empty($pedidos) ? 0 : max(array_map(fn($p) => (int)$p['id'], $pedidos));
                    $item['id'] = $ids + 1;
                    $pedidos[] = $item;
                }
                if (empty($item['dataCadastro']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['dataCadastro'])) {
                    $item['dataCadastro'] = date('Y-m-d');
                }
                if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                    logSistema("Pedido #{$item['id']} salvo via API");
                    echo json_encode(['success' => true, 'id' => $item['id']]);
                } else {
                    echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                }
            } else {
                echo json_encode(['success' => false, 'erro' => 'Dados invalidos']);
            }
        } elseif ($tipo === 'produto') {
            $item = json_decode($dados, true);
            if ($item) {
                // Se ID fornecido, verificar se é atualização ou novo
                if (isset($item['id']) && !empty($item['id'])) {
                    $novoId = (int)$item['id'];
                    $encontrado = false;
                    foreach ($produtos as $key => $p) {
                        if ((int)$p['id'] === $novoId) {
                            // Atualizar produto existente
                            $produtos[$key] = $item;
                            $encontrado = true;
                            break;
                        }
                    }
                    // Se não encontrou, é um novo produto com ID específico
                    if (!$encontrado) {
                        $produtos[] = $item;
                    }
                } else {
                    // Gerar novo ID automaticamente
                    $ids = empty($produtos) ? 0 : max(array_map(fn($p) => (int)$p['id'], $produtos));
                    $item['id'] = $ids + 1;
                    $produtos[] = $item;
                }
                if (empty($item['dataCadastro'])) $item['dataCadastro'] = date('Y-m-d');
                if (!isset($item['frete'])) $item['frete'] = 0;
                if (!isset($item['embalagem'])) $item['embalagem'] = 0;
                if (!isset($item['gastosExtras'])) $item['gastosExtras'] = 0;
                if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                    logSistema("Produto #{$item['id']} salvo via API: {$item['nome']}");
                    echo json_encode(['success' => true, 'id' => $item['id']]);
                } else {
                    echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                }
            } else {
                echo json_encode(['success' => false, 'erro' => 'Dados invalidos']);
            }
        } elseif ($tipo === 'cliente') {
            $item = json_decode($dados, true);
            if ($item) {
                if (empty($item['id'])) {
                    $ids = empty($clientes) ? 0 : max(array_map(fn($c) => (int)$c['id'], $clientes));
                    $item['id'] = $ids + 1;
                }
                $clientes[] = $item;
                if (salvarJSON(CLIENTES_FILE, $clientes)) {
                    echo json_encode(['success' => true, 'id' => $item['id']]);
                } else {
                    echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                }
            } else {
                echo json_encode(['success' => false, 'erro' => 'Dados invalidos']);
            }
        } elseif ($tipo === 'amazon_config') {
            $item = json_decode($dados, true);
            if ($item) {
                $apiKeys['amazon'] = $item;
                if (salvarJSON(API_KEYS_FILE, $apiKeys)) {
                    logSistema('Configurações Amazon salvas via API');
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                }
            } else {
                echo json_encode(['success' => false, 'erro' => 'Dados invalidos']);
            }
        } else {
            echo json_encode(['success' => false, 'erro' => 'Tipo de salvamento não reconhecido']);
        }
        break;

    case 'atualizar':
        if ($tipo === 'pedido') {
            $item = json_decode($dados, true);
            if ($item) {
                foreach ($pedidos as $key => $pedido) {
                    if ((int)$pedido['id'] === (int)$item['id']) {
                        $pedidos[$key] = $item;
                        if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                            echo json_encode(['success' => true]);
                        } else {
                            echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                        }
                        exit;
                    }
                }
            }
            echo json_encode(['success' => false, 'erro' => 'Nao encontrado']);
        } elseif ($tipo === 'produto') {
            $item = json_decode($dados, true);
            if ($item) {
                foreach ($produtos as $key => $produto) {
                    if ((int)$produto['id'] === (int)$item['id']) {
                        $produtos[$key] = $item;
                        if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                            echo json_encode(['success' => true]);
                        } else {
                            echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                        }
                        exit;
                    }
                }
            }
            echo json_encode(['success' => false, 'erro' => 'Nao encontrado']);
        }
        break;

    case 'excluir':
        $id = (int)($input['id'] ?? 0);
        $found = false;

        if ($tipo === 'pedido') {
            $deletedCpf = null;
            foreach ($pedidos as $key => $pedido) {
                if ((int)$pedido['id'] === $id) {
                    $deletedCpf = $pedido['cliente']['cpf'] ?? $pedido['cliente']['cpfCnpj'] ?? null;
                    unset($pedidos[$key]);
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $pedidos = array_values($pedidos);
                if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                    if ($deletedCpf) {
                        $clientePermanece = false;
                        foreach ($pedidos as $pedido) {
                            $pedidoCpf = $pedido['cliente']['cpf'] ?? $pedido['cliente']['cpfCnpj'] ?? null;
                            if ($pedidoCpf && $pedidoCpf === $deletedCpf) {
                                $clientePermanece = true;
                                break;
                            }
                        }
                        if (!$clientePermanece) {
                            $clientes = array_values(array_filter($clientes, function ($cliente) use ($deletedCpf) {
                                $clienteCpf = $cliente['cpf'] ?? $cliente['cpfCnpj'] ?? null;
                                return $clienteCpf !== $deletedCpf;
                            }));
                            salvarJSON(CLIENTES_FILE, $clientes);
                        }
                    }
                    logSistema("Pedido #{$id} excluido via API");
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                }
            } else {
                echo json_encode(['success' => false, 'erro' => 'Nao encontrado']);
            }
        } elseif ($tipo === 'produto') {
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
                    logSistema("Produto #{$id} excluido via API");
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
                }
            } else {
                echo json_encode(['success' => false, 'erro' => 'Nao encontrado']);
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
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
            }
        }
        break;

    case 'carregar':
        echo json_encode([
            'success' => true,
            'pedidos' => $pedidos,
            'produtos' => $produtos,
            'clientes' => $clientes,
            'config' => $config,
            'apiKeys' => $apiKeys,
        ]);
        break;

    case 'obter-taxa':
        $cat = $input['categoria'] ?? '';
        echo json_encode(['taxa' => obterTaxaCategoria($cat, $config)]);
        break;

    case 'salvar-configuracoes':
        $newConfig = json_decode($dados, true);
        if ($newConfig) {
            $config = array_merge($config, $newConfig);
            if (salvarJSON(CONFIG_FILE, $config)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'erro' => 'Falha ao salvar']);
            }
        }
        break;

    case 'amazon-sync':
    case 'test-amazon-connection':
        require_once __DIR__ . '/sync.php';
        exit;

    default:
        echo json_encode(['success' => false, 'erro' => 'Acao nao reconhecida']);
        break;
}
