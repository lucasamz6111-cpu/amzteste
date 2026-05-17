<?php
// Proteção de acesso à pasta /data
http_response_code(403);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔒 Acesso Negado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-bonax;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f1a2c 0%, #1a2a3e 50%, #162440 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #e4e9f0;
        }
        
        .container {
            text-align: center;
            background: rgba(0, 0, 0, 0.4);
            padding: 60px 40px;
            border-radius: 20px;
            border: 3px solid #e74c3c;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 0 60px rgba(231, 76, 60, 0.3), inset 0 0 20px rgba(0, 0, 0, 0.2);
        }
        
        .lock-icon {
            font-size: 120px;
            margin-bottom: 30px;
            animation: lock-pulse 2s ease-in-out infinite;
            color: #e74c3c;
            display: inline-block;
        }
        
        @keyframes lock-pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
                filter: drop-shadow(0 0 0px #e74c3c);
            }
            50% {
                transform: scale(1.12);
                opacity: 0.85;
                filter: drop-shadow(0 0 20px #e74c3c);
            }
        }
        
        h1 {
            font-size: 38px;
            margin-bottom: 15px;
            color: #e74c3c;
            font-weight: 800;
            text-shadow: 0 2px 15px rgba(231, 76, 60, 0.4);
        }
        
        .subtitle {
            font-size: 18px;
            color: #f39c12;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .message {
            font-size: 16px;
            color: #8a9bb2;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        .info-box {
            background: rgba(231, 76, 60, 0.1);
            border-left: 5px solid #e74c3c;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            text-align: left;
        }
        
        .info-box strong {
            color: #e74c3c;
            display: block;
            margin-bottom: 12px;
            font-size: 17px;
        }
        
        .info-box p {
            margin: 8px 0;
            color: #8a9bb2;
            font-size: 14px;
            padding-left: 25px;
            position: relative;
        }
        
        .info-box p:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #2ecc71;
            font-weight: bold;
            font-size: 16px;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #00a8ff, #0080cc);
            color: white;
            padding: 16px 45px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            margin-top: 20px;
            box-shadow: 0 6px 20px rgba(0, 168, 255, 0.25);
        }
        
        .button:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0, 168, 255, 0.4);
            background: linear-gradient(135deg, #00c1ff, #0090dd);
        }
        
        .button:active {
            transform: translateY(-1px);
        }
        
        .footer {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 12px;
            color: #5a6a7a;
        }
        
        .warning-flash {
            animation: flash 0.5s ease-in-out;
        }
        
        @keyframes flash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Responsividade */
        @media (max-width: 600px) {
            .container {
                padding: 40px 25px;
            }
            
            .lock-icon {
                font-size: 80px;
                margin-bottom: 20px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .button {
                padding: 14px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container warning-flash">
        <div class="lock-icon">🔒</div>
        <h1>Acesso Negado!</h1>
        <p class="subtitle">🚫 Área Protegida - Acesso Restrito</p>
        
        <p class="message">
            Esta pasta contém dados sensíveis e não pode ser acessada diretamente. O acesso está bloqueado por questões de segurança.
        </p>
        
        <div class="info-box">
            <strong>⚠️ Sistema de Segurança Ativo:</strong>
            <p>Arquivos de configuração bloqueados</p>
            <p>Banco de dados protegido com criptografia</p>
            <p>Todos os acessos não autorizados são registrados</p>
        </div>
        
        <p class="message">
            Se você é o <strong>administrador do sistema</strong> e precisa acessar esses dados, utilize a <strong>interface profissional</strong> através do painel de controle principal.
        </p>
        
        <a href="/amazongest/" class="button">← Voltar à Aplicação Principal</a>
        
        <div class="footer">
            <p><strong>Market Manager Pro</strong> © 2026 | Todos os direitos reservados</p>
            <p style="margin-top: 8px; color: #3a4a5a;">Sistema de Proteção de Dados v2.0</p>
        </div>
    </div>
</body>
</html>
