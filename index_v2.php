<?php
// ============================================
// ARQUIVO: index_v2.php
// SISTEMA: Market Manager Pro V2
// VERSÃO: 4.0 - CRUD Melhorado + Design Moderno + Integração Amazon Real
// ============================================

// Configurações
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    if (!$locked) {
        $fp2 = fopen($arquivo, 'c');
        if (!$fp2) { fclose($fp); return false; }
        $fp = $fp2;
    }

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

// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $acao = $input['acao'] ?? '';

    $retorno = ['success' => false];

    switch ($acao) {
        case 'salvar_pedido':
            $pedido = $input['pedido'] ?? [];
            if (empty($pedido['id'])) {
                $ids = empty($pedidos) ? 0 : max(array_column($pedidos, 'id'));
                $pedido['id'] = $ids + 1;
            }
            if (empty($pedido['dataCadastro'])) {
                $pedido['dataCadastro'] = date('Y-m-d H:i:s');
            }

            // Verificar se já existe
            $exists = false;
            foreach ($pedidos as $key => $p) {
                if ($p['id'] == $pedido['id']) {
                    $pedidos[$key] = $pedido;
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $pedidos[] = $pedido;
            }

            if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                $retorno = ['success' => true, 'id' => $pedido['id']];
            }
            break;

        case 'salvar_produto':
            $produto = $input['produto'] ?? [];
            if (empty($produto['id'])) {
                $ids = empty($produtos) ? 0 : max(array_column($produtos, 'id'));
                $produto['id'] = $ids + 1;
            }
            if (empty($produto['dataCadastro'])) {
                $produto['dataCadastro'] = date('Y-m-d H:i:s');
            }

            // Verificar se já existe
            $exists = false;
            foreach ($produtos as $key => $p) {
                if ($p['id'] == $produto['id']) {
                    $produtos[$key] = $produto;
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $produtos[] = $produto;
            }

            if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                $retorno = ['success' => true, 'id' => $produto['id']];
            }
            break;

        case 'excluir_pedido':
            $id = $input['id'] ?? 0;
            foreach ($pedidos as $key => $p) {
                if ($p['id'] == $id) {
                    unset($pedidos[$key]);
                    $pedidos = array_values($pedidos);
                    if (salvarJSON(PEDIDOS_FILE, $pedidos)) {
                        $retorno = ['success' => true];
                    }
                    break;
                }
            }
            break;

        case 'excluir_produto':
            $id = $input['id'] ?? 0;
            foreach ($produtos as $key => $p) {
                if ($p['id'] == $id) {
                    unset($produtos[$key]);
                    $produtos = array_values($produtos);
                    if (salvarJSON(PRODUTOS_FILE, $produtos)) {
                        $retorno = ['success' => true];
                    }
                    break;
                }
            }
            break;

        case 'carregar_dados':
            $retorno = [
                'success' => true,
                'pedidos' => $pedidos,
                'produtos' => $produtos,
                'clientes' => $clientes,
                'config' => $config
            ];
            break;

        case 'amazon_sync':
            require_once __DIR__ . '/includes/amazon_sync.php';
            $amazonConfig = $apiKeys['amazon'] ?? [];
            $amazon = new AmazonAPI($amazonConfig);
            $result = $amazon->syncOrders();
            $retorno = $result;
            break;

        default:
            $retorno = ['success' => false, 'erro' => 'Ação não reconhecida'];
    }

    echo json_encode($retorno);
    exit;
}

// Configuração padrão se não existir
if (empty($config)) {
    $config = [
        'taxaPadrao' => 15,
        'categoriasAmazon' => [
            'eletronicos' => ['nome' => 'Eletrônicos', 'taxa' => 15],
            'livros' => ['nome' => 'Livros', 'taxa' => 10],
            'casa' => ['nome' => 'Casa e Cozinha', 'taxa' => 15],
            'vestuario' => ['nome' => 'Vestuário', 'taxa' => 17],
            'beleza' => ['nome' => 'Beleza', 'taxa' => 15],
            'brinquedos' => ['nome' => 'Brinquedos', 'taxa' => 15],
            'outros' => ['nome' => 'Outros', 'taxa' => 15]
        ]
    ];
    salvarJSON(CONFIG_FILE, $config);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Manager Pro V2 - Sistema de Gestão</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_v2.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-rocket"></i>
                <span>Market Manager Pro</span>
            </div>

            <nav class="nav-menu">
                <a href="#dashboard" class="nav-item active" data-section="dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#pedidos" class="nav-item" data-section="pedidos">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pedidos</span>
                    <span class="badge" id="badge-pedidos">0</span>
                </a>
                <a href="#produtos" class="nav-item" data-section="produtos">
                    <i class="fas fa-box"></i>
                    <span>Produtos</span>
                    <span class="badge" id="badge-produtos">0</span>
                </a>
                <a href="#clientes" class="nav-item" data-section="clientes">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                    <span class="badge" id="badge-clientes">0</span>
                </a>
                <a href="#amazon" class="nav-item" data-section="amazon">
                    <i class="fab fa-amazon"></i>
                    <span>Integração Amazon</span>
                </a>
                <a href="#configuracoes" class="nav-item" data-section="configuracoes">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1 id="page-title">Dashboard</h1>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" id="btn-novo-pedido">
                        <i class="fas fa-plus"></i> Novo Pedido
                    </button>
                    <button class="btn btn-secondary" id="btn-novo-produto">
                        <i class="fas fa-box"></i> Novo Produto
                    </button>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-pedidos">0</div>
                            <div class="stat-label">Pedidos</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-produtos">0</div>
                            <div class="stat-label">Produtos</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-clientes">0</div>
                            <div class="stat-label">Clientes</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-faturamento">R$ 0,00</div>
                            <div class="stat-label">Faturamento</div>
                        </div>
                    </div>
                </div>

                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3>Pedidos Recentes</h3>
                        </div>
                        <div class="card-body">
                            <div id="pedidos-recentes" class="list-container">
                                <!-- Preenchido via JS -->
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3>Produtos Mais Vendidos</h3>
                        </div>
                        <div class="card-body">
                            <div id="produtos-top" class="list-container">
                                <!-- Preenchido via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pedidos Section -->
            <section id="pedidos" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3>Gerenciar Pedidos</h3>
                        <div class="card-actions">
                            <input type="text" id="busca-pedidos" class="search-input" placeholder="Buscar pedidos...">
                            <select id="filtro-status" class="filter-select">
                                <option value="">Todos os Status</option>
                                <option value="pendente">Pendente</option>
                                <option value="processando">Processando</option>
                                <option value="transito">Em Trânsito</option>
                                <option value="entregue">Entregue</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="pedidos-list" class="table-container">
                            <!-- Preenchido via JS -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- Produtos Section -->
            <section id="produtos" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3>Catálogo de Produtos</h3>
                        <div class="card-actions">
                            <input type="text" id="busca-produtos" class="search-input" placeholder="Buscar produtos...">
                            <select id="filtro-categoria" class="filter-select">
                                <option value="">Todas as Categorias</option>
                                <option value="eletronicos">Eletrônicos</option>
                                <option value="livros">Livros</option>
                                <option value="casa">Casa e Cozinha</option>
                                <option value="vestuario">Vestuário</option>
                                <option value="beleza">Beleza</option>
                                <option value="brinquedos">Brinquedos</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="produtos-list" class="table-container">
                            <!-- Preenchido via JS -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- Clientes Section -->
            <section id="clientes" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3>Base de Clientes</h3>
                    </div>
                    <div class="card-body">
                        <div id="clientes-list" class="table-container">
                            <!-- Preenchido via JS -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- Amazon Integration Section -->
            <section id="amazon" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3>Integração Amazon</h3>
                    </div>
                    <div class="card-body">
                        <div class="amazon-config">
                            <h4>Configurar Credenciais Amazon SP-API</h4>
                            <form id="amazon-config-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>AWS Access Key ID</label>
                                        <input type="text" id="aws-access-key" class="form-control" placeholder="AKIA...">
                                    </div>
                                    <div class="form-group">
                                        <label>AWS Secret Access Key</label>
                                        <input type="password" id="aws-secret-key" class="form-control" placeholder="Secret key...">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>LWA Client ID</label>
                                        <input type="text" id="lwa-client-id" class="form-control" placeholder="amzn1...">
                                    </div>
                                    <div class="form-group">
                                        <label>LWA Client Secret</label>
                                        <input type="password" id="lwa-client-secret" class="form-control" placeholder="Client secret...">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>LWA Refresh Token</label>
                                        <input type="password" id="lwa-refresh-token" class="form-control" placeholder="Atzr...">
                                    </div>
                                    <div class="form-group">
                                        <label>Marketplace</label>
                                        <select id="amazon-marketplace" class="form-control">
                                            <option value="BR">Brasil</option>
                                            <option value="US">Estados Unidos</option>
                                            <option value="MX">México</option>
                                            <option value="CA">Canadá</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" id="btn-testar-amazon">
                                        <i class="fas fa-plug"></i> Testar Conexão
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Configurações
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="amazon-sync">
                            <h4>Sincronizar Pedidos</h4>
                            <p>Importe pedidos da Amazon automaticamente</p>
                            <div class="form-actions">
                                <button type="button" class="btn btn-success" id="btn-sync-amazon">
                                    <i class="fas fa-sync"></i> Sincronizar Agora
                                </button>
                            </div>
                            <div id="amazon-sync-status" class="sync-status"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Configurações Section -->
            <section id="configuracoes" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3>Configurações do Sistema</h3>
                    </div>
                    <div class="card-body">
                        <form id="config-form">
                            <div class="form-group">
                                <label>Taxa Padrão (%)</label>
                                <input type="number" id="taxa-padrao" class="form-control" value="<?php echo $config['taxaPadrao'] ?? 15; ?>" step="0.1">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Salvar Configurações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Novo Pedido -->
    <div class="modal" id="modal-novo-pedido">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Novo Pedido</h2>
                <button class="modal-close" data-close="modal-novo-pedido">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-novo-pedido">
                <input type="hidden" id="pedido-id" name="id">

                <div class="form-section">
                    <h3>Dados do Cliente</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome *</label>
                            <input type="text" id="pedido-cliente-nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="pedido-cliente-email" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="tel" id="pedido-cliente-telefone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>CPF/CNPJ</label>
                            <input type="text" id="pedido-cliente-cpf" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Endereço de Entrega</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>CEP</label>
                            <input type="text" id="pedido-cep" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Rua</label>
                            <input type="text" id="pedido-rua" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Número</label>
                            <input type="text" id="pedido-numero" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Complemento</label>
                            <input type="text" id="pedido-complemento" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bairro</label>
                            <input type="text" id="pedido-bairro" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Cidade</label>
                            <input type="text" id="pedido-cidade" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Estado</label>
                            <input type="text" id="pedido-estado" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Dados do Produto</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Produto *</label>
                            <input type="text" id="pedido-produto-nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Categoria</label>
                            <select id="pedido-produto-categoria" class="form-control">
                                <option value="outros">Outros</option>
                                <option value="eletronicos">Eletrônicos</option>
                                <option value="livros">Livros</option>
                                <option value="casa">Casa e Cozinha</option>
                                <option value="vestuario">Vestuário</option>
                                <option value="beleza">Beleza</option>
                                <option value="brinquedos">Brinquedos</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Preço Custo (R$) *</label>
                            <input type="number" id="pedido-preco-custo" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Preço Venda (R$) *</label>
                            <input type="number" id="pedido-preco-venda" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantidade</label>
                            <input type="number" id="pedido-quantidade" class="form-control" value="1" min="1">
                        </div>
                        <div class="form-group">
                            <label>Frete (R$)</label>
                            <input type="number" id="pedido-frete" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Rastreio</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Código de Rastreio</label>
                            <input type="text" id="pedido-codigo-rastreio" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="pedido-status" class="form-control">
                                <option value="pendente">Pendente</option>
                                <option value="processando">Processando</option>
                                <option value="transito">Em Trânsito</option>
                                <option value="entregue">Entregue</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Resumo do Pedido</h3>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="summary-subtotal">R$ 0,00</span>
                        </div>
                        <div class="summary-row">
                            <span>Taxa Marketplace:</span>
                            <span id="summary-taxa">R$ 0,00</span>
                        </div>
                        <div class="summary-row">
                            <span>Frete:</span>
                            <span id="summary-frete">R$ 0,00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="summary-total">R$ 0,00</span>
                        </div>
                        <div class="summary-row profit">
                            <span>Lucro Líquido:</span>
                            <span id="summary-lucro">R$ 0,00</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" data-close="modal-novo-pedido">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Pedido</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Novo Produto -->
    <div class="modal" id="modal-novo-produto">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Novo Produto</h2>
                <button class="modal-close" data-close="modal-novo-produto">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-novo-produto">
                <input type="hidden" id="produto-id" name="id">

                <div class="form-group">
                    <label>Nome do Produto *</label>
                    <input type="text" id="produto-nome" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Categoria</label>
                        <select id="produto-categoria" class="form-control">
                            <option value="outros">Outros</option>
                            <option value="eletronicos">Eletrônicos</option>
                            <option value="livros">Livros</option>
                            <option value="casa">Casa e Cozinha</option>
                            <option value="vestuario">Vestuário</option>
                            <option value="beleza">Beleza</option>
                            <option value="brinquedos">Brinquedos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estoque</label>
                        <input type="number" id="produto-estoque" class="form-control" value="0" min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Preço Custo (R$) *</label>
                        <input type="number" id="produto-preco-custo" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Preço Venda (R$) *</label>
                        <input type="number" id="produto-preco-venda" class="form-control" step="0.01" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Frete (R$)</label>
                        <input type="number" id="produto-frete" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Embalagem (R$)</label>
                        <input type="number" id="produto-embalagem" class="form-control" step="0.01" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição</label>
                    <textarea id="produto-descricao" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Link do Produto</label>
                    <input type="url" id="produto-link" class="form-control">
                </div>

                <div class="form-group">
                    <label>ASIN (Amazon)</label>
                    <input type="text" id="produto-asin" class="form-control">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" data-close="modal-novo-produto">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container" id="toast-container"></div>

    <script src="js/app_v2.js"></script>
</body>
</html>