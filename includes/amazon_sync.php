<?php
/**
 * Amazon SP-API Integration
 * Autenticacao Assinatura V4 + Chamadas a API real da Amazon
 */

require_once __DIR__ . '/functions.php';

class AmazonAPI {
    private $accessKeyId;
    private $secretKey;
    private $refreshToken;
    private $lwaClientId;
    private $lwaClientSecret;
    private $endpoint; // ex: https://sellingpartnerapi-na.amazon.com
    private $region;   // na, eu, fe
    private $marketplace;

    const LWA_TOKEN_URL = 'https://api.amazon.com/auth/o2/token';

    public function __construct($config) {
        $this->accessKeyId = $config['aws_access_key_id'] ?? '';
        $this->secretKey = $config['aws_secret_access_key'] ?? '';
        $this->refreshToken = $config['lwa_refresh_token'] ?? '';
        $this->lwaClientId = $config['lwa_client_id'] ?? '';
        $this->lwaClientSecret = $config['lwa_client_secret'] ?? '';
        $this->marketplace = strtoupper($config['marketplace'] ?? 'BR');

        if (in_array($this->marketplace, ['US', 'MX', 'CA'])) {
            $this->endpoint = 'https://sellingpartnerapi-na.amazon.com';
            $this->region = 'na';
        } elseif (in_array($this->marketplace, ['UK', 'DE', 'FR', 'IT', 'ES'])) {
            $this->endpoint = 'https://sellingpartnerapi-eu.amazon.com';
            $this->region = 'eu';
        } else {
            $this->endpoint = 'https://sellingpartnerapi-na.amazon.com';
            $this->region = 'na';
        }
    }

    public function isValid() {
        return !empty($this->accessKeyId) && !empty($this->secretKey) && !empty($this->refreshToken);
    }

    /**
     * Obter access token via LWA
     */
    private function getAccessToken() {
        $cacheFile = DATA_DIR . 'amazon_token.cache';

        // Verifica cache (token valido ~3600s)
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if ($cache && isset($cache['expires_at']) && time() < ($cache['expires_at'] - 300)) {
                return $cache['access_token'];
            }
        }

        $postData = http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->lwaClientId,
            'client_secret' => $this->lwaClientSecret,
        ]);

        $ch = curl_init(self::LWA_TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new Exception("Erro ao obter access token LWA: HTTP {$httpCode} - " . substr($response ?: '', 0, 200));
        }

        $data = json_decode($response, true);
        $token = $data['access_token'] ?? null;
        if (!$token) {
            throw new Exception("Access token nao encontrado na resposta LWA");
        }

        // Cache do token
        $expiresIn = $data['expires_in'] ?? 3600;
        file_put_contents($cacheFile, json_encode([
            'access_token' => $token,
            'expires_at' => time() + $expiresIn,
        ]));

        return $token;
    }

    /**
     * Assinatura AWS Signature V4
     */
    private function signRequest($method, $path, $query = '', $body = '', $accessToken) {
        $service = 'execute-api';
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $region = $this->region;

        // Canonical Request
        $canonicalUri = $path;
        $canonicalQueryString = $query;
        $payloadHash = hash('sha256', $body);

        $headers = [
            'host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-access-token' => $accessToken,
            'x-amz-date' => $amzDate,
        ];

        $signedHeaders = implode(';', array_keys($headers));
        $canonicalHeaders = '';
        foreach ($headers as $k => $v) {
            $canonicalHeaders .= "{$k}:{$v}\n";
        }

        $canonicalRequest = "{$method}\n{$canonicalUri}\n{$canonicalQueryString}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

        // String to Sign
        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        // Signing Key
        $kDate = hash_hmac('sha256', $dateStamp, "AWS4" . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // Authorization Header
        $authorization = "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        return $authorization;
    }

    /**
     * Fazer chamada a API
     */
    public function call($method, $path, $query = '', $body = '') {
        $accessToken = $this->getAccessToken();
        $authHeader = $this->signRequest($method, $path, $query, $body, $accessToken);

        $url = "{$this->endpoint}{$path}" . ($query ? "?{$query}" : '');

        $headers = [
            "x-amz-access-token: {$accessToken}",
            'x-amz-date: ' . gmdate('Ymd\THis\Z'),
            "Authorization: {$authHeader}",
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'MarketManagerPro/4.0',
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: {$error}");
        }

        return [
            'status' => $httpCode,
            'data' => json_decode($response, true),
            'raw' => $response,
        ];
    }

    /**
     * Buscar pedidos recentes da Amazon
     */
    public function getOrders($createdAfter = null, $maxResults = 20) {
        if (!$createdAfter) {
            $createdAfter = gmdate('c', strtotime('-24 hours'));
        }

        $query = http_build_query([
            'CreatedAfter' => $createdAfter,
            'MarketplaceIds' => $this->getMarketplaceId(),
            'MaxResults' => $maxResults,
        ]);

        $result = $this->call('GET', '/orders/v0/orders', $query);

        if ($result['status'] >= 400) {
            return ['error' => true, 'message' => $result['raw']];
        }

        return $result['data']['orders'] ?? [];
    }

    /**
     * Buscar detalhes completos de um pedido
     */
    public function getOrderDetails($orderId) {
        $query = http_build_query([
            'MarketplaceIds' => $this->getMarketplaceId(),
        ]);

        $result = $this->call('GET', "/orders/v0/orders/{$orderId}", $query);
        return $result['data'] ?? [];
    }

    /**
     * Buscar itens de um pedido com detalhes de preço
     */
    public function getOrderItems($orderId) {
        $query = http_build_query([
            'MarketplaceIds' => $this->getMarketplaceId(),
        ]);

        $result = $this->call('GET', "/orders/v0/orders/{$orderId}/orderItems", $query);
        return $result['data']['OrderItems'] ?? [];
    }

    /**
     * Buscar endereço de entrega do pedido
     */
    public function getOrderAddress($orderId) {
        $orderDetails = $this->getOrderDetails($orderId);
        return $orderDetails['ShippingAddress'] ?? [];
    }

    /**
     * Buscar informações de pagamento do pedido
     */
    public function getOrderFinancials($orderId) {
        $orderDetails = $this->getOrderDetails($orderId);
        return [
            'total' => $orderDetails['OrderTotal']['Amount'] ?? 0,
            'currency' => $orderDetails['OrderTotal']['CurrencyCode'] ?? 'BRL',
            'payment_method' => $orderDetails['PaymentMethod'] ?? 'unknown',
            'payment_status' => $orderDetails['PaymentStatus'] ?? 'unknown',
        ];
    }

    /**
     * Sincronizar pedidos com o sistema local
     */
    public function syncOrders() {
        $orders = $this->getOrders();

        if (isset($orders['error'])) {
            return ['success' => false, 'message' => $orders['message']];
        }

        $pedidos = carregarJSON(PEDIDOS_FILE);
        $produtos = carregarJSON(PRODUTOS_FILE);
        $config = carregarJSON(CONFIG_FILE);
        $importados = 0;
        $atualizados = 0;

        foreach ($orders as $order) {
            $orderId = $order['AmazonOrderId'];
            $orderItems = $this->getOrderItems($orderId);
            $orderAddress = $this->getOrderAddress($orderId);
            $orderFinancials = $this->getOrderFinancials($orderId);

            // Verificar se pedido já existe
            $pedidoExistente = null;
            $pedidoIndex = -1;
            foreach ($pedidos as $index => $p) {
                if (isset($p['amazon_order_id']) && $p['amazon_order_id'] === $orderId) {
                    $pedidoExistente = $p;
                    $pedidoIndex = $index;
                    break;
                }
            }

            foreach ($orderItems as $item) {
                $itemPrice = $item['ItemPrice']['Amount'] ?? 0;
                $itemTitle = $item['Title'] ?? 'Produto Amazon';
                $asin = $item['ASIN'] ?? '';
                $quantity = $item['QuantityOrdered'] ?? 1;

                // Dados do cliente
                $cliente = [
                    'nome' => $order['BuyerName'] ?? 'Comprador Amazon',
                    'email' => $order['BuyerEmail'] ?? '',
                    'telefone' => $order['BuyerPhone'] ?? '',
                    'endereco' => [
                        'nome' => $orderAddress['Name'] ?? '',
                        'rua' => $orderAddress['AddressLine1'] ?? '',
                        'numero' => $orderAddress['AddressLine2'] ?? '',
                        'complemento' => $orderAddress['AddressLine3'] ?? '',
                        'cidade' => $orderAddress['City'] ?? '',
                        'estado' => $orderAddress['StateOrRegion'] ?? '',
                        'cep' => $orderAddress['PostalCode'] ?? '',
                        'pais' => $orderAddress['CountryCode'] ?? 'BR',
                    ],
                ];

                // Dados do produto
                $produto = [
                    'nome' => $itemTitle,
                    'asin' => $asin,
                    'sku' => $item['SellerSKU'] ?? '',
                    'precoCusto' => 0, // Preencher manualmente
                    'precoVenda' => (float)$itemPrice,
                    'quantidade' => $quantity,
                    'categoria' => 'outros',
                ];

                // Dados de pagamento
                $pagamento = [
                    'metodo' => $orderFinancials['payment_method'] ?? 'amazon',
                    'status' => $orderFinancials['payment_status'] ?? 'unknown',
                    'total' => (float)$orderFinancials['total'],
                    'moeda' => $orderFinancials['currency'] ?? 'BRL',
                ];

                // Dados de rastreio
                $rastreio = [
                    'codigo' => $order['LatestShipDate'] ? $order['LatestShipDate'] : '',
                    'transportadora' => 'amazon',
                    'status' => $this->mapearStatusAmazon($order['OrderStatus'] ?? 'Pending'),
                    'dataEnvio' => !empty($order['ShipDate']) ? date('Y-m-d', strtotime($order['ShipDate'])) : '',
                    'dataEntrega' => !empty($order['LatestDeliveryDate']) ? date('Y-m-d', strtotime($order['LatestDeliveryDate'])) : '',
                ];

                // Links
                $links = [
                    'produto' => $asin ? "https://www.amazon.com.br/gp/product/{$asin}" : '',
                    'rastreio' => '',
                    'maps' => $cliente['endereco']['cep'] ? "https://www.google.com/maps/search/?api=1&query=" . urlencode($cliente['endereco']['cep']) : '',
                ];

                if ($pedidoExistente) {
                    // Atualizar pedido existente
                    $pedidos[$pedidoIndex] = array_merge($pedidoExistente, [
                        'cliente' => $cliente,
                        'produto' => $produto,
                        'pagamento' => $pagamento,
                        'rastreio' => $rastreio,
                        'links' => $links,
                        'observacoes' => "Atualizado via Amazon SP-API em " . date('Y-m-d H:i:s'),
                    ]);
                    $atualizados++;
                } else {
                    // Criar novo pedido
                    $novoPedido = [
                        'id' => gerarId($pedidos),
                        'amazon_order_id' => $orderId,
                        'codigoRastreio' => $rastreio['codigo'],
                        'contaShopee' => 'amazon',
                        'plataforma' => 'amazon',
                        'cliente' => $cliente,
                        'produto' => $produto,
                        'rastreio' => $rastreio,
                        'pagamento' => $pagamento,
                        'links' => $links,
                        'observacoes' => "Importado automaticamente via Amazon SP-API",
                        'dataCadastro' => !empty($order['PurchaseDate']) ? date('Y-m-d', strtotime($order['PurchaseDate'])) : date('Y-m-d'),
                    ];

                    $pedidos[] = $novoPedido;
                    $importados++;
                }

                // Adicionar produto se não existir
                $prodExiste = false;
                foreach ($produtos as $p) {
                    if ($p['asin'] === $asin) {
                        $prodExiste = true;
                        break;
                    }
                }
                if (!$prodExiste && !empty($asin)) {
                    $produtos[] = [
                        'id' => gerarId($produtos),
                        'nome' => $itemTitle,
                        'asin' => $asin,
                        'sku' => $item['SellerSKU'] ?? '',
                        'plataforma' => 'amazon',
                        'precoCusto' => 0,
                        'precoVenda' => (float)$itemPrice,
                        'categoria' => 'outros',
                        'estoque' => 0,
                        'descricao' => '',
                        'link' => "https://www.amazon.com.br/gp/product/{$asin}",
                        'dataCadastro' => date('Y-m-d'),
                    ];
                }
            }
        }

        if ($importados > 0 || $atualizados > 0) {
            salvarJSON(PEDIDOS_FILE, $pedidos);
            salvarJSON(PRODUTOS_FILE, $produtos);
            logSistema("Amazon API: {$importados} pedidos importados, {$atualizados} atualizados");
        }

        return [
            'success' => true,
            'importados' => $importados,
            'atualizados' => $atualizados,
            'total_encontrados' => count($orders)
        ];
    }

    private function mapearStatusAmazon($status) {
        $mapa = [
            'Pending' => 'pendente',
            'Unshipped' => 'processando',
            'PartiallyShipped' => 'transito',
            'Shipped' => 'transito',
            'Delivered' => 'entregue',
            'Canceled' => 'cancelado',
            'Unfulfillable' => 'pendente',
        ];
        return $mapa[$status] ?? 'pendente';
    }

    private function getMarketplaceId() {
        $marketplaces = [
            'US' => 'ATVPDKIKX0DER',
            'BR' => 'A2Q3Y263D00KWC',
            'MX' => 'A1AM78C64UM0Y8',
            'CA' => 'A2EUQ1WTGCTBG2',
            'UK' => 'A1F83G8C2ARO7P',
            'DE' => 'A1PA6795UKMFR9',
            'FR' => 'A13V1IB3VIYZZH',
            'IT' => 'APJ6JRA9NG5V9',
            'ES' => 'A1RKKUPIHCS9HS',
            'IN' => 'A21TJRUUN4KGV',
            'JP' => 'A1VC38T7YXB528',
            'AU' => 'A39IBJ37TRP1C6',
            'SG' => 'A19VAU5U5O7RUS',
            'AE' => 'A2VIGQ35RCS4UG',
            'SA' => 'A2EUQ1WTGCTBG2',
            'NL' => 'A1805IZSGTT6HS',
            'BE' => 'A2N3K2VJ7L4B1J',
            'PL' => 'A2QGRYL447D2BX',
            'SE' => 'A2VIGQ35RCS4UG',
            'EG' => 'A1ZZFT5FULY4CN',
            'TR' => 'A2QVEYRB5N8BFZ'
        ];

        $mp = strtoupper($this->marketplace);
        return $marketplaces[$mp] ?? 'ATVPDKIKX0DER';
    }

    private function getMarketplaceFromConfig() {
        return $this->marketplace;
    }

    /**
     * Buscar produtos do inventário da Amazon
     */
    public function getInventory($sku = null) {
        $path = $sku ? "/fba/inventory/v1/summaries/{$sku}" : '/fba/inventory/v1/summaries';

        $query = http_build_query([
            'details' => 'true',
            'granularityType' => 'Marketplace',
            'granularityId' => $this->getMarketplaceId(),
            'marketplaceIds' => $this->getMarketplaceId(),
        ]);

        $result = $this->call('GET', $path, $query);

        if ($result['status'] >= 400) {
            return ['error' => true, 'message' => $result['raw']];
        }

        return $result['data'] ?? [];
    }

    /**
     * Buscar detalhes de um produto por ASIN
     */
    public function getProductByASIN($asin) {
        $query = http_build_query([
            'MarketplaceId' => $this->getMarketplaceId(),
            'ASIN' => $asin,
        ]);

        $result = $this->call('GET', '/products/pricing/v0/listings/' . $this->getMarketplaceId() . '/' . $asin, $query);

        if ($result['status'] >= 400) {
            return ['error' => true, 'message' => $result['raw']];
        }

        return $result['data'] ?? [];
    }

    /**
     * Sincronizar produtos do inventário da Amazon
     */
    public function syncProducts() {
        $inventory = $this->getInventory();

        if (isset($inventory['error'])) {
            return ['success' => false, 'message' => $inventory['message']];
        }

        $produtos = carregarJSON(PRODUTOS_FILE);
        $config = carregarJSON(CONFIG_FILE);
        $importados = 0;
        $atualizados = 0;

        foreach ($inventory as $item) {
            $asin = $item['asin'] ?? '';
            $sku = $item['sellerSku'] ?? '';
            $quantity = $item['quantityAvailable'] ?? 0;

            if (empty($asin)) {
                continue;
            }

            // Verificar se produto já existe
            $prodExistente = null;
            $prodIndex = -1;
            foreach ($produtos as $index => $p) {
                if ($p['asin'] === $asin) {
                    $prodExistente = $p;
                    $prodIndex = $index;
                    break;
                }
            }

            // Buscar detalhes do produto
            $productDetails = $this->getProductByASIN($asin);
            $price = $productDetails['Price']['ListingPrice']['Amount'] ?? 0;
            $title = $productDetails['Title'] ?? 'Produto Amazon';

            if ($prodExistente) {
                // Atualizar produto existente
                $produtos[$prodIndex] = array_merge($prodExistente, [
                    'estoque' => $quantity,
                    'sku' => $sku,
                    'precoVenda' => (float)$price,
                    'nome' => $title,
                ]);
                $atualizados++;
            } else {
                // Criar novo produto
                $produtos[] = [
                    'id' => gerarId($produtos),
                    'nome' => $title,
                    'asin' => $asin,
                    'sku' => $sku,
                    'plataforma' => 'amazon',
                    'precoCusto' => 0,
                    'precoVenda' => (float)$price,
                    'categoria' => 'outros',
                    'estoque' => $quantity,
                    'descricao' => '',
                    'link' => "https://www.amazon.com.br/gp/product/{$asin}",
                    'dataCadastro' => date('Y-m-d'),
                ];
                $importados++;
            }
        }

        if ($importados > 0 || $atualizados > 0) {
            salvarJSON(PRODUTOS_FILE, $produtos);
            logSistema("Amazon API: {$importados} produtos importados, {$atualizados} atualizados");
        }

        return [
            'success' => true,
            'importados' => $importados,
            'atualizados' => $atualizados,
            'total_encontrados' => count($inventory)
        ];
    }

    /**
     * Buscar notificações de vendas recentes
     */
    public function getSalesNotifications($hours = 24) {
        $createdAfter = gmdate('c', strtotime("-{$hours} hours"));
        $orders = $this->getOrders($createdAfter, 50);

        if (isset($orders['error'])) {
            return ['error' => true, 'message' => $orders['message']];
        }

        $notifications = [];
        foreach ($orders as $order) {
            $orderId = $order['AmazonOrderId'];
            $orderItems = $this->getOrderItems($orderId);
            $orderFinancials = $this->getOrderFinancials($orderId);

            foreach ($orderItems as $item) {
                $notifications[] = [
                    'order_id' => $orderId,
                    'customer_name' => $order['BuyerName'] ?? 'Comprador',
                    'customer_email' => $order['BuyerEmail'] ?? '',
                    'customer_phone' => $order['BuyerPhone'] ?? '',
                    'product_name' => $item['Title'] ?? 'Produto',
                    'asin' => $item['ASIN'] ?? '',
                    'quantity' => $item['QuantityOrdered'] ?? 1,
                    'price' => $item['ItemPrice']['Amount'] ?? 0,
                    'total' => $orderFinancials['total'] ?? 0,
                    'currency' => $orderFinancials['currency'] ?? 'BRL',
                    'status' => $this->mapearStatusAmazon($order['OrderStatus'] ?? 'Pending'),
                    'purchase_date' => $order['PurchaseDate'] ?? '',
                    'payment_method' => $orderFinancials['payment_method'] ?? 'unknown',
                ];
            }
        }

        return $notifications;
    }
}
