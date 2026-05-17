<?php
// Resetar configurações do sistema
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Remover classes do body e limpar configurações
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetar Sistema</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .btn { padding: 10px 20px; margin: 5px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Resetar Sistema Demostra</h1>
        <p>Clique no botão abaixo para limpar todas as configurações e retornar ao sistema normal:</p>

        <button onclick="resetar()" class="btn">🔄 Resetar Sistema Agora</button>
        <a href="index.php" class="btn">🚀 Acessar Sistema</a>

        <hr>

        <?php
        if ($_POST['resetar']) {
            echo '<p class="success">✅ Sistema resetado com sucesso!</p>';
            echo '<meta http-equiv="refresh" content="2;url=index.php">';
        }
        ?>

        <form method="post">
            <button type="submit" name="resetar" value="1" class="btn">✅ Resetar via PHP</button>
        </form>
    </div>

    <script>
        function resetar() {
            // Limpa todo o localStorage
            localStorage.clear();

            // Limpa sessionStorage
            sessionStorage.clear();

            // Remove classes específicas
            document.body.classList.remove('dados-ocultados');
            document.body.classList.remove('modo-manutencao');

            // Mostra mensagem
            alert('Sistema resetado! Redirecionando...');

            // Redireciona
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        }

        // Auto-execução ao carregar
        window.onload = function() {
            // Força limpeza do body
            document.body.className = '';

            // Remove classes de ocultação
            const elementos = document.querySelectorAll('.dados-ocultados');
            elementos.forEach(el => {
                if (el.id === 'body') {
                    el.classList.remove('dados-ocultados');
                }
            });
        };
    </script>
</body>
</html>