# Market Manager Pro V2 - Documentação de Melhorias

## 🚀 Visão Geral

O Market Manager Pro V2 é uma versão completamente refatorada do sistema de gestão de e-commerce, com foco em:

- **CRUD funcional e robusto** para pedidos e produtos
- **Design moderno e responsivo** com interface intuitiva
- **Integração Amazon real** via SP-API
- **Melhor performance e usabilidade**

---

## 📋 Principais Melhorias

### 1. CRUD Refeito e Funcional

#### ✅ Adicionar Pedido
- Formulário completo com validação
- Cálculo automático de lucro em tempo real
- Suporte a múltiplos itens
- Endereço completo do cliente
- Rastreio integrado

**Como usar:**
1. Clique em "Novo Pedido" no header
2. Preencha os dados do cliente
3. Preencha o endereço de entrega
4. Adicione os produtos
5. O sistema calcula automaticamente o lucro
6. Salve o pedido

#### ✅ Adicionar Produto
- Cadastro completo de produtos
- Gestão de estoque
- Cálculo de margem de lucro
- Suporte a ASIN da Amazon
- Link para produto externo

**Como usar:**
1. Clique em "Novo Produto" no header
2. Preencha os dados do produto
3. Defina preço de custo e venda
4. O sistema mostra a margem de lucro
5. Salve o produto

#### ✅ Excluir Itens
- Exclusão segura com confirmação
- Atualização automática da interface
- Preservação de dados relacionados

**Como usar:**
1. Na lista de pedidos/produtos
2. Clique no botão de excluir (ícone lixeira)
3. Confirme a exclusão
4. O item é removido imediatamente

### 2. Design Moderno

#### 🎨 Interface
- **Tema escuro** profissional
- **Gradientes** modernos
- **Animações** suaves
- **Responsivo** para mobile
- **Cards** com hover effects
- **Badges** coloridos para status

#### 📱 Responsividade
- Sidebar adaptável
- Grid flexível
- Tabelas com scroll horizontal
- Modais responsivos
- Botões touch-friendly

### 3. Integração Amazon Real

#### 🔗 Funcionalidades
- **Autenticação SP-API** com Signature V4
- **Sincronização de pedidos** automática
- **Cache de tokens** para performance
- **Tratamento de erros** robusto
- **Mapeamento de status** automático

#### ⚙️ Configuração
1. Acesse "Integração Amazon"
2. Preencha as credenciais:
   - AWS Access Key ID
   - AWS Secret Access Key
   - LWA Client ID
   - LWA Client Secret
   - LWA Refresh Token
3. Selecione o marketplace
4. Clique em "Testar Conexão"
5. Clique em "Sincronizar Agora"

#### 📦 Sincronização
- Importa pedidos dos últimos 30 dias
- Cria pedidos automaticamente
- Mapeia status corretamente
- Preserva dados do cliente
- Calcula valores automaticamente

---

## 🗂️ Estrutura de Arquivos

```
amazongest/
├── index_v2.php              # Arquivo principal V2
├── css/
│   └── style_v2.css         # Estilos modernos
├── js/
│   └── app_v2.js            # JavaScript V2
├── includes/
│   ├── amazon_sync_v2.php   # Integração Amazon V2
│   └── functions.php        # Funções auxiliares
└── data/
    ├── pedidos.json         # Pedidos
    ├── produtos.json        # Produtos
    ├── clientes.json        # Clientes
    ├── config.json          # Configurações
    └── api-keys.json       # Chaves de API
```

---

## 🎯 Funcionalidades por Seção

### Dashboard
- **Cards de estatísticas** em tempo real
- **Pedidos recentes** com preview
- **Produtos mais vendidos** ranking
- **Faturamento total** calculado

### Pedidos
- **Lista completa** com filtros
- **Busca** por cliente/produto/rastreio
- **Filtro por status** (pendente, processando, etc.)
- **Ações rápidas** (editar, excluir)
- **Status badges** coloridos

### Produtos
- **Catálogo completo** com filtros
- **Busca** por nome/descrição
- **Filtro por categoria**
- **Cálculo de lucro** por produto
- **Gestão de estoque**

### Clientes
- **Base de clientes** completa
- **Histórico de pedidos** por cliente
- **Informações de contato**
- **CPF/CNPJ** cadastrado

### Integração Amazon
- **Configuração de credenciais**
- **Teste de conexão**
- **Sincronização de pedidos**
- **Status da sincronização**

### Configurações
- **Taxa padrão** do marketplace
- **Categorias personalizadas**
- **Configurações de sistema**

---

## 🔧 Como Usar

### 1. Acessar o Sistema

Abra o navegador e acesse:
```
http://localhost/amazongest/index_v2.php
```

### 2. Criar Primeiro Pedido

1. Clique em "Novo Pedido"
2. Preencha os dados do cliente
3. Adicione o produto
4. Defina os preços
5. Veja o cálculo automático de lucro
6. Salve

### 3. Criar Primeiro Produto

1. Clique em "Novo Produto"
2. Preencha os dados do produto
3. Defina categoria e estoque
4. Configure preços
5. Salve

### 4. Configurar Amazon

1. Acesse "Integração Amazon"
2. Preencha as credenciais SP-API
3. Teste a conexão
4. Sincronize os pedidos

---

## 🎨 Customização

### Cores

Edite `css/style_v2.css` e modifique as variáveis CSS:

```css
:root {
    --primary-color: #6366f1;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    /* ... */
}
```

### Taxas por Categoria

Edite `data/config.json`:

```json
{
    "taxaPadrao": 15,
    "categoriasAmazon": {
        "eletronicos": {"nome": "Eletrônicos", "taxa": 15},
        "livros": {"nome": "Livros", "taxa": 10},
        /* ... */
    }
}
```

---

## 🐛 Correções de Bugs

### V1 → V2

| Problema | Solução |
|-----------|---------|
| CRUD não funcionava | Refeito completamente |
| Design bugado | Interface moderna |
| Integração fake | SP-API real |
| Sem validação | Validação completa |
| Performance lenta | Otimizado |
| Sem feedback visual | Toast notifications |
| Responsividade ruim | Mobile-first |

---

## 📊 Comparativo V1 vs V2

| Funcionalidade | V1 | V2 |
|---------------|----|----|
| CRUD Pedidos | ❌ Bugado | ✅ Funcional |
| CRUD Produtos | ❌ Bugado | ✅ Funcional |
| Design | ❌ Antigo | ✅ Moderno |
| Amazon | ❌ Fake | ✅ Real |
| Responsivo | ❌ Ruim | ✅ Excelente |
| Performance | ❌ Lento | ✅ Rápido |
| Validação | ❌ Parcial | ✅ Completa |
| Feedback | ❌ Ausente | ✅ Toasts |
| Filtros | ❌ Básicos | ✅ Avançados |
| Cálculos | ❌ Manuais | ✅ Automáticos |

---

## 🔒 Segurança

- **Sanitização de inputs** em todos os formulários
- **Validação de dados** no servidor
- **Proteção contra SQL injection** (JSON-based)
- **Tokens seguros** para Amazon
- **HTTPS recomendado** em produção

---

## 🚀 Próximas Melhorias

- [ ] Exportação de relatórios em PDF/Excel
- [ ] Integração com Shopee
- [ ] Dashboard com gráficos
- [ ] Notificações por email
- [ ] Multi-usuário com permissões
- [ ] API REST para integrações
- [ ] App mobile (React Native)

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique a documentação
2. Teste a conexão com Amazon
3. Verifique os logs do servidor
4. Confirme as credenciais

---

## 📝 Notas de Versão

### V2.0.0 (2026-04-23)
- ✅ CRUD completamente refeito
- ✅ Design moderno implementado
- ✅ Integração Amazon real
- ✅ Performance otimizada
- ✅ Responsividade melhorada
- ✅ Validação completa
- ✅ Feedback visual adicionado

---

**Desenvolvido com ❤️ para gestão eficiente de e-commerce**