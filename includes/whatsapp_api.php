<?php

function ensureWhatsappFiles() {
    $configFile = __DIR__ . '/../data/whatsapp_config.json';
    $messagesFile = __DIR__ . '/../data/whatsapp_messages.json';

    if (!file_exists($configFile)) {
        file_put_contents($configFile, json_encode(["access_token" => "", "phone_number_id" => "", "webhook_verify_token" => "amazongest_verify"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    if (!file_exists($messagesFile)) {
        file_put_contents($messagesFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function loadWhatsappConfig() {
    ensureWhatsappFiles();
    $configFile = __DIR__ . '/../data/whatsapp_config.json';
    $config = json_decode(file_get_contents($configFile), true);
    if (!is_array($config)) {
        $config = ["access_token" => "", "phone_number_id" => "", "webhook_verify_token" => "amazongest_verify"];
    }
    return $config;
}

function saveWhatsappConfig(array $config) {
    ensureWhatsappFiles();
    $configFile = __DIR__ . '/../data/whatsapp_config.json';
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function loadWhatsappMessages() {
    ensureWhatsappFiles();
    $messagesFile = __DIR__ . '/../data/whatsapp_messages.json';
    $messages = json_decode(file_get_contents($messagesFile), true);
    return is_array($messages) ? $messages : [];
}

function saveWhatsappMessages(array $messages) {
    ensureWhatsappFiles();
    $messagesFile = __DIR__ . '/../data/whatsapp_messages.json';
    file_put_contents($messagesFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function appendWhatsappMessage(array $message) {
    $messages = loadWhatsappMessages();
    $message['id'] = $message['id'] ?? uniqid('', true);
    $message['timestamp'] = $message['timestamp'] ?? time();
    $messages[] = $message;
    saveWhatsappMessages($messages);
}

function whatsappApiRequest(string $method, string $url, array $payload = []) {
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

    if ($method === 'POST' || $method === 'PATCH' || $method === 'PUT') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

    $result = curl_exec($curl);
    $error = curl_error($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($result === false) {
        return [
            'success' => false,
            'error' => $error,
            'status' => $status
        ];
    }

    $decoded = json_decode($result, true);
    return [
        'success' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => $decoded,
        'raw' => $result
    ];
}

function getWhatsappStatus() {
    $config = loadWhatsappConfig();
    $result = [
        'connected' => false,
        'access_token' => $config['access_token'] ?? '',
        'phone_number_id' => $config['phone_number_id'] ?? '',
        'webhook_verify_token' => $config['webhook_verify_token'] ?? 'amazongest_verify',
        'display_phone_number' => null,
        'error' => null
    ];

    if (empty($config['access_token']) || empty($config['phone_number_id'])) {
        return $result;
    }

    $url = sprintf(
        'https://graph.facebook.com/v18.0/%s?fields=display_phone_number&access_token=%s',
        urlencode($config['phone_number_id']),
        urlencode($config['access_token'])
    );

    $response = whatsappApiRequest('GET', $url);
    if ($response['success'] && isset($response['body']['display_phone_number'])) {
        $result['connected'] = true;
        $result['display_phone_number'] = $response['body']['display_phone_number'];
    } else {
        $result['error'] = $response['body']['error']['message'] ?? ($response['error'] ?? 'Falha na conexão');
    }

    return $result;
}

function sendWhatsappMessage(string $toNumber, string $text) {
    $config = loadWhatsappConfig();

    if (empty($config['access_token']) || empty($config['phone_number_id'])) {
        return ['success' => false, 'error' => 'Configuração incompleta'];
    }

    $to = preg_match('/@/', $toNumber) ? $toNumber : preg_replace('/[^0-9]/', '', $toNumber) . '@c.us';
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => ['body' => $text]
    ];

    $url = sprintf(
        'https://graph.facebook.com/v18.0/%s/messages?access_token=%s',
        urlencode($config['phone_number_id']),
        urlencode($config['access_token'])
    );

    $response = whatsappApiRequest('POST', $url, $payload);
    if ($response['success']) {
        appendWhatsappMessage([
            'direction' => 'sent',
            'from' => 'me',
            'to' => $to,
            'body' => $text,
            'timestamp' => time()
        ]);
        return ['success' => true, 'body' => $response['body']];
    }

    return ['success' => false, 'error' => $response['body']['error']['message'] ?? ($response['error'] ?? 'Falha ao enviar')];
}

function normalizeContactId(string $contact) {
    return preg_replace('/[^0-9]/', '', str_replace('@c.us', '', $contact));
}

function getConversationMessages(string $contactId) {
    $messages = loadWhatsappMessages();
    $contactId = normalizeContactId($contactId);
    return array_filter($messages, function ($item) use ($contactId) {
        $from = normalizeContactId($item['from'] ?? '');
        $to = normalizeContactId($item['to'] ?? '');
        return $from === $contactId || $to === $contactId;
    });
}

function getConversations() {
    $messages = loadWhatsappMessages();
    $conversations = [];

    foreach ($messages as $message) {
        $contact = normalizeContactId($message['from'] === 'me' ? ($message['to'] ?? '') : ($message['from'] ?? ''));
        if (empty($contact)) {
            continue;
        }

        if (!isset($conversations[$contact]) || ($message['timestamp'] ?? 0) > ($conversations[$contact]['timestamp'] ?? 0)) {
            $conversations[$contact] = [
                'id' => $contact,
                'name' => $contact,
                'lastMessage' => $message['body'] ?? '',
                'timestamp' => $message['timestamp'] ?? time(),
                'direction' => $message['direction'] ?? 'received'
            ];
        }
    }

    usort($conversations, function ($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });

    return array_values($conversations);
}
