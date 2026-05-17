/**
 * Amazon Sync UI - Integracao com UI existente
 * Adiciona botoes de sincronizacao Amazon e polling automatico
 */
(function() {
    function injectAmazonUI() {
        if (!window.marketManager) {
            setTimeout(injectAmazonUI, 500);
            return;
        }

        const mm = window.marketManager;

        // 1) Adicionar botao de sync Amazon na aba Pedidos
        const pedidosDiv = document.getElementById('pedidos');
        if (pedidosDiv) {
            const existingBtn = document.getElementById('btn-amazon-sync');
            if (!existingBtn) {
                const actionsDiv = pedidosDiv.querySelector('div[style*="justify-content: space-between"]');
                if (actionsDiv) {
                    const btn = document.createElement('button');
                    btn.id = 'btn-amazon-sync';
                    btn.className = 'btn btn-warning';
                    btn.innerHTML = '<i class="fab fa-amazon"></i> Sync Amazon';
                    btn.title = 'Sincronizar pedidos da Amazon via API';
                    btn.addEventListener('click', () => syncAmazonPedidos());
                    actionsDiv.appendChild(btn);
                }
            }
        }

        // 2) Adicionar botao de sync Amazon na aba Produtos
        const produtosDiv = document.getElementById('produtos');
        if (produtosDiv) {
            const existingBtn2 = document.getElementById('btn-amazon-products-sync');
            if (!existingBtn2) {
                const actionsDiv = produtosDiv.querySelector('div[style*="justify-content: space-between"]');
                if (actionsDiv) {
                    const btn = document.createElement('button');
                    btn.id = 'btn-amazon-products-sync';
                    btn.className = 'btn btn-warning';
                    btn.innerHTML = '<i class="fab fa-amazon"></i> Importar Amazon';
                    btn.title = 'Importar produtos da Amazon via API';
                    btn.addEventListener('click', () => syncAmazonPedidos());
                    actionsDiv.appendChild(btn);
                }
            }
        }

        // 3) Seccion de Amazon na aba Integracoes
        injectAmazonConfigSection(mm);

        // 4) Polling automatico a cada 60s
        startAmazonPolling();
    }

    async function syncAmazonPedidos() {
        if (!window.marketManager) return;
        const mm = window.marketManager;

        mm.mostrarNotificacao('Conectando a Amazon API...', 'info');

        try {
            const response = await fetch('api/crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'amazon-sync', tipo: 'pedido', dados: JSON.stringify({ sync_type: 'orders' }) })
            });
            const result = await response.json();

            if (result.success) {
                mm.mostrarNotificacao(
                    `Amazon Sync: ${result.importados || 0} pedidos importados (${result.total_encontrados || 0} encontrados)`,
                    'success'
                );
                await mm.carregarDadosServidor();
                mm.atualizarDashboard();
                mm.carregarPedidos('todos');
                mm.carregarProdutos();
            } else if (result.setup_needed) {
                mm.mostrarNotificacao(
                    'Configure suas credenciais Amazon na aba Integracoes',
                    'warning'
                );
            } else {
                mm.mostrarNotificacao(`Erro no sync: ${result.message || 'Erro desconhecido'}`, 'danger');
            }
        } catch (e) {
            console.error('Erro no sync Amazon:', e);
            mm.mostrarNotificacao('Erro ao conectar com Amazon API', 'danger');
        }
    }

    function injectAmazonConfigSection(mm) {
        // Verificar se ja existe
        if (document.getElementById('amazon-config-section')) return;

        // Encontrar a secao de configuracoes e adicionar
        const integracaoTab = document.getElementById('integracao');
        if (!integracaoTab) return;

        const sectionHtml = `
        <div id="amazon-config-section" style="margin-top: 30px; background: linear-gradient(135deg, rgba(255, 153, 0, 0.1), rgba(0, 0, 0, 0.1)); padding: 30px; border-radius: var(--radius); border-left: 4px solid #ff9900;">
            <h3 style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; color: var(--text-light);">
                <i class="fab fa-amazon" style="color: #ff9900; font-size: 28px;"></i>
                Amazon SP-API - Integracao
            </h3>

            <div style="background: rgba(0, 0, 0, 0.2); padding: 20px; border-radius: var(--radius-small); margin-bottom: 20px;">
                <h4 style="color: var(--warning-color); margin-bottom: 12px;">Configuracao - Amazon Selling Partner API</h4>
                <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 14px; line-height: 1.6;">
                    Para sincronizar pedidos automaticamente com a Amazon, voce precisa criar credenciais no Amazon Seller Central.
                    <br><br>
                    <strong style="color: var(--text-light);">Passos:</strong>
                    <br>1. Acesse <a href="https://developer.amazonservices.com/" target="_blank" style="color: #ff9900;">developer.amazonservices.com</a>
                    <br>2. Crie uma aplicacao e obtenha suas credenciais
                    <br>3. Preencha os campos abaixo
                    <br>4. Clique em "Testar Conexao" para validar
                </p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">AWS Access Key ID</label>
                    <input type="password" id="amz-access-key" class="form-control" placeholder="AKIA...">
                </div>
                <div class="form-group">
                    <label class="form-label">AWS Secret Access Key</label>
                    <input type="password" id="amz-secret-key" class="form-control" placeholder="...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">LWA Client ID</label>
                    <input type="text" id="amz-lwa-client-id" class="form-control" placeholder="amzn1.application-oa2-client...">
                </div>
                <div class="form-group">
                    <label class="form-label">LWA Client Secret</label>
                    <input type="password" id="amz-lwa-client-secret" class="form-control" placeholder="...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">LWA Refresh Token</label>
                    <input type="password" id="amz-refresh-token" class="form-control" placeholder="Atzr|...">
                </div>
                <div class="form-group">
                    <label class="form-label">Marketplace</label>
                    <select id="amz-marketplace" class="form-control">
                        <option value="US">Estados Unidos (US)</option>
                        <option value="BR">Brasil (BR)</option>
                        <option value="MX">Mexico (MX)</option>
                        <option value="CA">Canada (CA)</option>
                        <option value="UK">Reino Unido (UK)</option>
                        <option value="DE">Alemanha (DE)</option>
                        <option value="FR">Franca (FR)</option>
                        <option value="IT">Italia (IT)</option>
                        <option value="ES">Espanha (ES)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button id="btn-amazon-test" class="btn btn-primary" onclick="testAmazonConexao()">
                    <i class="fas fa-plug"></i> Testar Conexao
                </button>
                <button id="btn-amazon-save" class="btn btn-success" onclick="saveAmazonConfig()">
                    <i class="fas fa-save"></i> Salvar Configuracao
                </button>
                <button id="btn-amazon-auto-sync" class="btn btn-secondary" onclick="toggleAutoSync()">
                    <i class="fas fa-sync-alt"></i> Auto-Sync: Desligado
                </button>
            </div>

            <div id="amazon-config-status" style="margin-top: 15px; padding: 12px; border-radius: 8px; display: none;"></div>
        </div>`;

        integracaoTab.appendChild(document.importNode(new DOMParser().parseFromString(sectionHtml, 'text/html').body.firstElementChild, true).cloneNode ?
            createSectionElement(sectionHtml) : null);

        // Load saved config
        loadAmazonConfig();
    }

    function createSectionElement(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        return temp.firstElementChild;
    }

    async function loadAmazonConfig() {
        try {
            const response = await fetch('api/crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'carregar', tipo: 'todos' })
            });
            const data = await response.json();

            const amz = (data.apiKeys && data.apiKeys.amazon) || (data.config && data.config.amazon) || {};
            if (amz.aws_access_key_id) document.getElementById('amz-access-key').value = amz.aws_access_key_id;
            if (amz.aws_secret_access_key) document.getElementById('amz-secret-key').value = amz.aws_secret_access_key;
            if (amz.lwa_client_id) document.getElementById('amz-lwa-client-id').value = amz.lwa_client_id;
            if (amz.lwa_client_secret) document.getElementById('amz-lwa-client-secret').value = amz.lwa_client_secret;
            if (amz.lwa_refresh_token) document.getElementById('amz-refresh-token').value = amz.lwa_refresh_token;
            if (amz.marketplace) document.getElementById('amz-marketplace').value = amz.marketplace;
        } catch (e) {
            console.log('Nenhuma config Amazon encontrada');
        }
    }

    window.testAmazonConexao = async function() {
        const config = {
            aws_access_key_id: document.getElementById('amz-access-key')?.value || '',
            aws_secret_access_key: document.getElementById('amz-secret-key')?.value || '',
            lwa_client_id: document.getElementById('amz-lwa-client-id')?.value || '',
            lwa_client_secret: document.getElementById('amz-lwa-client-secret')?.value || '',
            lwa_refresh_token: document.getElementById('amz-refresh-token')?.value || '',
            marketplace: document.getElementById('amz-marketplace')?.value || 'US',
        };

        const statusDiv = document.getElementById('amazon-config-status');
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando conexao...';
        statusDiv.style.background = 'rgba(0, 168, 255, 0.1)';
        statusDiv.style.color = 'var(--primary-color)';

        try {
            const response = await fetch('api/crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'test-amazon-connection', tipo: 'amazon', dados: JSON.stringify(config) })
            });
            const result = await response.json();

            if (result.success) {
                statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${result.message} (${result.orders_found || 0} pedidos recentes encontrados)`;
                statusDiv.style.background = 'rgba(46, 204, 113, 0.1)';
                statusDiv.style.color = 'var(--success-color)';
            } else {
                statusDiv.innerHTML = `<i class="fas fa-times-circle"></i> ${result.message}`;
                statusDiv.style.background = 'rgba(231, 76, 60, 0.1)';
                statusDiv.style.color = 'var(--danger-color)';
            }
        } catch (e) {
            statusDiv.innerHTML = `<i class="fas fa-times-circle"></i> Erro de conexao: ${e.message}`;
            statusDiv.style.background = 'rgba(231, 76, 60, 0.1)';
            statusDiv.style.color = 'var(--danger-color)';
        }
    };

    window.saveAmazonConfig = async function() {
        const config = {
            aws_access_key_id: document.getElementById('amz-access-key')?.value || '',
            aws_secret_access_key: document.getElementById('amz-secret-key')?.value || '',
            lwa_client_id: document.getElementById('amz-lwa-client-id')?.value || '',
            lwa_client_secret: document.getElementById('amz-lwa-client-secret')?.value || '',
            lwa_refresh_token: document.getElementById('amz-refresh-token')?.value || '',
            marketplace: document.getElementById('amz-marketplace')?.value || 'US',
            ativa: true,
            dataCriacao: new Date().toISOString().split('T')[0]
        };

        if (!window.marketManager) return;
        const mm = window.marketManager;

        try {
            const response = await fetch('api/crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'carregar', tipo: 'todos' })
            });
            const data = await response.json();

            // Salvar config Amazon no servidor via salvar-configuracoes
            await fetch('api/crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'salvar-configuracoes', dados: JSON.stringify({ amazon: config }) })
            });

            localStorage.setItem('amazon_config', JSON.stringify(config));

            mm.mostrarNotificacao('Configuracao Amazon salva com sucesso!', 'success');
        } catch (e) {
            console.error('Erro ao salvar config:', e);
            mm.mostrarNotificacao('Erro ao salvar configuracao', 'danger');
        }
    };

    let autoSyncInterval = null;
    window.toggleAutoSync = function() {
        const btn = document.getElementById('btn-amazon-auto-sync');
        if (autoSyncInterval) {
            clearInterval(autoSyncInterval);
            autoSyncInterval = null;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Auto-Sync: Desligado';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('Auto-Sync desligado', 'info');
            }
        } else {
            syncAmazonPedidos();
            autoSyncInterval = setInterval(() => syncAmazonPedidos(), 60000); // 60s
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Auto-Sync: Ligado (60s)';
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-success');
            if (window.marketManager) {
                window.marketManager.mostrarNotificacao('Auto-Sync ativado (sync a cada 60s)', 'success');
            }
        }
    };

    function startAmazonPolling() {
        // Restaura auto-sync se estava ligado
        const saved = localStorage.getItem('amazon_autosync');
        if (saved === 'true') {
            const btn = document.getElementById('btn-amazon-auto-sync');
            if (btn) {
                autoSyncInterval = setInterval(() => syncAmazonPedidos(), 60000);
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Auto-Sync: Ligado (60s)';
                btn.classList.add('btn-success');
            }
        }
    }

    // Iniciar injecao
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectAmazonUI);
    } else {
        injectAmazonUI();
    }
})();
