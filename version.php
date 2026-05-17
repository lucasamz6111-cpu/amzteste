<?php
$packageFile = __DIR__ . '/package.json';
$version = 'desconhecida';
$updated = null;
if (file_exists($packageFile)) {
    $content = file_get_contents($packageFile);
    $json = json_decode($content, true);
    if (isset($json['version'])) {
        $version = $json['version'];
    }
    $updated = date('d/m/Y H:i:s', filemtime($packageFile));
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Versão do Sistema</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 2rem; background: #f4f4f4; color: #111; }
    .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,.12); max-width: 520px; margin: auto; }
    h1 { margin-top: 0; }
    .data { margin: 0.75rem 0; padding: 0.85rem; background: #f7f7f7; border-radius: 6px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Versão do Sistema</h1>
    <p>Use este arquivo para mostrar se o projeto foi atualizado recentemente.</p>
    <div class="data"><strong>Versão:</strong> <?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="data"><strong>Última modificação:</strong> <?php echo htmlspecialchars($updated ?: 'não disponível', ENT_QUOTES, 'UTF-8'); ?></div>
    <p>Depois de rodar <code>amzbelly update</code>, recarregue esta página para ver a versão e hora atualizadas.</p>
  </div>
</body>
</html>
