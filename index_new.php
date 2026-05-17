<?php
// ============================================
// ARQUIVO: index_new.php - VERSÃO PREMIUM
// SISTEMA: Amazon Gest Professional
// VERSÃO: 5.0 - Design Premium + Cálculos Otimizados
// ============================================

// Configurações
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

// Diretórios de dados
define('DATA_DIR', __DIR__ . '/data/');
define('PEDIDOS_FILE', DATA_DIR . 'pedidos.json');
define('PRODUTOS_FILE', DATA_DIR . 'produtos.json');
define('CLIENTES_FILE', DATA_DIR . 'clientes.json');
define('CONFIG_FILE', DATA_DIR . 'config.json');
define('API_KEYS_FILE', DATA_DIR . 'api-keys.json');

// Criar diretório de dados se não existir
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

// Funções de manipulação de dados JSON
function carregarJSON($arquivo) {
    if (!file_exists($arquivo)) {
        touch($arquivo);
        file_put_contents($arquivo, json_encode([]));
        return [];
    }
    $conteudo = file_get_contents($arquivo);
    if (empty($conteudo)) return [];
    return json_decode($conteudo, true) ?: [];
}

function salvarJSON($arquivo, $dados) {
    $fp = fopen($arquivo, 'c');
    if (!$fp) return false;

    $locked = @flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    fwrite($fp, $json);
    fflush($fp);
    if ($locked) flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

// Carregar dados existentes
$pedidos = carregarJSON(PEDIDOS_FILE);
$produtos = carregarJSON(PRODUTOS_FILE);
$clientes = carregarJSON(CLIENTES_FILE);
$config = carregarJSON(CONFIG_FILE);
$apiKeys = carregarJSON(API_KEYS_FILE);

// Processar requisições AJAX/POST
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? '';

if ($acao && $tipo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json;charset=UTF-8');
    header('Cache-Control: no-cache');
    
    $retorno = ['success' => false];
    
    switch ($acao) {
        case 'salvar':
            $dados = $_POST['dados'] ?? '{}';
            $novoDado = json_decode($dados, true);
            
            if ($tipo === 'pedido') {
                if (empty($novoDado['id'])) {
                    $ids = empty($pedidos) ? 0 : max(array_column($pedidos, 'id'));
                    $novoDado['id'] = $ids + 1;
                }
                if (empty($novoDado['dataCadastro'])) {
                    $novoDado['dataCadastro'] = date('Y-m-d');
                }
                $pedidos[] = $novoDado;
                if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                    $retorno = ['success' => true, 'id' => $novoDado['id']];
                }
            } elseif ($tipo === 'produto') {
                if (empty($novoDado['id'])) {
                    $ids = empty($produtos) ? 0 : max(array_column($produtos, 'id'));
                    $novoDado['id'] = $ids + 1;
                }
                if (empty($novoDado['dataCadastro'])) {
                    $novoDado['dataCadastro'] = date('Y-m-d');
                }
                $produtos[] = $novoDado;
                if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                    $retorno = ['success' => true, 'id' => $novoDado['id']];
                }
            }
            echo json_encode($retorno);
            exit;
            
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            
            if ($tipo === 'pedido') {
                $pedidos = array_filter($pedidos, function($p) use ($id) {
                    return (int)$p['id'] !== $id;
                });
                $pedidos = array_values($pedidos);
                if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                    $retorno = ['success' => true];
                }
            } elseif ($tipo === 'produto') {
                $produtos = array_filter($produtos, function($p) use ($id) {
                    return (int)$p['id'] !== $id;
                });
                $produtos = array_values($produtos);
                if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                    $retorno = ['success' => true];
                }
            }
            echo json_encode($retorno);
            exit;
            
        case 'carregar':
            if ($tipo === 'todos') {
                echo json_encode([
                    'pedidos' => $pedidos,
                    'produtos' => $produtos,
                    'clientes' => $clientes,
                    'config' => $config
                ]);
            }
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amazon Gest Pro - Gestão de Vendas Premium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_premium.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                <span>AmazonGest</span>
            </div>

            <nav class="nav-menu">
                <a href="#dashboard" class="nav-item active" data-page="dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#pedidos" class="nav-item" data-page="pedidos">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pedidos</span>
                    <span class="badge" id="badge-pedidos">0</span>
                </a>
                <a href="#produtos" class="nav-item" data-page="produtos">
                    <i class="fas fa-box"></i>
                    <span>Produtos</span>
                    <span class="badge" id="badge-produtos">0</span>
                </a>
                <a href="#analises" class="nav-item" data-page="analises">
                    <i class="fas fa-chart-bar"></i>
                    <span>Análises</span>
                </a>
                <a href="#lucro" class="nav-item" data-page="lucro">
                    <i class="fas fa-calculator"></i>
                    <span>Calculadora</span>
                </a>
                <a href="#configuracoes" class="nav-item" data-page="configuracoes">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
            </nav>

            <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.75rem; color: var(--text-muted);">
                <div style="text-align: center;">v5.0 Premium</div>
                <div style="text-align: center; margin-top: 0.5rem;">© 2026</div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- HEADER -->
            <header class="header">
                <h1 class="header-title">
                    <i class="fas fa-chart-line"></i>
                    Amazon Gest Pro
                </h1>
                <div class="header-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Novo Pedido
                    </button>
                </div>
            </header>

            <!-- CONTEÚDO DINÂMICO -->
            <div class="content">
                <!-- DASHBOARD -->
                <div id="page-dashboard" class="page-content active">
                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-icon">💰</div>
                            <div class="kpi-label">Faturamento Total</div>
                            <div class="kpi-value" id="kpi-faturamento">R$ 0,00</div>
                            <div class="kpi-change positive">
                                <i class="fas fa-arrow-up"></i> +0%
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon">💵</div>
                            <div class="kpi-label">Lucro Líquido</div>
                            <div class="kpi-value" id="kpi-lucro">R$ 0,00</div>
                            <div class="kpi-change positive">
                                <i class="fas fa-arrow-up"></i> +0%
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon">📊</div>
                            <div class="kpi-label">Margem Média</div>
                            <div class="kpi-value" id="kpi-margem">0%</div>
                            <div class="kpi-change positive">
                                <i class="fas fa-arrow-up"></i> +0pp
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon">🛍️</div>
                            <div class="kpi-label">Pedidos</div>
                            <div class="kpi-value" id="kpi-pedidos">0</div>
                            <div class="kpi-change positive">
                                <i class="fas fa-arrow-up"></i> +0
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Evolução Mensal</h3>
                            </div>
                            <canvas id="chart-mensal" height="300"></canvas>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Produtos Mais Rentáveis</h3>
                            </div>
                            <canvas id="chart-produtos" height="300"></canvas>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-brain"></i> Insights IA
                            </h3>
                        </div>
                        <div id="ia-insights" class="text-muted">Carregando análises...</div>
                    </div>
                </div>

                <!-- PEDIDOS -->
                <div id="page-pedidos" class="page-content">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Gerenciar Pedidos</h3>
                            <button class="btn btn-primary btn-sm" onclick="app.abrirModalPedido()">
                                <i class="fas fa-plus"></i> Novo Pedido
                            </button>
                        </div>
                        <div id="lista-pedidos" class="table-container">
                            <p class="text-muted text-center p-3">Nenhum pedido cadastrado</p>
                        </div>
                    </div>
                </div>

                <!-- PRODUTOS -->
                <div id="page-produtos" class="page-content">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Gerenciar Produtos</h3>
                            <button class="btn btn-primary btn-sm" onclick="app.abrirModalProduto()">
                                <i class="fas fa-plus"></i> Novo Produto
                            </button>
                        </div>
                        <div id="lista-produtos" class="table-container">
                            <p class="text-muted text-center p-3">Nenhum produto cadastrado</p>
                        </div>
                    </div>
                </div>

                <!-- ANÁLISES -->
                <div id="page-analises" class="page-content">
                    <div class="grid grid-2">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Análise por Categoria</h3>
                            </div>
                            <canvas id="chart-categorias" height="250"></canvas>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Distribuição de Custos</h3>
                            </div>
                            <canvas id="chart-custos" height="250"></canvas>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">Top 10 Produtos</h3>
                        </div>
                        <div id="lista-top-produtos" class="table-container">
                            <p class="text-muted text-center p-3">Nenhum dado disponível</p>
                        </div>
                    </div>
                </div>

                <!-- CALCULADORA DE LUCRO -->
                <div id="page-lucro" class="page-content">
                    <div class="grid grid-2">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Calculadora de Lucro</h3>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Categoria do Produto</label>
                                <select class="form-select" id="calc-categoria" onchange="app.atualizarCalculo()">
                                    <option value="">Selecione uma categoria</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Preço de Custo (R$)</label>
                                <input type="number" class="form-input" id="calc-custo" placeholder="0,00" step="0.01" oninput="app.atualizarCalculo()">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Preço de Venda (R$)</label>
                                <input type="number" class="form-input" id="calc-venda" placeholder="0,00" step="0.01" oninput="app.atualizarCalculo()">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Valor do Frete (R$)</label>
                                <input type="number" class="form-input" id="calc-frete" placeholder="0,00" step="0.01" oninput="app.atualizarCalculo()">
                            </div>

                            <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius); border: 1px solid var(--border-light);">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Taxa Marketplace</div>
                                        <div id="calc-taxa" style="font-size: 1.5rem; font-weight: 700; color: var(--accent-secondary);">-</div>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Lucro Bruto</div>
                                        <div id="calc-lucro-bruto" style="font-size: 1.5rem; font-weight: 700; color: var(--status-success);">-</div>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-card); border-radius: var(--radius); border: 2px solid var(--accent-primary);">
                                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem;">Lucro Líquido</div>
                                <div id="calc-lucro-liquido" style="font-size: 2rem; font-weight: 700; color: var(--accent-primary);">-</div>
                                <div id="calc-margem" style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;">Margem: -</div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Tabela de Taxas por Categoria</h3>
                            </div>
                            <div id="tabela-taxas" style="max-height: 500px; overflow-y: auto;">
                                <p class="text-muted text-center p-3">Carregando categorias...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONFIGURAÇÕES -->
                <div id="page-configuracoes" class="page-content">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Configurações Gerais</h3>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tema da Aplicação</label>
                            <select class="form-select">
                                <option selected>Preto Premium (Padrão)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notificações</label>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" checked>
                                    <span>Ativadas</span>
                                </label>
                            </div>
                        </div>

                        <button class="btn btn-success" onclick="app.exportarDados()">
                            <i class="fas fa-download"></i> Exportar Dados
                        </button>
                        <button class="btn btn-secondary" style="margin-left: 1rem;" onclick="app.limparDados()">
                            <i class="fas fa-trash"></i> Limpar Dados
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL PEDIDO -->
    <div class="modal-overlay" id="modal-pedido">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Novo Pedido</h2>
                <button class="modal-close" onclick="app.fecharModal('modal-pedido')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-input" id="pedido-cliente" placeholder="Nome do cliente">
                </div>
                <div class="form-group">
                    <label class="form-label">Produto</label>
                    <input type="text" class="form-input" id="pedido-produto" placeholder="Nome do produto">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" id="pedido-categoria"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Preço de Custo (R$)</label>
                    <input type="number" class="form-input" id="pedido-custo" placeholder="0,00" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">Preço de Venda (R$)</label>
                    <input type="number" class="form-input" id="pedido-venda" placeholder="0,00" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">Frete (R$)</label>
                    <input type="number" class="form-input" id="pedido-frete" placeholder="0,00" step="0.01">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="app.fecharModal('modal-pedido')">Cancelar</button>
                <button class="btn btn-primary" onclick="app.salvarPedido()">Salvar Pedido</button>
            </div>
        </div>
    </div>

    <!-- MODAL PRODUTO -->
    <div class="modal-overlay" id="modal-produto">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Novo Produto</h2>
                <button class="modal-close" onclick="app.fecharModal('modal-produto')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nome do Produto</label>
                    <input type="text" class="form-input" id="produto-nome" placeholder="Nome">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" id="produto-categoria"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Preço de Custo (R$)</label>
                    <input type="number" class="form-input" id="produto-custo" placeholder="0,00" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">Preço de Venda (R$)</label>
                    <input type="number" class="form-input" id="produto-venda" placeholder="0,00" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" class="form-input" id="produto-sku" placeholder="SKU">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="app.fecharModal('modal-produto')">Cancelar</button>
                <button class="btn btn-primary" onclick="app.salvarProduto()">Salvar Produto</button>
            </div>
        </div>
    </div>

    <script src="js/app_premium.js"></script>
</body>
</html>
