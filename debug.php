<?php
// Página de depuração
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Depuração do Sistema</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .box {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            margin: 10px 0;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        pre {
            background: rgba(0,0,0,0.3);
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success { color: #2ecc71; }
        .error { color: #e74c3c; }
        a { color: #3498db; text-decoration: none; padding: 10px 20px; background: rgba(0,0,0,0.2); border-radius: 5px; display: inline-block; margin: 5px; }
        a:hover { background: rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <h1>🔍 SISTEMA DE DEPURAÇÃO</h1>

    <div class="box">
        <h2>📊 Status do Servidor</h2>
        <p class="success">✅ PHP funcionando corretamente</p>
        <p>Horário: <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>Versão PHP: <?php echo phpversion(); ?></p>
    </div>

    <div class="box">
        <h2>📁 Arquivos no Diretório</h2>
        <pre><?php
        $files = glob('*.php');
        foreach($files as $file) {
            echo "- $file [" . number_format(filesize($file)/1024, 2) . " KB]\n";
        }
        ?></pre>
    </div>

    <div class="box">
        <h2>🔗 Links para Teste</h2>
        <a href="index.php">index.php (versão original)</a>
        <a href="indexteste.php">indexteste.php</a>
        <a href="test_minimal.php">test_minimal.php</a>
        <a href="ma.php">ma.php</a>
        <a href="zap.php">zap.php</a>
    </div>

    <div class="box">
        <h2>🐞 Diagnóstico do Index.php</h2>
        <pre><?php
        $indexContent = file_get_contents('index.php');

        // Verificar se contém HTML essencial
        $hasDoctype = stripos($indexContent, '<!DOCTYPE') !== false;
        $hasHtml = stripos($indexContent, '<html') !== false;
        $hasBody = stripos($indexContent, '<body') !== false;
        $hasContainer = stripos($indexContent, 'class="container') !== false;
        $hasMarketManager = stripos($indexContent, 'MarketManager') !== false;

        echo "DOCTYPE: " . ($hasDoctype ? "✅" : "❌") . "\n";
        echo "HTML tag: " . ($hasHtml ? "✅" : "❌") . "\n";
        echo "BODY tag: " . ($hasBody ? "✅" : "❌") . "\n";
        echo "Container: " . ($hasContainer ? "✅" : "❌") . "\n";
        echo "MarketManager: " . ($hasMarketManager ? "✅" : "❌") . "\n";

        // Verificar JavaScript crítico
        if ($hasMarketManager) {
            // Procurar pela função init
            $hasInit = stripos($indexContent, 'async init()') !== false;
            echo "Init function: " . ($hasInit ? "✅" : "❌") . "\n";
        }
        ?></pre>
    </div>

    <div class="box">
        <h2>💡 Possíveis Soluções</h2>
        <ol>
            <li>Limpe o cache do navegador: <strong>Ctrl+Shift+Del</strong></li>
            <li>Abra o console: <strong>F12</strong> e procure por erros</li>
            <li>Verifique na aba "Network" se algum arquivo não carregou</li>
            <li>Se nada funcionar, use o backup: indexteste.php</li>
        </ol>
    </div>

    <div class="box">
        <h2>🔧 Ações Rápidas</h2>
        <button onclick="clearStorage()" style="padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">Limpar Tudo (localStorage + recarregar)</button>
        <button onclick="debugConsole()" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Ver Console</button>
    </div>

    <script>
        function clearStorage() {
            if(confirm('Isso limpará todos os dados salvos e recarregará a página. Continuar?')) {
                localStorage.clear();
                sessionStorage.clear();
                console.log('🗑️ Storage limpo!');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            }
        }

        function debugConsole() {
            console.log('🔍 Verificando estado do sistema...');
            console.log('Body classes:', document.body.className);
            console.log('LocalStorage:', Object.keys(localStorage));
            console.log('URL atual:', window.location.href);

            // Verificar se há erros
            const errorElements = document.querySelectorAll('script[src]');
            errorElements.forEach(script => {
                if(!script.onload) {
                    script.onload = () => console.log(`✅ Carregado: ${script.src}`);
                }
                script.onerror = () => console.error(`❌ Erro ao carregar: ${script.src}`);
            });
        }

        // Auto-verificar
        debugConsole();
    </script>
</body>
</html>