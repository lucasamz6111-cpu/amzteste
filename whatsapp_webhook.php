<?php
require __DIR__ . '/includes/whatsapp_api.php';

$verifyToken = loadWhatsappConfig()['webhook_verify_token'] ?? 'amazongest_verify';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? null;
    $token = $_GET['hub_verify_token'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? null;

    if ($mode === 'subscribe' && $token === $verifyToken && $challenge) {
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo 'Verification failed';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo 'Invalid payload';
        exit;
    }

    $entries = $data['entry'] ?? [];
    foreach ($entries as $entry) {
        $changes = $entry['changes'] ?? [];
        foreach ($changes as $change) {
            $value = $change['value'] ?? [];
            $messages = $value['messages'] ?? [];
            foreach ($messages as $message) {
                $from = $message['from'] ?? '';
                $text = $message['text']['body'] ?? ($message['type'] === 'text' ? $message['text']['body'] : '');
                if (!$text) {
                    continue;
                }
                appendWhatsappMessage([
                    'direction' => 'received',
                    'from' => $from,
                    'to' => 'me',
                    'body' => $text,
                    'timestamp' => $message['timestamp'] ?? time()
                ]);
            }
        }
    }

    echo 'EVENT_RECEIVED';
    exit;
}

http_response_code(405);
echo 'Method not allowed';
