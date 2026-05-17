<?php
/**
 * API de Sincronizacao Amazon
 * Endpoint: POST /api/sync.php
 * Acoes: sync-pedidos, sync-produtos, testar-conexao
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/amazon_sync.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    switch ($acao) {

        case 'sync-pedidos':
            $apiConfig = carregarJSON(API_KEYS_FILE);
            $amazonConfig = [];

            // Tentar encontrar credenciaisAmazon
            if (isset($apiConfig['amazon']) && is_array($apiConfig['amazon'])) {
                $amazonConfig = $apiConfig['amazon'];
            }

            // Tambem verificar credenciais salvas individualmente
            foreach ($apiConfig as $key => $val) {
                if (is_string($key) && stripos($key, 'amazon') !== false) {
                    $amazonConfig = array_merge($amazonConfig, is_array($val) ? $val : []);
                }
            }

            // Verificar via config tambem
            $config = carregarJSON(CONFIG_FILE);
            if (!empty($config['amazon'])) {
                $amazonConfig = array_merge($amazonConfig, $config['amazon']);
            }

            // Mapear chaves para o formato esperado pela classe AmazonAPI
            $finalConfig = [
                'aws_access_key_id' => $amazonConfig['aws_access_key'] ?? '',
                'aws_secret_access_key' => $amazonConfig['aws_secret_key'] ?? '',
                'lwa_refresh_token' => $amazonConfig['lwa_refresh_token'] ?? '',
                'lwa_client_id' => $amazonConfig['lwa_client_id'] ?? '',
                'lwa_client_secret' => $amazonConfig['lwa_client_secret'] ?? '',
                'marketplace' => $amazonConfig['marketplace'] ?? 'BR',
                'seller_id' => $amazonConfig['seller_id'] ?? '',
            ];

            $amazon = new AmazonAPI($finalConfig);

            if (!$amazon->isValid()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Credenciais da Amazon nao configuradas. Configure-as na aba Integracoes.',
                    'setup_needed' => true,
                ]);
                exit;
            }

            $result = $amazon->syncOrders();
            echo json_encode($result);
            break;

        case 'sync-produtos':
            $apiConfig = carregarJSON(API_KEYS_FILE);
            $amazonConfig = [];

            // Tentar encontrar credenciaisAmazon
            if (isset($apiConfig['amazon']) && is_array($apiConfig['amazon'])) {
                $amazonConfig = $apiConfig['amazon'];
            }

            // Tambem verificar credenciais salvas individualmente
            foreach ($apiConfig as $key => $val) {
                if (is_string($key) && stripos($key, 'amazon') !== false) {
                    $amazonConfig = array_merge($amazonConfig, is_array($val) ? $val : []);
                }
            }

            // Verificar via config tambem
            $config = carregarJSON(CONFIG_FILE);
            if (!empty($config['amazon'])) {
                $amazonConfig = array_merge($amazonConfig, $config['amazon']);
            }

            // Mapear chaves para o formato esperado pela classe AmazonAPI
            $finalConfig = [
                'aws_access_key_id' => $amazonConfig['aws_access_key'] ?? '',
                'aws_secret_access_key' => $amazonConfig['aws_secret_key'] ?? '',
                'lwa_refresh_token' => $amazonConfig['lwa_refresh_token'] ?? '',
                'lwa_client_id' => $amazonConfig['lwa_client_id'] ?? '',
                'lwa_client_secret' => $amazonConfig['lwa_client_secret'] ?? '',
                'marketplace' => $amazonConfig['marketplace'] ?? 'BR',
                'seller_id' => $amazonConfig['seller_id'] ?? '',
            ];

            $amazon = new AmazonAPI($finalConfig);

            if (!$amazon->isValid()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Credenciais da Amazon nao configuradas. Configure-as na aba Integracoes.',
                    'setup_needed' => true,
                ]);
                exit;
            }

            $result = $amazon->syncProducts();
            echo json_encode($result);
            break;

        case 'notificacoes-vendas':
            $apiConfig = carregarJSON(API_KEYS_FILE);
            $amazonConfig = [];

            // Tentar encontrar credenciaisAmazon
            if (isset($apiConfig['amazon']) && is_array($apiConfig['amazon'])) {
                $amazonConfig = $apiConfig['amazon'];
            }

            // Tambem verificar credenciais salvas individualmente
            foreach ($apiConfig as $key => $val) {
                if (is_string($key) && stripos($key, 'amazon') !== false) {
                    $amazonConfig = array_merge($amazonConfig, is_array($val) ? $val : []);
                }
            }

            // Verificar via config tambem
            $config = carregarJSON(CONFIG_FILE);
            if (!empty($config['amazon'])) {
                $amazonConfig = array_merge($amazonConfig, $config['amazon']);
            }

            // Mapear chaves para o formato esperado pela classe AmazonAPI
            $finalConfig = [
                'aws_access_key_id' => $amazonConfig['aws_access_key'] ?? '',
                'aws_secret_access_key' => $amazonConfig['aws_secret_key'] ?? '',
                'lwa_refresh_token' => $amazonConfig['lwa_refresh_token'] ?? '',
                'lwa_client_id' => $amazonConfig['lwa_client_id'] ?? '',
                'lwa_client_secret' => $amazonConfig['lwa_client_secret'] ?? '',
                'marketplace' => $amazonConfig['marketplace'] ?? 'BR',
                'seller_id' => $amazonConfig['seller_id'] ?? '',
            ];

            $amazon = new AmazonAPI($finalConfig);

            if (!$amazon->isValid()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Credenciais da Amazon nao configuradas. Configure-as na aba Integracoes.',
                    'setup_needed' => true,
                ]);
                exit;
            }

            $hours = $input['hours'] ?? 24;
            $result = $amazon->getSalesNotifications($hours);
            echo json_encode($result);
            break;

        case 'testar-conexao':
            $config = $input['config'] ?? $input;

            // Mapear chaves para o formato esperado pela classe AmazonAPI
            $amazonConfig = [
                'aws_access_key_id' => $config['aws_access_key'] ?? '',
                'aws_secret_access_key' => $config['aws_secret_key'] ?? '',
                'lwa_refresh_token' => $config['lwa_refresh_token'] ?? '',
                'lwa_client_id' => $config['lwa_client_id'] ?? '',
                'lwa_client_secret' => $config['lwa_client_secret'] ?? '',
                'marketplace' => $config['marketplace'] ?? 'BR',
                'seller_id' => $config['seller_id'] ?? '',
            ];

            $amazon = new AmazonAPI($amazonConfig);

            if (!$amazon->isValid()) {
                echo json_encode(['success' => false, 'message' => 'Preencha todas as credenciais obrigatorias']);
                exit;
            }

            try {
                $orders = $amazon->getOrders(gmdate('c', strtotime('-24 hours')), 1);
                echo json_encode([
                    'success' => true,
                    'message' => 'Conexao com Amazon estabelecida com sucesso!',
                    'orders_found' => count($orders),
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acao nao reconhecida. Use: sync-pedidos, sync-produtos, notificacoes-vendas, testar-conexao']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
