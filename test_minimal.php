<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Mínimo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f0f0f0;
        }
        .test-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover {
            background: #27ae60;
        }
    </style>
</head>
<body>
    <div class="test-box">
        <h1>✅ Teste de Funcionamento</h1>
        <p>Se você está vendo esta página, o servidor está funcionando!</p>

        <h2>Links disponíveis:</h2>
        <a href="index.php" class="btn">Tentar Index.php</a>
        <a href="indexteste.php" class="btn">Versão de Teste</a>
        <a href="ma.php" class="btn">Página MA</a>

        <h3>O que testar:</h3>
        <ul>
            <li>Abra o console do navegador (F12)</li>
            <li>Veja se há erros de JavaScript</li>
            <li>Verifique na aba "Network" se os CSS/JS carregam</li>
        </ul>
    </div>

    <div class="test-box">
        <h2>Código de depuração:</h2>
        <button onclick="testCSS()" class="btn">Testar CSS</button>
        <button onclick="testJS()" class="btn">Testar JavaScript</button>
        <button onclick="testAPI()" class="btn">Testar API</button>
        <div id="test-results"></div>
    </div>

    <script>
        function testCSS() {
            document.getElementById('test-results').innerHTML = '<p>✅ CSS está funcionando!</p>';
        }

        function testJS() {
            if (typeof console !== 'undefined') {
                document.getElementById('test-results').innerHTML = '<p>✅ JavaScript está funcionando!</p>';
            } else {
                document.getElementById('test-results').innerHTML = '<p>❌ JavaScript não está funcionando!</p>';
            }
        }

        function testAPI() {
            fetch('index.php')
                .then(response => response.text())
                .then(data => {
                    if (data.includes('Market Manager Pro')) {
                        document.getElementById('test-results').innerHTML = '<p>✅ API/Index.php está respondendo!</p>';
                    } else {
                        document.getElementById('test-results').innerHTML = '<p>⚠️ API/Index.php não está retornando o esperado!</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('test-results').innerHTML = '<p>❌ Erro: ' + error.message + '</p>';
                });
        }
    </script>
</body>
</html>