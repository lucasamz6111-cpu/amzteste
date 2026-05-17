<?php
// ============================================
// ARQUIVO: training.php
// SISTEMA: Market Manager Pro - Sistema de Treinamento IA
// VERSÃO: 1.0
// ============================================

// Configurações
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

// Diretórios de dados
define('DATA_DIR', __DIR__ . '/data/');
define('MEMORY_DIR', __DIR__ . '/memory/');
define('TRAINING_CONFIG_FILE', DATA_DIR . 'training_config.json');
define('TRAINING_MEMORY_FILE', MEMORY_DIR . 'training_memory.json');

// Funções de manipulação de dados JSON
function carregarJSON($arquivo) {
    if (!file_exists($arquivo)) {
        file_put_contents($arquivo, json_encode([]));
        return [];
    }
    $conteudo = file_get_contents($arquivo);
    return json_decode($conteudo, true) ?: [];
}

function salvarJSON($arquivo, $dados) {
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($arquivo, $json) !== false;
}

// Carregar configurações de treinamento
$trainingConfig = carregarJSON(TRAINING_CONFIG_FILE);
$trainingMemory = carregarJSON(TRAINING_MEMORY_FILE);

// Processar formulário de configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'salvar_config') {
        $trainingConfig['tom_voz'] = $_POST['tom_voz'] ?? 'Profissional';
        $trainingConfig['nivel_detalhe'] = $_POST['nivel_detalhe'] ?? 'Médio';
        $trainingConfig['idioma'] = $_POST['idioma'] ?? 'Português BR';
        $trainingConfig['personalidade'] = $_POST['personalidade'] ?? 'Entusiasta';
        $trainingConfig['seguranca'] = $_POST['seguranca'] ?? 'Ativada';
        $trainingConfig['pedidos_analisados'] = (int)($_POST['pedidos_analisados'] ?? 1);
        $trainingConfig['produtos_conhecidos'] = (int)($_POST['produtos_conhecidos'] ?? 1);
        $trainingConfig['instrucoes_treinamento'] = $_POST['instrucoes_treinamento'] ?? '';
        $trainingConfig['estilo_resposta'] = $_POST['estilo_resposta'] ?? 'Profissional';
        $trainingConfig['tema_personalizado'] = $_POST['tema_personalizado'] ?? '';

        salvarJSON(TRAINING_CONFIG_FILE, $trainingConfig);
        $mensagem = "Configurações salvas com sucesso!";
    } elseif ($_POST['action'] === 'adicionar_instrucao') {
        $novaInstrucao = trim($_POST['nova_instrucao'] ?? '');
        if (!empty($novaInstrucao)) {
            if (!isset($trainingConfig['instrucoes_sistema'])) {
                $trainingConfig['instrucoes_sistema'] = [];
            }
            $instrucaoData = [
                'texto' => $novaInstrucao,
                'data' => date('Y-m-d H:i:s')
            ];
            $trainingConfig['instrucoes_sistema'][] = $instrucaoData;
            salvarJSON(TRAINING_CONFIG_FILE, $trainingConfig);

            // Salvar na memória de treinamento
            if (!isset($trainingMemory['instrucoes_aplicadas'])) {
                $trainingMemory['instrucoes_aplicadas'] = [];
            }
            $trainingMemory['instrucoes_aplicadas'][] = $instrucaoData;
            salvarJSON(TRAINING_MEMORY_FILE, $trainingMemory);

            $mensagem = "Instrução adicionada ao treinamento e memória!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Treinamento IA - Market Manager Pro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        form { margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        select, input, textarea { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        button:hover { background-color: #0056b3; }
        .mensagem { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .instrucao { background-color: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧠 Sistema de Treinamento IA</h1>
        <p>Configure e treine a IA para responder de acordo com suas preferências.</p>

        <?php if (isset($mensagem)): ?>
            <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <h2>Configurações de Treino</h2>
        <form method="post">
            <input type="hidden" name="action" value="salvar_config">

            <label for="tom_voz">💬 Tom de Voz:</label>
            <select name="tom_voz" id="tom_voz">
                <option value="Profissional" <?php echo ($trainingConfig['tom_voz'] ?? '') === 'Profissional' ? 'selected' : ''; ?>>📋 Profissional</option>
                <option value="Amigável" <?php echo ($trainingConfig['tom_voz'] ?? '') === 'Amigável' ? 'selected' : ''; ?>>😊 Amigável</option>
                <option value="Técnico" <?php echo ($trainingConfig['tom_voz'] ?? '') === 'Técnico' ? 'selected' : ''; ?>>🔧 Técnico</option>
            </select>

            <label for="nivel_detalhe">📖 Nível de Detalhe:</label>
            <select name="nivel_detalhe" id="nivel_detalhe">
                <option value="Baixo" <?php echo ($trainingConfig['nivel_detalhe'] ?? '') === 'Baixo' ? 'selected' : ''; ?>>📊 Baixo</option>
                <option value="Médio" <?php echo ($trainingConfig['nivel_detalhe'] ?? '') === 'Médio' ? 'selected' : ''; ?>>📊 Médio</option>
                <option value="Alto" <?php echo ($trainingConfig['nivel_detalhe'] ?? '') === 'Alto' ? 'selected' : ''; ?>>📊 Alto</option>
            </select>

            <label for="idioma">🌐 Idioma:</label>
            <select name="idioma" id="idioma">
                <option value="Português BR" <?php echo ($trainingConfig['idioma'] ?? '') === 'Português BR' ? 'selected' : ''; ?>>🇧🇷 Português BR</option>
                <option value="Inglês" <?php echo ($trainingConfig['idioma'] ?? '') === 'Inglês' ? 'selected' : ''; ?>>🇺🇸 Inglês</option>
            </select>

            <label for="personalidade">🎭 Personalidade:</label>
            <select name="personalidade" id="personalidade">
                <option value="Entusiasta" <?php echo ($trainingConfig['personalidade'] ?? '') === 'Entusiasta' ? 'selected' : ''; ?>>🌟 Entusiasta</option>
                <option value="Calmo" <?php echo ($trainingConfig['personalidade'] ?? '') === 'Calmo' ? 'selected' : ''; ?>>🧘 Calmo</option>
                <option value="Criativo" <?php echo ($trainingConfig['personalidade'] ?? '') === 'Criativo' ? 'selected' : ''; ?>>🎨 Criativo</option>
            </select>

            <label for="seguranca">🔒 Segurança:</label>
            <select name="seguranca" id="seguranca">
                <option value="Ativada" <?php echo ($trainingConfig['seguranca'] ?? '') === 'Ativada' ? 'selected' : ''; ?>>Ativada</option>
                <option value="Desativada" <?php echo ($trainingConfig['seguranca'] ?? '') === 'Desativada' ? 'selected' : ''; ?>>Desativada</option>
            </select>

            <button type="submit">Salvar Configurações</button>
        </form>

        <h2>Treinar a IA</h2>
        <form method="post">
            <input type="hidden" name="action" value="salvar_config">

            <label for="pedidos_analisados">📋 Pedidos Analisados:</label>
            <input type="number" name="pedidos_analisados" id="pedidos_analisados" value="<?php echo htmlspecialchars($trainingConfig['pedidos_analisados'] ?? 1); ?>" min="0">

            <label for="produtos_conhecidos">📦 Produtos Conhecidos:</label>
            <input type="number" name="produtos_conhecidos" id="produtos_conhecidos" value="<?php echo htmlspecialchars($trainingConfig['produtos_conhecidos'] ?? 1); ?>" min="0">

            <label for="instrucoes_treinamento">✍️ Instruções de Treinamento (Texto Livre):</label>
            <textarea name="instrucoes_treinamento" id="instrucoes_treinamento" rows="5" placeholder="Digite aqui como você quer que a IA responda... Pode ser bem grande e detalhado!"><?php echo htmlspecialchars($trainingConfig['instrucoes_treinamento'] ?? ''); ?></textarea>

            <label for="estilo_resposta">🎯 Estilo de Resposta:</label>
            <select name="estilo_resposta" id="estilo_resposta">
                <option value="Profissional" <?php echo ($trainingConfig['estilo_resposta'] ?? '') === 'Profissional' ? 'selected' : ''; ?>>💼 Profissional - Formal e respeitoso</option>
                <option value="Amigável" <?php echo ($trainingConfig['estilo_resposta'] ?? '') === 'Amigável' ? 'selected' : ''; ?>>😊 Amigável - Casual e próximo</option>
                <option value="Técnica" <?php echo ($trainingConfig['estilo_resposta'] ?? '') === 'Técnica' ? 'selected' : ''; ?>>🔧 Técnica - Detalhada e precisa</option>
                <option value="Sucinta" <?php echo ($trainingConfig['estilo_resposta'] ?? '') === 'Sucinta' ? 'selected' : ''; ?>>⚡ Sucinta</option>
            </select>

            <button type="submit">Treinar IA</button>
        </form>

        <h2>Adicionar Instruções de Sistema</h2>
        <p>Adicione instruções específicas para o comportamento da IA, como "A partir de agora, toda conversa que se inicia, você fala 'Olá, como posso ajudar?'".</p>
        <form method="post">
            <input type="hidden" name="action" value="adicionar_instrucao">
            <label for="nova_instrucao">Nova Instrução:</label>
            <textarea name="nova_instrucao" id="nova_instrucao" rows="3" placeholder="Digite a instrução aqui..."></textarea>
            <button type="submit">Adicionar Instrução</button>
        </form>

        <h2>Instruções Salvas</h2>
        <?php if (!empty($trainingConfig['instrucoes_sistema'])): ?>
            <?php foreach ($trainingConfig['instrucoes_sistema'] as $instrucao): ?>
                <div class="instrucao">
                    <strong><?php echo htmlspecialchars($instrucao['data']); ?>:</strong> <?php echo htmlspecialchars($instrucao['texto']); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhuma instrução salva ainda.</p>
        <?php endif; ?>

        <h2>Memória de Treinamento Aplicada</h2>
        <p>Instruções que foram aplicadas e salvas na memória da IA.</p>
        <?php if (!empty($trainingMemory['instrucoes_aplicadas'])): ?>
            <?php foreach ($trainingMemory['instrucoes_aplicadas'] as $instrucao): ?>
                <div class="instrucao">
                    <strong><?php echo htmlspecialchars($instrucao['data']); ?>:</strong> <?php echo htmlspecialchars($instrucao['texto']); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhuma instrução aplicada ainda.</p>
        <?php endif; ?>

        <h2>Teste de Treinamento</h2>
        <p>Clique aqui para gerar uma resposta de exemplo baseada nas suas configurações de treinamento.</p>
        <button onclick="gerarRespostaExemplo()">Gerar Resposta de Exemplo</button>
        <div id="resposta-exemplo" style="margin-top: 20px; padding: 10px; background-color: #e9ecef; border-radius: 4px; display: none;"></div>
    </div>

    <script>
        function gerarRespostaExemplo() {
            const config = <?php echo json_encode($trainingConfig); ?>;
            let resposta = '';

            // Aplicar tom de voz
            if (config.tom_voz === 'Profissional') {
                resposta += 'Olá! Sou o assistente virtual especializado em gestão de e-commerce. ';
            } else if (config.tom_voz === 'Amigável') {
                resposta += 'Oi! Como vai? Estou aqui para ajudar com seu negócio online! ';
            } else if (config.tom_voz === 'Técnico') {
                resposta += 'Sistema de assistência técnica ativado. ';
            }

            // Aplicar personalidade
            if (config.personalidade === 'Entusiasta') {
                resposta += 'Estou super animado para otimizar suas vendas! ';
            } else if (config.personalidade === 'Calmo') {
                resposta += 'Vamos trabalhar de forma organizada e eficiente. ';
            } else if (config.personalidade === 'Criativo') {
                resposta += 'Vamos pensar fora da caixa para impulsionar seu negócio! ';
            }

            // Aplicar estilo de resposta
            if (config.estilo_resposta === 'Profissional') {
                resposta += 'Posso ajudá-lo com pedidos, produtos ou análises?';
            } else if (config.estilo_resposta === 'Amigável') {
                resposta += 'O que você precisa hoje? Estou aqui para ajudar!';
            } else if (config.estilo_resposta === 'Técnica') {
                resposta += 'Especifique sua consulta para processamento otimizado.';
            } else if (config.estilo_resposta === 'Sucinta') {
                resposta += 'Pronto para ajudar.';
            }

            // Adicionar tema personalizado se existir
            if (config.tema_personalizado) {
                resposta += ' ' + config.tema_personalizado;
            }

            // Adicionar instruções de sistema
            if (config.instrucoes_sistema && config.instrucoes_sistema.length > 0) {
                resposta += ' (Aplicando instruções personalizadas)';
            }

            document.getElementById('resposta-exemplo').innerText = resposta;
            document.getElementById('resposta-exemplo').style.display = 'block';
        }
    </script>
</body>
</html>