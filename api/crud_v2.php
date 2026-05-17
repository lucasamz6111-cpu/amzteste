<?php
/**
 * API CRUD V2 - Endpoint melhorado para operações de dados
 * Suporta todas as operações CRUD com validação
 */

header('Content-Type: application/json;charset=UTF-8');
header('Cache-Control: no-cache');

// Limpar qualquer output anterior
while (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../includes/functions.php';

// Ler JSON POST
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$acao = $input['acao'] ?? '';

// Carregar dados
$pedidos = carregarJSON(PEDIDOS_FILE);
$produtos = carregarJSON(PRODUTOS_FILE);
$clientes = carregarJSON(CLIENTES_FILE);
$config = carregarJSON(CONFIG_FILE);
$apiKeys = carregarJSON(API_KEYS_FILE);

$retorno = ['success' => false];

switch ($acao) {
    case 'salvar_pedido':
        $pedido = $input['pedido'] ?? [];
        if (empty($pedido)) {
            $retorno = ['success' => false, 'erro' => 'Dados do pedido não fornecidos'];
            break;
        }

        // Validar campos obrigatórios
        if (empty($pedido['cliente']['nome'])) {
            $retorno = ['success' => false, 'erro' => 'Nome do cliente é obrigatório'];
            break;
        }

        if (empty($pedido['produto']['nome'])) {
            $retorno = ['success' => false, 'erro' => 'Nome do produto é obrigatório'];
            break;
        }

        // Gerar ID se não existir
        if (empty($pedido['id'])) {
            $ids = empty($pedidos) ? 0 : max(array_column($pedidos, 'id'));
            $pedido['id'] = $ids + 1;
        }

        // Data de cadastro
        if (empty($pedido['dataCadastro'])) {
            $pedido['dataCadastro'] = date('Y-m-d H:i:s');
        }

        // Verificar se já existe (atualização)
        $exists = false;
        foreach ($pedidos as $key => $p) {
            if ($p['id'] == $pedido['id']) {
                $pedidos[$key] = $pedido;
                $exists = true;
                break;
            }
        }

        // Adicionar se não existe
        if (!$exists) {
            $pedidos[] = $pedido;
        }

        // Salvar
        if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
            $retorno = ['success' => true, 'id' => $pedido['id'], 'mensagem' => 'Pedido salvo com sucesso'];
        } else {
            $retorno = ['success' => false, 'erro' => 'Erro ao salvar pedido'];
        }
        break;

    case 'salvar_produto':
        $produto = $input['produto'] ?? [];
        if (empty($produto)) {
            $retorno = ['success' => false, 'erro' => 'Dados do produto não fornecidos'];
            break;
        }

        // Validar campos obrigatórios
        if (empty($produto['nome'])) {
            $retorno = ['success' => false, 'erro' => 'Nome do produto é obrigatório'];
            break;
        }

        // Gerar ID se não existir
        if (empty($produto['id'])) {
            $ids = empty($produtos) ? 0 : max(array_column($produtos, 'id'));
            $produto['id'] = $ids + 1;
        }

        // Data de cadastro
        if (empty($produto['dataCadastro'])) {
            $produto['dataCadastro'] = date('Y-m-d H:i:s');
        }

        // Verificar se já existe (atualização)
        $exists = false;
        foreach ($produtos as $key => $p) {
            if ($p['id'] == $produto['id']) {
                $produtos[$key] = $produto;
                $exists = true;
                break;
            }
        }

        // Adicionar se não existe
        if (!$exists) {
            $produtos[] = $produto;
        }

        // Salvar
        if (salvarJSON(PRODUTOS_FILE, $produtos)) {
            $retorno = ['success' => true, 'id' => $produto['id'], 'mensagem' => 'Produto salvo com sucesso'];
        } else {
            $retorno = ['success' => false, 'erro' => 'Erro ao salvar produto'];
        }
        break;

    case 'excluir_pedido':
        $id = $input['id'] ?? 0;
        if (empty($id)) {
            $retorno = ['success' => false, 'erro' => 'ID não fornecido'];
            break;
        }

        $encontrado = false;
        foreach ($pedidos as $key => $p) {
            if ($p['id'] == $id) {
                unset($pedidos[$key]);
                $pedidos = array_values($pedidos);
                $encontrado = true;
                break;
            }
        }

        if ($encontrado) {
            if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                $retorno = ['success' => true, 'mensagem' => 'Pedido excluído com sucesso'];
            } else {
                $retorno = ['success' => false, 'erro' => 'Erro ao excluir pedido'];
            }
        } else {
            $retorno = ['success' => false, 'erro' => 'Pedido não encontrado'];
        }
        break;

    case 'excluir_produto':
        $id = $input['id'] ?? 0;
        if (empty($id)) {
            $retorno = ['success' => false, 'erro' => 'ID não fornecido'];
            break;
        }

        $encontrado = false;
        foreach ($produtos as $key => $p) {
            if ($p['id'] == $id) {
                unset($produtos[$key]);
                $produtos = array_values($produtos);
                $encontrado = true;
                break;
            }
        }

        if ($encontrado) {
            if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                $retorno = ['success' => true, 'mensagem' => 'Produto excluído com sucesso'];
            } else {
                $retorno = ['success' => false, 'erro' => 'Erro ao excluir produto'];
            }
        } else {
            $retorno = ['success' => false, 'erro' => 'Produto não encontrado'];
        }
        break;

    case 'carregar_dados':
        $retorno = [
            'success' => true,
            'pedidos' => $pedidos,
            'produtos' => $produtos,
            'clientes' => $clientes,
            'config' => $config,
            'apiKeys' => $apiKeys
        ];
        break;

    case 'amazon_sync':
        require_once __DIR__ . '/../includes/amazon_sync_v2.php';
        $amazonConfig = $apiKeys['amazon'] ?? [];
        $amazon = new AmazonAPIV2($amazonConfig);
        $result = $amazon->syncOrders();
        $retorno = $result;
        break;

    case 'amazon_test':
        require_once __DIR__ . '/../includes/amazon_sync_v2.php';
        $amazonConfig = $apiKeys['amazon'] ?? [];
        $amazon = new AmazonAPIV2($amazonConfig);
        $result = $amazon->testConnection();
        $retorno = $result;
        break;

    case 'salvar_config_amazon':
        $amazonConfig = $input['config'] ?? [];
        if (empty($apiKeys)) {
            $apiKeys = [];
        }
        $apiKeys['amazon'] = $amazonConfig;

        if (salvarJSON(API_KEYS_FILE, $apiKeys)) {
            $retorno = ['success' => true, 'mensagem' => 'Configurações da Amazon salvas'];
        } else {
            $retorno = ['success' => false, 'erro' => 'Erro ao salvar configurações'];
        }
        break;

    case 'salvar_config':
        $novaConfig = $input['config'] ?? [];
        $config = array_merge($config, $novaConfig);

        if (salvarJSON(CONFIG_FILE, $config)) {
            $retorno = ['success' => true, 'mensagem' => 'Configurações salvas'];
        } else {
            $retorno = ['success' => false, 'erro' => 'Erro ao salvar configurações'];
        }
        break;

    default:
        $retorno = ['success' => false, 'erro' => 'Ação não reconhecida: ' . $acao];
}

echo json_encode($retorno, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);