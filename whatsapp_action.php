<?php
require __DIR__ . '/includes/whatsapp_api.php';

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'] ?? '';
ensureWhatsappFiles();

if ($action === 'status') {
    echo json_encode(getWhatsappStatus());
    exit;
}

if ($action === 'conversations') {
    echo json_encode(getConversations());
    exit;
}

if ($action === 'messages') {
    $contact = $_GET['contact'] ?? '';
    if (empty($contact)) {
        echo json_encode([]);
        exit;
    }
    echo json_encode(array_values(getConversationMessages($contact)));
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];

if ($action === 'save_config') {
    $accessToken = trim($body['access_token'] ?? '');
    $phoneNumberId = trim($body['phone_number_id'] ?? '');
    $webhookToken = trim($body['webhook_verify_token'] ?? 'amazongest_verify');

    saveWhatsappConfig([
        'access_token' => $accessToken,
        'phone_number_id' => $phoneNumberId,
        'webhook_verify_token' => $webhookToken
    ]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'send') {
    $to = trim($body['to'] ?? '');
    $message = trim($body['message'] ?? '');

    if (empty($to) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Número e mensagem são obrigatórios']);
        exit;
    }

    $result = sendWhatsappMessage($to, $message);
    echo json_encode($result);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Ação inválida']);
