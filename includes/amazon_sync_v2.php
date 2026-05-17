<?php
/**
 * Amazon SP-API Integration V2
 * Integração melhorada com autenticação e sincronização real
 */

require_once __DIR__ . '/functions.php';

class AmazonAPIV2 {
    private $accessKeyId;
    private $secretKey;
    private $refreshToken;
    private $lwaClientId;
    private $lwaClientSecret;
    private $endpoint;
    private $region;

    const LWA_TOKEN_URL = 'https://api.amazon.com/auth/o2/token';

    public function __construct($config) {
        $this->accessKeyId = $config['aws_access_key_id'] ?? '';
        $this->secretKey = $config['aws_secret_access_key'] ?? '';
        $this->refreshToken = $config['lwa_refresh_token'] ?? '';
        $this->lwaClientId = $config['lwa_client_id'] ?? '';
        $this->lwaClientSecret = $config['lwa_client_secret'] ?? '';

        $marketplace = strtoupper($config['marketplace'] ?? 'BR');
        if (in_array($marketplace, ['US', 'MX', 'CA'])) {
            $this->endpoint = 'https://sellingpartnerapi-na.amazon.com';
            $this->region = 'na';
        } elseif (in_array($marketplace, ['UK', 'DE', 'FR', 'IT', 'ES'])) {
            $this->endpoint = 'https://sellingpartnerapi-eu.amazon.com';
            $this->region = 'eu';
        } else {
            $this->endpoint = 'https://sellingpartnerapi-na.amazon.com';
            $this->region = 'na';
        }
    }

    public function isValid() {
        return !empty($this->accessKeyId) &&
               !empty($this->secretKey) &&
               !empty($this->refreshToken) &&
               !empty($this->lwaClientId) &&
               !empty($this->lwaClientSecret);
    }

    /**
     * Obter access token via LWA
     */
    private function getAccessToken() {
        $cacheFile = DATA_DIR . 'amazon_token_v2.cache';

        // Verificar cache (token válido ~3600s)
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if ($cache && isset($cache['token']) && isset($cache['expires_at'])) {
                if (time() < $cache['expires_at']) {
                    return $cache['token'];
                }
            }
        }

        // Obter novo token
        $response = $this->makeLWARequest();

        if ($response && isset($response['access_token'])) {
            $expiresIn = $response['expires_in'] ?? 3600;
            $cacheData = [
                'token' => $response['access_token'],
                'expires_at' => time() + $expiresIn - 300 // Renovar 5min antes
            ];
            file_put_contents($cacheFile, json_encode($cacheData));
            return $response['access_token'];
        }

        return null;
    }

    /**
     * Fazer requisição para obter token LWA
     */
    private function makeLWARequest() {
        $ch = curl_init(self::LWA_TOKEN_URL);

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->lwaClientId,
            'client_secret' => $this->lwaClientSecret
        ];

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return null;
    }

    /**
     * Assinar requisição AWS Signature V4
     */
    private function signRequest($method, $path, $query = '', $body = '') {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }

        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $algorithm = 'AWS4-HMAC-SHA256';
        $service = 'execute-api';
        $region = $this->region;

        // Timestamp
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        // Canonical request
        $canonicalUri = $path;
        $canonicalQuery = $query;
        $canonicalHeaders = "host:" . $host . "\n" .
                          "x-amz-date:" . $amzDate . "\n";
        $signedHeaders = 'host;x-amz-date';
        $payloadHash = hash('sha256', $body);

        $canonicalRequest = $method . "\n" .
                           $canonicalUri . "\n" .
                           $canonicalQuery . "\n" .
                           $canonicalHeaders . "\n" .
                           $signedHeaders . "\n" .
                           $payloadHash;

        // String to sign
        $credentialScope = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
        $stringToSign = $algorithm . "\n" .
                       $amzDate . "\n" .
                       $credentialScope . "\n" .
                       hash('sha256', $canonicalRequest);

        // Calculate signature
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // Authorization header
        $authorizationHeader = $algorithm . ' ' .
                            'Credential=' . $this->accessKeyId . '/' . $credentialScope . ', ' .
                            'SignedHeaders=' . $signedHeaders . ', ' .
                            'Signature=' . $signature;

        return [
            'Authorization' => $authorizationHeader,
            'x-amz-date' => $amzDate,
            'x-amz-security-token' => $accessToken
        ];
    }

    /**
     * Fazer requisição assinada para API
     */
    private function makeSignedRequest($method, $path, $query = '', $body = '') {
        if (!$this->isValid()) {
            return ['success' => false, 'erro' => 'Credenciais inválidas'];
        }

        $headers = $this->signRequest($method, $path, $query, $body);
        if (!$headers) {
            return ['success' => false, 'erro' => 'Erro ao assinar requisição'];
        }

        $url = $this->endpoint . $path;
        if ($query) {
            $url .= '?' . $query;
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $headers['Authorization'],
            'x-amz-date: ' . $headers['x-amz-date'],
            'x-amz-security-token: ' . $headers['x-amz-security-token']
        ]);

        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'erro' => $error];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData];
        }

        return [
            'success' => false,
            'erro' => 'Erro HTTP: ' . $httpCode,
            'response' => $responseData
        ];
    }

    /**
     * Sincronizar pedidos da Amazon
     */
    public function syncOrders() {
        if (!$this->isValid()) {
            return [
                'success' => false,
                'erro' => 'Configure as credenciais da Amazon primeiro'
            ];
        }

        // Obter pedidos dos últimos 30 dias
        $createdAfter = date('Y-m-d\TH:i:s\Z', strtotime('-30 days'));
        $query = http_build_query(['CreatedAfter' => $createdAfter]);

        $result = $this->makeSignedRequest('GET', '/orders/v0/orders', $query);

        if (!$result['success']) {
            return $result;
        }

        $orders = $result['data']['payload']['Orders'] ?? [];

        // Carregar pedidos existentes
        $pedidos = carregarJSON(PEDIDOS_FILE);

        // Processar pedidos
        $novosPedidos = 0;
        foreach ($orders as $order) {
            // Verificar se pedido já existe
            $existe = false;
            foreach ($pedidos as $p) {
                if ($p['amazonOrderId'] === $order['AmazonOrderId']) {
                    $existe = true;
                    break;
                }
            }

            if (!$existe) {
                // Obter detalhes do pedido
                $orderItems = $this->getOrderItems($order['AmazonOrderId']);

                if ($orderItems['success']) {
                    $items = $orderItems['data']['payload']['OrderItems'] ?? [];

                    foreach ($items as $item) {
                        $novoPedido = [
                            'id' => count($pedidos) + 1,
                            'amazonOrderId' => $order['AmazonOrderId'],
                            'cliente' => [
                                'nome' => $order['BuyerName'] ?? 'Cliente Amazon',
                                'email' => ''
                            ],
                            'endereco' => $this->parseAddress($order['ShippingAddress']),
                            'produto' => [
                                'nome' => $item['Title'] ?? 'Produto Amazon',
                                'categoria' => 'outros',
                                'precoCusto' => 0,
                                'precoVenda' => floatval($item['ItemPrice']['Amount'] ?? 0)
                            ],
                            'quantidade' => intval($item['QuantityOrdered'] ?? 1),
                            'frete' => floatval($order['ShipmentServiceLevel']['Price']['Amount'] ?? 0),
                            'codigoRastreio' => '',
                            'status' => $this->mapStatus($order['OrderStatus']),
                            'dataCadastro' => date('Y-m-d H:i:s', strtotime($order['PurchaseDate'] ?? 'now')),
                            'origem' => 'amazon'
                        ];

                        $pedidos[] = $novoPedido;
                        $novosPedidos++;
                    }
                }
            }
        }

        // Salvar pedidos
        if ($novosPedidos > 0) {
            salvarJSON(PEDIDOS_FILE, $pedidos);
        }

        return [
            'success' => true,
            'mensagem' => "Sincronização concluída! {$novosPedidos} novos pedidos importados.",
            'total_importados' => $novosPedidos
        ];
    }

    /**
     * Obter itens de um pedido
     */
    private function getOrderItems($orderId) {
        return $this->makeSignedRequest('GET', "/orders/v0/orders/{$orderId}/orderItems");
    }

    /**
     * Parsear endereço do pedido Amazon
     */
    private function parseAddress($address) {
        if (!$address) {
            return [];
        }

        return [
            'rua' => $address['AddressLine1'] ?? '',
            'numero' => '',
            'complemento' => $address['AddressLine2'] ?? '',
            'bairro' => '',
            'cidade' => $address['City'] ?? '',
            'estado' => $address['StateOrRegion'] ?? '',
            'cep' => $address['PostalCode'] ?? '',
            'pais' => $address['CountryCode'] ?? ''
        ];
    }

    /**
     * Mapear status do pedido Amazon
     */
    private function mapStatus($amazonStatus) {
        $statusMap = [
            'Pending' => 'pendente',
            'Unshipped' => 'processando',
            'PartiallyShipped' => 'transito',
            'Shipped' => 'transito',
            'Delivered' => 'entregue',
            'Canceled' => 'cancelado'
        ];

        return $statusMap[$amazonStatus] ?? 'pendente';
    }

    /**
     * Testar conexão com Amazon
     */
    public function testConnection() {
        if (!$this->isValid()) {
            return [
                'success' => false,
                'erro' => 'Credenciais não configuradas'
            ];
        }

        // Tentar obter token
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'erro' => 'Não foi possível obter token de acesso'
            ];
        }

        return [
            'success' => true,
            'mensagem' => 'Conexão estabelecida com sucesso!'
        ];
    }
}