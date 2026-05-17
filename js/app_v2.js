// ============================================
// Market Manager Pro V2 - JavaScript
// ============================================

class MarketManagerV2 {
    constructor() {
        this.pedidos = [];
        this.produtos = [];
        this.clientes = [];
        this.config = {};
        this.apiKeys = {};

        this.init();
    }

    async init() {
        await this.carregarDados();
        this.configurarEventos();
        this.renderizarDashboard();
        this.renderizarPedidos();
        this.renderizarProdutos();
        this.renderizarClientes();
        this.atualizarBadges();
    }

    // ============================================
    // API - Comunicação com Servidor
    // ============================================

    async apiRequest(acao, dados = {}) {
        try {
            const response = await fetch('api/crud_v2.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ acao, ...dados })
            });

            const resultado = await response.json();
            return resultado;
        } catch (erro) {
            console.error('Erro na requisição API:', erro);
            this.mostrarToast('Erro de comunicação com o servidor', 'error');
            return { success: false, erro: erro.message };
        }
    }

    // ============================================
    // Carregar Dados
    // ============================================

    async carregarDados() {
        const resultado = await this.apiRequest('carregar_dados');
        if (resultado.success) {
            this.pedidos = resultado.pedidos || [];
            this.produtos = resultado.produtos || [];
            this.clientes = resultado.clientes || [];
            this.config = resultado.config || {};
            this.apiKeys = resultado.apiKeys || {};
        }
    }

    // ============================================
    // Pedidos
    // ============================================

    async salvarPedido(pedido) {
        const resultado = await this.apiRequest('salvar_pedido', { pedido });
        if (resultado.success) {
            this.mostrarToast('Pedido salvo com sucesso!', 'success');
            await this.carregarDados();
            this.renderizarPedidos();
            this.renderizarDashboard();
            this.atualizarBadges();
            return resultado.id;
        } else {
            this.mostrarToast('Erro ao salvar pedido', 'error');
            return null;
        }
    }

    async excluirPedido(id) {
        if (!confirm('Tem certeza que deseja excluir este pedido?')) {
            return;
        }

        const resultado = await this.apiRequest('excluir_pedido', { id });
        if (resultado.success) {
            this.mostrarToast('Pedido excluído com sucesso!', 'success');
            await this.carregarDados();
            this.renderizarPedidos();
            this.renderizarDashboard();
            this.atualizarBadges();
        } else {
            this.mostrarToast('Erro ao excluir pedido', 'error');
        }
    }

    // ============================================
    // Produtos
    // ============================================

    async salvarProduto(produto) {
        const resultado = await this.apiRequest('salvar_produto', { produto });
        if (resultado.success) {
            this.mostrarToast('Produto salvo com sucesso!', 'success');
            await this.carregarDados();
            this.renderizarProdutos();
            this.renderizarDashboard();
            this.atualizarBadges();
            return resultado.id;
        } else {
            this.mostrarToast('Erro ao salvar produto', 'error');
            return null;
        }
    }

    async excluirProduto(id) {
        if (!confirm('Tem certeza que deseja excluir este produto?')) {
            return;
        }

        const resultado = await this.apiRequest('excluir_produto', { id });
        if (resultado.success) {
            this.mostrarToast('Produto excluído com sucesso!', 'success');
            await this.carregarDados();
            this.renderizarProdutos();
            this.renderizarDashboard();
            this.atualizarBadges();
        } else {
            this.mostrarToast('Erro ao excluir produto', 'error');
        }
    }

    // ============================================
    // Renderização
    // ============================================

    renderizarDashboard() {
        // Stats
        document.getElementById('stat-pedidos').textContent = this.pedidos.length;
        document.getElementById('stat-produtos').textContent = this.produtos.length;
        document.getElementById('stat-clientes').textContent = this.clientes.length;

        // Faturamento
        const faturamento = this.pedidos.reduce((total, p) => {
            return total + (p.precoVenda || 0) * (p.quantidade || 1);
        }, 0);
        document.getElementById('stat-faturamento').textContent = this.formatarMoeda(faturamento);

        // Pedidos Recentes
        const pedidosRecentes = this.pedidos
            .sort((a, b) => new Date(b.dataCadastro) - new Date(a.dataCadastro))
            .slice(0, 5);

        const containerPedidos = document.getElementById('pedidos-recentes');
        if (pedidosRecentes.length === 0) {
            containerPedidos.innerHTML = this.renderizarEmptyState('Nenhum pedido ainda');
        } else {
            containerPedidos.innerHTML = pedidosRecentes.map(p => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${p.cliente?.nome || 'Cliente não informado'}</div>
                        <div class="list-item-subtitle">${p.produto?.nome || 'Produto não informado'}</div>
                    </div>
                    <div class="list-item-value">${this.formatarMoeda(p.precoVenda || 0)}</div>
                </div>
            `).join('');
        }

        // Produtos Top
        const produtosTop = this.produtos
            .map(p => ({
                ...p,
                vendas: this.pedidos.filter(pedido => pedido.produto?.nome === p.nome).length
            }))
            .sort((a, b) => b.vendas - a.vendas)
            .slice(0, 5);

        const containerProdutos = document.getElementById('produtos-top');
        if (produtosTop.length === 0) {
            containerProdutos.innerHTML = this.renderizarEmptyState('Nenhum produto vendido');
        } else {
            containerProdutos.innerHTML = produtosTop.map(p => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${p.nome}</div>
                        <div class="list-item-subtitle">${p.vendas} vendas</div>
                    </div>
                    <div class="list-item-value">${this.formatarMoeda(p.precoVenda || 0)}</div>
                </div>
            `).join('');
        }
    }

    renderizarPedidos() {
        const container = document.getElementById('pedidos-list');
        const busca = document.getElementById('busca-pedidos')?.value?.toLowerCase() || '';
        const filtroStatus = document.getElementById('filtro-status')?.value || '';

        let pedidosFiltrados = this.pedidos;

        // Aplicar busca
        if (busca) {
            pedidosFiltrados = pedidosFiltrados.filter(p =>
                (p.cliente?.nome || '').toLowerCase().includes(busca) ||
                (p.produto?.nome || '').toLowerCase().includes(busca) ||
                (p.codigoRastreio || '').toLowerCase().includes(busca)
            );
        }

        // Aplicar filtro de status
        if (filtroStatus) {
            pedidosFiltrados = pedidosFiltrados.filter(p => p.status === filtroStatus);
        }

        if (pedidosFiltrados.length === 0) {
            container.innerHTML = this.renderizarEmptyState('Nenhum pedido encontrado');
            return;
        }

        container.innerHTML = `
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ${pedidosFiltrados.map(p => `
                        <tr>
                            <td>#${p.id}</td>
                            <td>${p.cliente?.nome || '-'}</td>
                            <td>${p.produto?.nome || '-'}</td>
                            <td>${this.formatarMoeda(p.precoVenda || 0)}</td>
                            <td><span class="status-badge ${p.status || 'pendente'}">${this.formatarStatus(p.status)}</span></td>
                            <td>${this.formatarData(p.dataCadastro)}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="app.editarPedido(${p.id})" title="Editar pedido">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="app.excluirPedido(${p.id})" title="Excluir pedido">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    renderizarProdutos() {
        const container = document.getElementById('produtos-list');
        const busca = document.getElementById('busca-produtos')?.value?.toLowerCase() || '';
        const filtroCategoria = document.getElementById('filtro-categoria')?.value || '';

        let produtosFiltrados = this.produtos;

        // Aplicar busca
        if (busca) {
            produtosFiltrados = produtosFiltrados.filter(p =>
                p.nome.toLowerCase().includes(busca) ||
                (p.descricao || '').toLowerCase().includes(busca)
            );
        }

        // Aplicar filtro de categoria
        if (filtroCategoria) {
            produtosFiltrados = produtosFiltrados.filter(p => p.categoria === filtroCategoria);
        }

        if (produtosFiltrados.length === 0) {
            container.innerHTML = this.renderizarEmptyState('Nenhum produto encontrado');
            return;
        }

        container.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Custo</th>
                        <th>Venda</th>
                        <th>Lucro</th>
                        <th>Estoque</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ${produtosFiltrados.map(p => {
                        const lucro = (p.precoVenda || 0) - (p.precoCusto || 0);
                        const taxa = (p.precoVenda || 0) * (this.obterTaxaCategoria(p.categoria) / 100);
                        const lucroLiquido = lucro - taxa;
                        return `
                            <tr>
                                <td>#${p.id}</td>
                                <td>${p.nome}</td>
                                <td>${this.formatarCategoria(p.categoria)}</td>
                                <td>${this.formatarMoeda(p.precoCusto || 0)}</td>
                                <td>${this.formatarMoeda(p.precoVenda || 0)}</td>
                                <td style="color: ${lucroLiquido >= 0 ? 'var(--success-color)' : 'var(--danger-color)'}">${this.formatarMoeda(lucroLiquido)}</td>
                                <td>${p.estoque || 0}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="app.editarProduto(${p.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="app.excluirProduto(${p.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
    }

    renderizarClientes() {
        const container = document.getElementById('clientes-list');

        if (this.clientes.length === 0) {
            container.innerHTML = this.renderizarEmptyState('Nenhum cliente cadastrado');
            return;
        }

        container.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>CPF/CNPJ</th>
                        <th>Pedidos</th>
                    </tr>
                </thead>
                <tbody>
                    ${this.clientes.map(c => {
                        const pedidosCliente = this.pedidos.filter(p => p.cliente?.id === c.id).length;
                        return `
                            <tr>
                                <td>#${c.id}</td>
                                <td>${c.nome}</td>
                                <td>${c.email || '-'}</td>
                                <td>${c.telefone || '-'}</td>
                                <td>${c.cpfCnpj || '-'}</td>
                                <td>${pedidosCliente}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
    }

    atualizarBadges() {
        document.getElementById('badge-pedidos').textContent = this.pedidos.length;
        document.getElementById('badge-produtos').textContent = this.produtos.length;
        document.getElementById('badge-clientes').textContent = this.clientes.length;
    }

    // ============================================
    // Modais
    // ============================================

    abrirModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    }

    fecharModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    // ============================================
    // Formulários
    // ============================================

    prepararFormularioPedido(pedido = null) {
        const form = document.getElementById('form-novo-pedido');
        form.reset();

        if (pedido) {
            document.getElementById('pedido-id').value = pedido.id;
            document.getElementById('pedido-cliente-nome').value = pedido.cliente?.nome || '';
            document.getElementById('pedido-cliente-email').value = pedido.cliente?.email || '';
            document.getElementById('pedido-cliente-telefone').value = pedido.cliente?.telefone || '';
            document.getElementById('pedido-cliente-cpf').value = pedido.cliente?.cpfCnpj || '';
            document.getElementById('pedido-cep').value = pedido.endereco?.cep || '';
            document.getElementById('pedido-rua').value = pedido.endereco?.rua || '';
            document.getElementById('pedido-numero').value = pedido.endereco?.numero || '';
            document.getElementById('pedido-complemento').value = pedido.endereco?.complemento || '';
            document.getElementById('pedido-bairro').value = pedido.endereco?.bairro || '';
            document.getElementById('pedido-cidade').value = pedido.endereco?.cidade || '';
            document.getElementById('pedido-estado').value = pedido.endereco?.estado || '';
            document.getElementById('pedido-produto-nome').value = pedido.produto?.nome || '';
            document.getElementById('pedido-produto-categoria').value = pedido.produto?.categoria || 'outros';
            document.getElementById('pedido-preco-custo').value = pedido.produto?.precoCusto || '';
            document.getElementById('pedido-preco-venda').value = pedido.produto?.precoVenda || '';
            document.getElementById('pedido-quantidade').value = pedido.quantidade || 1;
            document.getElementById('pedido-frete').value = pedido.frete || 0;
            document.getElementById('pedido-codigo-rastreio').value = pedido.codigoRastreio || '';
            document.getElementById('pedido-status').value = pedido.status || 'pendente';
        } else {
            document.getElementById('pedido-id').value = '';
        }

        this.calcularResumoPedido();
    }

    prepararFormularioProduto(produto = null) {
        const form = document.getElementById('form-novo-produto');
        form.reset();

        if (produto) {
            document.getElementById('produto-id').value = produto.id;
            document.getElementById('produto-nome').value = produto.nome || '';
            document.getElementById('produto-categoria').value = produto.categoria || 'outros';
            document.getElementById('produto-estoque').value = produto.estoque || 0;
            document.getElementById('produto-preco-custo').value = produto.precoCusto || '';
            document.getElementById('produto-preco-venda').value = produto.precoVenda || '';
            document.getElementById('produto-frete').value = produto.frete || 0;
            document.getElementById('produto-embalagem').value = produto.embalagem || 0;
            document.getElementById('produto-descricao').value = produto.descricao || '';
            document.getElementById('produto-link').value = produto.link || '';
            document.getElementById('produto-asin').value = produto.asin || '';
        } else {
            document.getElementById('produto-id').value = '';
        }
    }

    calcularResumoPedido() {
        const precoCusto = parseFloat(document.getElementById('pedido-preco-custo').value) || 0;
        const precoVenda = parseFloat(document.getElementById('pedido-preco-venda').value) || 0;
        const quantidade = parseInt(document.getElementById('pedido-quantidade').value) || 1;
        const frete = parseFloat(document.getElementById('pedido-frete').value) || 0;
        const categoria = document.getElementById('pedido-produto-categoria').value;

        const subtotal = precoVenda * quantidade;
        const taxa = subtotal * (this.obterTaxaCategoria(categoria) / 100);
        const total = subtotal + frete;
        const lucro = subtotal - (precoCusto * quantidade) - taxa - frete;

        document.getElementById('summary-subtotal').textContent = this.formatarMoeda(subtotal);
        document.getElementById('summary-taxa').textContent = this.formatarMoeda(taxa);
        document.getElementById('summary-frete').textContent = this.formatarMoeda(frete);
        document.getElementById('summary-total').textContent = this.formatarMoeda(total);
        document.getElementById('summary-lucro').textContent = this.formatarMoeda(lucro);
    }

    // ============================================
    // Eventos
    // ============================================

    configurarEventos() {
        // Navegação
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const section = item.dataset.section;
                this.navegarPara(section);
            });
        });

        // Botões principais
        document.getElementById('btn-novo-pedido')?.addEventListener('click', () => {
            this.prepararFormularioPedido();
            this.abrirModal('modal-novo-pedido');
        });

        document.getElementById('btn-novo-produto')?.addEventListener('click', () => {
            this.prepararFormularioProduto();
            this.abrirModal('modal-novo-produto');
        });

        // Fechar modais
        document.querySelectorAll('.modal-close, [data-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.close || btn.closest('.modal').id;
                this.fecharModal(modalId);
            });
        });

        // Clicar fora do modal para fechar
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.fecharModal(modal.id);
                }
            });
        });

        // Formulário de pedido
        document.getElementById('form-novo-pedido')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submeterFormularioPedido();
        });

        // Formulário de produto
        document.getElementById('form-novo-produto')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submeterFormularioProduto();
        });

        // Cálculo automático do pedido
        const camposCalculoPedido = [
            'pedido-preco-custo',
            'pedido-preco-venda',
            'pedido-quantidade',
            'pedido-frete',
            'pedido-produto-categoria'
        ];

        camposCalculoPedido.forEach(campo => {
            document.getElementById(campo)?.addEventListener('input', () => {
                this.calcularResumoPedido();
            });
        });

        // Filtros
        document.getElementById('busca-pedidos')?.addEventListener('input', () => {
            this.renderizarPedidos();
        });

        document.getElementById('filtro-status')?.addEventListener('change', () => {
            this.renderizarPedidos();
        });

        document.getElementById('busca-produtos')?.addEventListener('input', () => {
            this.renderizarProdutos();
        });

        document.getElementById('filtro-categoria')?.addEventListener('change', () => {
            this.renderizarProdutos();
        });

        // Amazon
        document.getElementById('btn-testar-amazon')?.addEventListener('click', () => {
            this.testarConexaoAmazon();
        });

        document.getElementById('btn-sync-amazon')?.addEventListener('click', () => {
            this.sincronizarAmazon();
        });

        document.getElementById('amazon-config-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.salvarConfigAmazon();
        });
    }

    // ============================================
    // Submissão de Formulários
    // ============================================

    async submeterFormularioPedido() {
        const id = document.getElementById('pedido-id').value;
        const pedido = {
            id: id ? parseInt(id) : null,
            cliente: {
                nome: document.getElementById('pedido-cliente-nome').value,
                email: document.getElementById('pedido-cliente-email').value,
                telefone: document.getElementById('pedido-cliente-telefone').value,
                cpfCnpj: document.getElementById('pedido-cliente-cpf').value
            },
            endereco: {
                cep: document.getElementById('pedido-cep').value,
                rua: document.getElementById('pedido-rua').value,
                numero: document.getElementById('pedido-numero').value,
                complemento: document.getElementById('pedido-complemento').value,
                bairro: document.getElementById('pedido-bairro').value,
                cidade: document.getElementById('pedido-cidade').value,
                estado: document.getElementById('pedido-estado').value
            },
            produto: {
                nome: document.getElementById('pedido-produto-nome').value,
                categoria: document.getElementById('pedido-produto-categoria').value,
                precoCusto: parseFloat(document.getElementById('pedido-preco-custo').value) || 0,
                precoVenda: parseFloat(document.getElementById('pedido-preco-venda').value) || 0
            },
            quantidade: parseInt(document.getElementById('pedido-quantidade').value) || 1,
            frete: parseFloat(document.getElementById('pedido-frete').value) || 0,
            codigoRastreio: document.getElementById('pedido-codigo-rastreio').value,
            status: document.getElementById('pedido-status').value
        };

        const resultado = await this.salvarPedido(pedido);
        if (resultado) {
            this.fecharModal('modal-novo-pedido');
        }
    }

    async submeterFormularioProduto() {
        const id = document.getElementById('produto-id').value;
        const produto = {
            id: id ? parseInt(id) : null,
            nome: document.getElementById('produto-nome').value,
            categoria: document.getElementById('produto-categoria').value,
            estoque: parseInt(document.getElementById('produto-estoque').value) || 0,
            precoCusto: parseFloat(document.getElementById('produto-preco-custo').value) || 0,
            precoVenda: parseFloat(document.getElementById('produto-preco-venda').value) || 0,
            frete: parseFloat(document.getElementById('produto-frete').value) || 0,
            embalagem: parseFloat(document.getElementById('produto-embalagem').value) || 0,
            descricao: document.getElementById('produto-descricao').value,
            link: document.getElementById('produto-link').value,
            asin: document.getElementById('produto-asin').value
        };

        const resultado = await this.salvarProduto(produto);
        if (resultado) {
            this.fecharModal('modal-novo-produto');
        }
    }

    // ============================================
    // Edição
    // ============================================

    editarPedido(id) {
        const pedido = this.pedidos.find(p => p.id === id);
        if (pedido) {
            this.prepararFormularioPedido(pedido);
            this.abrirModal('modal-novo-pedido');
        }
    }

    editarProduto(id) {
        const produto = this.produtos.find(p => p.id === id);
        if (produto) {
            this.prepararFormularioProduto(produto);
            this.abrirModal('modal-novo-produto');
        }
    }

    // ============================================
    // Navegação
    // ============================================

    navegarPara(section) {
        // Atualizar menu
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.section === section) {
                item.classList.add('active');
            }
        });

        // Atualizar seções
        document.querySelectorAll('.section').forEach(sec => {
            sec.classList.remove('active');
        });

        const targetSection = document.getElementById(section);
        if (targetSection) {
            targetSection.classList.add('active');
        }

        // Atualizar título
        const titles = {
            dashboard: 'Dashboard',
            pedidos: 'Pedidos',
            produtos: 'Produtos',
            clientes: 'Clientes',
            amazon: 'Integração Amazon',
            configuracoes: 'Configurações'
        };

        document.getElementById('page-title').textContent = titles[section] || 'Dashboard';
    }

    // ============================================
    // Amazon Integration
    // ============================================

    async testarConexaoAmazon() {
        const status = document.getElementById('amazon-sync-status');
        status.className = 'sync-status loading';
        status.textContent = 'Testando conexão...';

        const resultado = await this.apiRequest('amazon_test');

        if (resultado.success) {
            status.className = 'sync-status success';
            status.textContent = '✅ ' + (resultado.mensagem || 'Conexão estabelecida com sucesso!');
        } else {
            status.className = 'sync-status error';
            status.textContent = '❌ ' + (resultado.erro || 'Erro ao conectar com Amazon');
        }
    }

    async sincronizarAmazon() {
        const status = document.getElementById('amazon-sync-status');
        status.className = 'sync-status loading';
        status.textContent = 'Sincronizando pedidos...';

        try {
            const resultado = await this.apiRequest('amazon_sync');
            if (resultado.success) {
                status.className = 'sync-status success';
                status.textContent = `✅ ${resultado.mensagem || 'Sincronização concluída!'}`;
                await this.carregarDados();
                this.renderizarPedidos();
                this.renderizarDashboard();
            } else {
                status.className = 'sync-status error';
                status.textContent = `❌ ${resultado.erro || 'Erro na sincronização'}`;
            }
        } catch (erro) {
            status.className = 'sync-status error';
            status.textContent = '❌ Erro ao sincronizar com Amazon';
        }
    }

    async salvarConfigAmazon() {
        const config = {
            aws_access_key_id: document.getElementById('aws-access-key').value,
            aws_secret_access_key: document.getElementById('aws-secret-key').value,
            lwa_client_id: document.getElementById('lwa-client-id').value,
            lwa_client_secret: document.getElementById('lwa-client-secret').value,
            lwa_refresh_token: document.getElementById('lwa-refresh-token').value,
            marketplace: document.getElementById('amazon-marketplace').value
        };

        const resultado = await this.apiRequest('salvar_config_amazon', { config });

        if (resultado.success) {
            this.mostrarToast('Configurações da Amazon salvas!', 'success');
        } else {
            this.mostrarToast('Erro ao salvar configurações', 'error');
        }
    }

    // ============================================
    // Utilitários
    // ============================================

    formatarMoeda(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    formatarData(data) {
        if (!data) return '-';
        const d = new Date(data);
        return d.toLocaleDateString('pt-BR');
    }

    formatarStatus(status) {
        const statusMap = {
            pendente: 'Pendente',
            processando: 'Processando',
            transito: 'Em Trânsito',
            entregue: 'Entregue'
        };
        return statusMap[status] || status;
    }

    formatarCategoria(categoria) {
        const categoriaMap = {
            eletronicos: 'Eletrônicos',
            livros: 'Livros',
            casa: 'Casa e Cozinha',
            vestuario: 'Vestuário',
            beleza: 'Beleza',
            brinquedos: 'Brinquedos',
            outros: 'Outros'
        };
        return categoriaMap[categoria] || categoria;
    }

    obterTaxaCategoria(categoria) {
        if (this.config.categoriasAmazon && this.config.categoriasAmazon[categoria]) {
            const cat = this.config.categoriasAmazon[categoria];
            return typeof cat === 'object' ? cat.taxa : cat;
        }
        return this.config.taxaPadrao || 15;
    }

    renderizarEmptyState(mensagem) {
        return `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>${mensagem}</h3>
            </div>
        `;
    }

    mostrarToast(mensagem, tipo = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${tipo}`;

        const icones = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <i class="fas ${icones[tipo]} toast-icon"></i>
            <span class="toast-message">${mensagem}</span>
            <button class="toast-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(toast);

        // Remover após 5 segundos
        setTimeout(() => {
            toast.style.animation = 'toastSlideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 5000);

        // Fechar ao clicar no botão
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });
    }
}

// Inicializar aplicação
const app = new MarketManagerV2();

// Expor funções globalmente para uso nos eventos onclick
window.app = app;