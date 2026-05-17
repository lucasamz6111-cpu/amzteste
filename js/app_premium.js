// ============================================
// APP PREMIUM - Lógica de Negócio
// Sistema Unificado com Cálculos Avançados
// ============================================

class AmazonGestApp {
    constructor() {
        this.pedidos = [];
        this.produtos = [];
        this.clientes = [];
        this.config = {
            categorias: {
                'eletrônicos': { taxa: 12, descricao: 'Eletrônicos' },
                'livros': { taxa: 3, descricao: 'Livros' },
                'roupas': { taxa: 18, descricao: 'Roupas' },
                'calçados': { taxa: 20, descricao: 'Calçados' },
                'alimentos': { taxa: 8, descricao: 'Alimentos' },
                'beleza': { taxa: 15, descricao: 'Beleza & Higiene' },
                'esportes': { taxa: 16, descricao: 'Esportes' },
                'móveis': { taxa: 10, descricao: 'Móveis' },
                'brinquedos': { taxa: 14, descricao: 'Brinquedos' },
                'saúde': { taxa: 9, descricao: 'Saúde' }
            }
        };
        this.charts = {};
        this.init();
    }

    init() {
        this.carregarDados();
        this.configurarEventos();
        this.preencherSelects();
        this.renderizarDashboard();
        this.adicionarEventosNavegacao();
    }

    // ============================================
    // DADOS
    // ============================================

    carregarDados() {
        try {
            const dados = localStorage.getItem('amazongest_dados');
            if (dados) {
                const json = JSON.parse(dados);
                this.pedidos = json.pedidos || [];
                this.produtos = json.produtos || [];
                this.clientes = json.clientes || [];
            }
            this.atualizarBadges();
        } catch (e) {
            console.error('Erro ao carregar dados:', e);
        }
    }

    salvarDados() {
        try {
            localStorage.setItem('amazongest_dados', JSON.stringify({
                pedidos: this.pedidos,
                produtos: this.produtos,
                clientes: this.clientes
            }));
            this.atualizarBadges();
            this.renderizarDashboard();
        } catch (e) {
            console.error('Erro ao salvar dados:', e);
        }
    }

    atualizarBadges() {
        document.getElementById('badge-pedidos').textContent = this.pedidos.length;
        document.getElementById('badge-produtos').textContent = this.produtos.length;
    }

    // ============================================
    // INTERFACE
    // ============================================

    configurarEventos() {
        // Event listeners para navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.navegarPara(item.dataset.page);
            });
        });
    }

    adicionarEventosNavegacao() {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                this.navegarPara(item.dataset.page);
            });
        });
    }

    navegarPara(pagina) {
        document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
        const page = document.getElementById(`page-${pagina}`);
        if (page) {
            page.classList.add('active');
        }

        // Renderizar conteúdo específico
        if (pagina === 'pedidos') {
            this.renderizarPedidos();
        } else if (pagina === 'produtos') {
            this.renderizarProdutos();
        } else if (pagina === 'analises') {
            this.renderizarAnalises();
        } else if (pagina === 'lucro') {
            this.renderizarCalculadora();
        }
    }

    preencherSelects() {
        const selects = document.querySelectorAll('select[id*="categoria"]');
        selects.forEach(select => {
            select.innerHTML = '<option value="">Selecione uma categoria</option>';
            Object.entries(this.config.categorias).forEach(([key, cat]) => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = `${cat.descricao} (${cat.taxa}%)`;
                select.appendChild(option);
            });
        });
    }

    // ============================================
    // MODAIS
    // ============================================

    abrirModalPedido() {
        document.getElementById('modal-pedido').classList.add('active');
    }

    abrirModalProduto() {
        document.getElementById('modal-produto').classList.add('active');
    }

    fecharModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // ============================================
    // SALVAR DADOS
    // ============================================

    salvarPedido() {
        const cliente = document.getElementById('pedido-cliente').value;
        const produto = document.getElementById('pedido-produto').value;
        const categoria = document.getElementById('pedido-categoria').value;
        const custo = parseFloat(document.getElementById('pedido-custo').value) || 0;
        const venda = parseFloat(document.getElementById('pedido-venda').value) || 0;
        const frete = parseFloat(document.getElementById('pedido-frete').value) || 0;

        if (!cliente || !produto || !categoria || custo <= 0 || venda <= 0) {
            this.mostrarAlerta('Preencha todos os campos obrigatórios!', 'error');
            return;
        }

        const pedido = {
            id: Date.now(),
            cliente,
            produto,
            categoria,
            custo,
            venda,
            frete,
            data: new Date().toISOString(),
            status: 'pendente'
        };

        this.pedidos.push(pedido);
        this.salvarDados();
        this.fecharModal('modal-pedido');
        this.limparFormularioPedido();
        this.mostrarAlerta('Pedido salvo com sucesso!', 'success');
    }

    salvarProduto() {
        const nome = document.getElementById('produto-nome').value;
        const categoria = document.getElementById('produto-categoria').value;
        const custo = parseFloat(document.getElementById('produto-custo').value) || 0;
        const venda = parseFloat(document.getElementById('produto-venda').value) || 0;
        const sku = document.getElementById('produto-sku').value;

        if (!nome || !categoria || custo <= 0 || venda <= 0) {
            this.mostrarAlerta('Preencha todos os campos obrigatórios!', 'error');
            return;
        }

        const produto = {
            id: Date.now(),
            nome,
            categoria,
            custo,
            venda,
            sku,
            data: new Date().toISOString()
        };

        this.produtos.push(produto);
        this.salvarDados();
        this.fecharModal('modal-produto');
        this.limparFormularioProduto();
        this.mostrarAlerta('Produto salvo com sucesso!', 'success');
    }

    limparFormularioPedido() {
        document.getElementById('pedido-cliente').value = '';
        document.getElementById('pedido-produto').value = '';
        document.getElementById('pedido-categoria').value = '';
        document.getElementById('pedido-custo').value = '';
        document.getElementById('pedido-venda').value = '';
        document.getElementById('pedido-frete').value = '';
    }

    limparFormularioProduto() {
        document.getElementById('produto-nome').value = '';
        document.getElementById('produto-categoria').value = '';
        document.getElementById('produto-custo').value = '';
        document.getElementById('produto-venda').value = '';
        document.getElementById('produto-sku').value = '';
    }

    // ============================================
    // RENDERIZAÇÃO
    // ============================================

    renderizarDashboard() {
        const stats = this.calcularEstatisticas();
        
        document.getElementById('kpi-faturamento').textContent = this.formatarMoeda(stats.faturamento);
        document.getElementById('kpi-lucro').textContent = this.formatarMoeda(stats.lucroTotal);
        document.getElementById('kpi-margem').textContent = stats.margemMedia.toFixed(1) + '%';
        document.getElementById('kpi-pedidos').textContent = stats.totalPedidos;

        this.renderizarGraficoMensal();
        this.renderizarGraficoProdutos();
        this.renderizarInsightsIA();
    }

    renderizarPedidos() {
        const container = document.getElementById('lista-pedidos');
        
        if (this.pedidos.length === 0) {
            container.innerHTML = '<p class="text-muted text-center p-3">Nenhum pedido cadastrado</p>';
            return;
        }

        let html = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Custo</th>
                        <th>Venda</th>
                        <th>Lucro</th>
                        <th>Data Entrega</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
        `;

        this.pedidos.forEach(pedido => {
            const lucro = this.calcularLucroLiquido(pedido.venda, pedido.custo, pedido.categoria, pedido.frete);
            const dataEntrega = pedido.rastreio?.dataEntrega || pedido.dataEntrega || '';
            html += `
                <tr>
                    <td>#${pedido.id}</td>
                    <td>${pedido.cliente}</td>
                    <td>${pedido.produto}</td>
                    <td>${this.config.categorias[pedido.categoria]?.descricao || pedido.categoria}</td>
                    <td>${this.formatarMoeda(pedido.custo)}</td>
                    <td>${this.formatarMoeda(pedido.venda)}</td>
                    <td class="text-success">${this.formatarMoeda(lucro)}</td>
                    <td>${dataEntrega ? this.formatarData(dataEntrega) : '-'}</td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="app.excluirPedido(${pedido.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    renderizarProdutos() {
        const container = document.getElementById('lista-produtos');
        
        if (this.produtos.length === 0) {
            container.innerHTML = '<p class="text-muted text-center p-3">Nenhum produto cadastrado</p>';
            return;
        }

        let html = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Custo</th>
                        <th>Venda</th>
                        <th>Margem</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
        `;

        this.produtos.forEach(produto => {
            const lucro = produto.venda - produto.custo;
            const margem = ((lucro / produto.venda) * 100).toFixed(1);
            html += `
                <tr>
                    <td>#${produto.id}</td>
                    <td>${produto.nome}</td>
                    <td>${this.config.categorias[produto.categoria]?.descricao || produto.categoria}</td>
                    <td>${this.formatarMoeda(produto.custo)}</td>
                    <td>${this.formatarMoeda(produto.venda)}</td>
                    <td>${margem}%</td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="app.excluirProduto(${produto.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    renderizarAnalises() {
        this.renderizarGraficosCategorias();
        this.renderizarTopProdutos();
    }

    renderizarCalculadora() {
        this.renderizarTabelaTaxas();
    }

    // ============================================
    // GRÁFICOS
    // ============================================

    renderizarGraficoMensal() {
        const ctx = document.getElementById('chart-mensal').getContext('2d');
        
        if (this.charts.mensal) {
            this.charts.mensal.destroy();
        }

        const dados = this.agruparPorMes();
        
        this.charts.mensal = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dados.meses,
                datasets: [
                    {
                        label: 'Faturamento',
                        data: dados.faturamento,
                        borderColor: '#00d4ff',
                        backgroundColor: 'rgba(0, 212, 255, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    },
                    {
                        label: 'Lucro',
                        data: dados.lucro,
                        borderColor: '#00ff88',
                        backgroundColor: 'rgba(0, 255, 136, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#d0d5dd' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#8891a0' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: '#8891a0' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    }
                }
            }
        });
    }

    renderizarGraficoProdutos() {
        const ctx = document.getElementById('chart-produtos').getContext('2d');
        
        if (this.charts.produtos) {
            this.charts.produtos.destroy();
        }

        const topProdutos = this.obterTopProdutos(5);
        
        this.charts.produtos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topProdutos.map(p => p.nome),
                datasets: [{
                    label: 'Lucro Total',
                    data: topProdutos.map(p => p.lucroTotal),
                    backgroundColor: '#00d4ff',
                    borderColor: '#0099ff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { labels: { color: '#d0d5dd' } }
                },
                scales: {
                    x: {
                        ticks: { color: '#8891a0' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    y: {
                        ticks: { color: '#8891a0' }
                    }
                }
            }
        });
    }

    renderizarGraficosCategorias() {
        const ctx = document.getElementById('chart-categorias');
        if (!ctx) return;

        const dados = this.agruparPorCategoria();
        
        if (this.charts.categorias) {
            this.charts.categorias.destroy();
        }

        this.charts.categorias = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: dados.categorias,
                datasets: [{
                    data: dados.vendas,
                    backgroundColor: [
                        '#00d4ff',
                        '#ff006e',
                        '#8338ec',
                        '#ffaa00',
                        '#00ff88',
                        '#3b82f6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#d0d5dd' } }
                }
            }
        });
    }

    // ============================================
    // CÁLCULOS
    // ============================================

    calcularLucroLiquido(precoVenda, precoCusto, categoria, frete = 0) {
        const taxa = (this.config.categorias[categoria]?.taxa || 0) / 100;
        const taxaValor = precoVenda * taxa;
        const lucroLiquido = precoVenda - precoCusto - taxaValor - frete;
        return Math.max(lucroLiquido, 0);
    }

    calcularEstatisticas() {
        let faturamento = 0;
        let custo = 0;
        let lucroTotal = 0;
        let margemTotal = 0;

        this.pedidos.forEach(pedido => {
            faturamento += pedido.venda;
            custo += pedido.custo;
            const lucro = this.calcularLucroLiquido(pedido.venda, pedido.custo, pedido.categoria, pedido.frete);
            lucroTotal += lucro;
            margemTotal += (lucro / pedido.venda) * 100;
        });

        return {
            faturamento,
            custo,
            lucroTotal,
            margemMedia: this.pedidos.length > 0 ? margemTotal / this.pedidos.length : 0,
            totalPedidos: this.pedidos.length
        };
    }

    agruparPorMes() {
        const meses = {};
        const agora = new Date();

        for (let i = 5; i >= 0; i--) {
            const data = new Date(agora.getFullYear(), agora.getMonth() - i, 1);
            const chave = data.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
            meses[chave] = { faturamento: 0, lucro: 0 };
        }

        this.pedidos.forEach(pedido => {
            const data = new Date(pedido.data);
            const chave = data.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
            if (meses[chave]) {
                meses[chave].faturamento += pedido.venda;
                meses[chave].lucro += this.calcularLucroLiquido(pedido.venda, pedido.custo, pedido.categoria, pedido.frete);
            }
        });

        return {
            meses: Object.keys(meses),
            faturamento: Object.values(meses).map(m => m.faturamento),
            lucro: Object.values(meses).map(m => m.lucro)
        };
    }

    agruparPorCategoria() {
        const categorias = {};

        this.pedidos.forEach(pedido => {
            const cat = pedido.categoria;
            if (!categorias[cat]) {
                categorias[cat] = 0;
            }
            categorias[cat] += pedido.venda;
        });

        return {
            categorias: Object.keys(categorias).map(k => this.config.categorias[k]?.descricao || k),
            vendas: Object.values(categorias)
        };
    }

    obterTopProdutos(limite = 5) {
        const ranking = {};

        this.pedidos.forEach(pedido => {
            if (!ranking[pedido.produto]) {
                ranking[pedido.produto] = { nome: pedido.produto, lucroTotal: 0, quantidade: 0 };
            }
            ranking[pedido.produto].lucroTotal += this.calcularLucroLiquido(pedido.venda, pedido.custo, pedido.categoria, pedido.frete);
            ranking[pedido.produto].quantidade++;
        });

        return Object.values(ranking)
            .sort((a, b) => b.lucroTotal - a.lucroTotal)
            .slice(0, limite);
    }

    // ============================================
    // CALCULADORA
    // ============================================

    atualizarCalculo() {
        const categoria = document.getElementById('calc-categoria').value;
        const custo = parseFloat(document.getElementById('calc-custo').value) || 0;
        const venda = parseFloat(document.getElementById('calc-venda').value) || 0;
        const frete = parseFloat(document.getElementById('calc-frete').value) || 0;

        if (!categoria || custo <= 0 || venda <= 0) {
            document.getElementById('calc-taxa').textContent = '-';
            document.getElementById('calc-lucro-bruto').textContent = '-';
            document.getElementById('calc-lucro-liquido').textContent = '-';
            document.getElementById('calc-margem').textContent = 'Margem: -';
            return;
        }

        const taxa = (this.config.categorias[categoria]?.taxa || 0);
        const taxaValor = venda * (taxa / 100);
        const lucoBruto = venda - custo;
        const lucoLiquido = lucoBruto - taxaValor - frete;
        const margem = (lucoLiquido / venda * 100).toFixed(1);

        document.getElementById('calc-taxa').textContent = taxa + '%';
        document.getElementById('calc-lucro-bruto').textContent = this.formatarMoeda(lucoBruto);
        document.getElementById('calc-lucro-liquido').textContent = this.formatarMoeda(lucoLiquido);
        document.getElementById('calc-margem').textContent = `Margem: ${margem}%`;
    }

    renderizarTabelaTaxas() {
        const container = document.getElementById('tabela-taxas');
        let html = `<table class="table"><thead><tr><th>Categoria</th><th>Taxa</th></tr></thead><tbody>`;

        Object.entries(this.config.categorias).forEach(([key, cat]) => {
            html += `<tr><td>${cat.descricao}</td><td>${cat.taxa}%</td></tr>`;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    renderizarTopProdutos() {
        const container = document.getElementById('lista-top-produtos');
        const topProdutos = this.obterTopProdutos(10);

        if (topProdutos.length === 0) {
            container.innerHTML = '<p class="text-muted text-center p-3">Nenhum dado disponível</p>';
            return;
        }

        let html = `
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produto</th>
                        <th>Vendas</th>
                        <th>Lucro Total</th>
                    </tr>
                </thead>
                <tbody>
        `;

        topProdutos.forEach((p, i) => {
            html += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${p.nome}</td>
                    <td>${p.quantidade}</td>
                    <td class="text-success">${this.formatarMoeda(p.lucroTotal)}</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    renderizarInsightsIA() {
        const stats = this.calcularEstatisticas();
        const topProdutos = this.obterTopProdutos(3);

        let insights = [];

        if (stats.margemMedia < 15) {
            insights.push({
                tipo: 'aviso',
                titulo: '⚠️ Margem Baixa',
                descricao: `Sua margem média é ${stats.margemMedia.toFixed(1)}%. Considere aumentar preços ou reduzir custos.`
            });
        }

        if (stats.totalPedidos > 50) {
            insights.push({
                tipo: 'sucesso',
                titulo: '✅ Vendas em Alta',
                descricao: `Você atingiu ${stats.totalPedidos} vendas! Foco em manter a qualidade.`
            });
        }

        if (topProdutos.length > 0) {
            insights.push({
                tipo: 'info',
                titulo: '🏆 Top Produto',
                descricao: `${topProdutos[0].nome} é seu produto mais rentável com ${this.formatarMoeda(topProdutos[0].lucroTotal)} de lucro.`
            });
        }

        const container = document.getElementById('ia-insights');
        let html = '';

        insights.forEach(insight => {
            html += `
                <div class="alert alert-${insight.tipo}">
                    <div style="flex: 1;">
                        <strong>${insight.titulo}</strong><br>
                        ${insight.descricao}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html || '<p class="text-muted">Nenhum insight disponível. Adicione pedidos para obter análises.</p>';
    }

    // ============================================
    // AÇÕES
    // ============================================

    excluirPedido(id) {
        if (confirm('Tem certeza que deseja deletar este pedido?')) {
            this.pedidos = this.pedidos.filter(p => p.id !== id);
            this.salvarDados();
            this.renderizarPedidos();
        }
    }

    excluirProduto(id) {
        if (confirm('Tem certeza que deseja deletar este produto?')) {
            this.produtos = this.produtos.filter(p => p.id !== id);
            this.salvarDados();
            this.renderizarProdutos();
        }
    }

    exportarDados() {
        const dados = {
            pedidos: this.pedidos,
            produtos: this.produtos,
            data: new Date().toISOString()
        };
        const blob = new Blob([JSON.stringify(dados, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `amazongest_${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        this.mostrarAlerta('Dados exportados com sucesso!', 'success');
    }

    limparDados() {
        if (confirm('⚠️ ATENÇÃO: Isto vai deletar TODOS os dados! Tem certeza?')) {
            this.pedidos = [];
            this.produtos = [];
            this.clientes = [];
            this.salvarDados();
            location.reload();
        }
    }

    // ============================================
    // UTILITÁRIOS
    // ============================================

    formatarMoeda(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    formatarData(data) {
        if (!data) return '-';
        const dt = new Date(data);
        if (Number.isNaN(dt.getTime())) return data;
        return dt.toLocaleDateString('pt-BR');
    }

    mostrarAlerta(mensagem, tipo = 'info') {
        const alertas = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo}`;
        alerta.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        alerta.innerHTML = `
            <div style="display: flex; gap: 1rem;">
                <span style="font-size: 1.5rem;">${alertas[tipo]}</span>
                <span>${mensagem}</span>
            </div>
        `;

        document.body.appendChild(alerta);
        setTimeout(() => alerta.remove(), 4000);
    }
}

// Inicializar aplicação
let app;
document.addEventListener('DOMContentLoaded', () => {
    app = new AmazonGestApp();
});
