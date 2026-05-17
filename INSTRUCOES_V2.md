# 🚀 Market Manager Pro V2 - Instruções de Uso

## 📋 Índice

1. [Instalação](#instalação)
2. [Primeiro Acesso](#primeiro-acesso)
3. [Criar Pedido](#criar-pedido)
4. [Criar Produto](#criar-produto)
5. [Integração Amazon](#integração-amazon)
6. [Dicas e Truques](#dicas-e-truques)
7. [Solução de Problemas](#solução-de-problemas)

---

## 📦 Instalação

### Pré-requisitos

- XAMPP ou servidor PHP 7.4+
- MySQL (opcional - usa JSON)
- Navegador moderno

### Passos

1. **Copiar arquivos para o servidor**
   ```bash
   Copie a pasta amazongest para: C:\xampp\htdocs\amazongest
   ```

2. **Verificar permissões**
   - Certifique-se que a pasta `data/` tem permissão de escrita

3. **Acessar o sistema**
   ```
   http://localhost/amazongest/index_v2.php
   ```

---

## 🎯 Primeiro Acesso

### Tela Inicial

Ao acessar o sistema pela primeira vez, você verá:

- **Dashboard** com estatísticas vazias
- **Menu lateral** com todas as seções
- **Botões** para criar pedido/produto

### Estrutura

```
┌─────────────────────────────────────────┐
│  🚀 Market Manager Pro                  │
├─────────────────────────────────────────┤
│  🏠 Dashboard                           │
│  🛒 Pedidos (0)                         │
│  📦 Produtos (0)                        │
│  👥 Clientes (0)                        │
│  🛍️ Integração Amazon                   │
│  ⚙️ Configurações                       │
└─────────────────────────────────────────┘
```

---

## 📝 Criar Pedido

### Passo a Passo

1. **Clique em "Novo Pedido"** no header

2. **Preencha os dados do cliente**
   - Nome (obrigatório)
   - Email
   - Telefone
   - CPF/CNPJ

3. **Preencha o endereço**
   - CEP
   - Rua
   - Número
   - Complemento
   - Bairro
   - Cidade
   - Estado

4. **Adicione o produto**
   - Nome do produto (obrigatório)
   - Categoria
   - Preço Custo (obrigatório)
   - Preço Venda (obrigatório)
   - Quantidade
   - Frete

5. **Configure o rastreio**
   - Código de rastreio
   - Status (Pendente, Processando, Em Trânsito, Entregue)

6. **Veja o resumo**
   - Subtotal
   - Taxa Marketplace
   - Frete
   - Total
   - Lucro Líquido (calculado automaticamente)

7. **Clique em "Salvar Pedido"**

### Dicas

- O lucro é calculado automaticamente
- A taxa varia por categoria
- Você pode editar pedidos existentes
- Use o filtro para encontrar pedidos

---

## 📦 Criar Produto

### Passo a Passo

1. **Clique em "Novo Produto"** no header

2. **Preencha os dados básicos**
   - Nome do produto (obrigatório)
   - Categoria
   - Estoque

3. **Configure os preços**
   - Preço Custo (obrigatório)
   - Preço Venda (obrigatório)
   - Frete
   - Embalagem

4. **Adicione detalhes**
   - Descrição
   - Link do produto
   - ASIN (Amazon)

5. **Clique em "Salvar Produto"**

### Dicas

- Use categorias para organizar
- Mantenha o estoque atualizado
- O ASIN ajuda na integração Amazon
- Links externos facilitam acesso

---

## 🛍️ Integração Amazon

### Configurar Credenciais

1. **Acesse "Integração Amazon"** no menu

2. **Preencha as credenciais SP-API**
   - **AWS Access Key ID**: Chave de acesso AWS
   - **AWS Secret Access Key**: Chave secreta AWS
   - **LWA Client ID**: ID do cliente LWA
   - **LWA Client Secret**: Segredo do cliente LWA
   - **LWA Refresh Token**: Token de refresh LWA
   - **Marketplace**: BR, US, MX, CA, etc.

3. **Clique em "Testar Conexão"**
   - Se sucesso: ✅ Conexão estabelecida
   - Se erro: ❌ Verifique as credenciais

4. **Clique em "Salvar Configurações"**

### Sincronizar Pedidos

1. **Clique em "Sincronizar Agora"**

2. **Aguarde o processo**
   - Importa pedidos dos últimos 30 dias
   - Cria pedidos automaticamente
   - Mapeia status corretamente

3. **Verifique os pedidos importados**
   - Vá para "Pedidos"
   - Filtre por origem "amazon"

### Onde obter credenciais?

1. **Acesse Seller Central**
   ```
   https://sellercentral.amazon.com
   ```

2. **Vá para:**
   - Apps & Services
   - Manage Your Apps
   - Create New App

3. **Obtenha:**
   - LWA Client ID
   - LWA Client Secret
   - LWA Refresh Token

4. **Configure IAM**
   - Crie usuário IAM
   - Obtenha Access Key ID
   - Obtenha Secret Access Key

---

## 💡 Dicas e Truques

### Dashboard

- **Cards de estatísticas**: Visão rápida do negócio
- **Pedidos recentes**: Últimos 5 pedidos
- **Top produtos**: Mais vendidos

### Pedidos

- **Busca**: Encontre por cliente, produto ou rastreio
- **Filtros**: Filtre por status
- **Ações rápidas**: Edite ou exclua com um clique
- **Status badges**: Visualize o status facilmente

### Produtos

- **Busca**: Encontre por nome ou descrição
- **Filtros**: Filtre por categoria
- **Lucro**: Veja a margem de cada produto
- **Estoque**: Mantenha controle do inventário

### Atalhos

- **Ctrl + N**: Novo pedido
- **Ctrl + P**: Novo produto
- **Esc**: Fechar modal
- **Enter**: Salvar formulário

---

## 🔧 Solução de Problemas

### CRUD não funciona

**Problema**: Não consigo salvar pedidos/produtos

**Solução**:
1. Verifique permissões da pasta `data/`
2. Verifique se o servidor PHP está rodando
3. Limpe o cache do navegador
4. Verifique o console do navegador para erros

### Integração Amazon falha

**Problema**: Erro ao conectar com Amazon

**Solução**:
1. Verifique as credenciais
2. Teste a conexão
3. Verifique se o token é válido
4. Confirme o marketplace correto

### Pedidos não aparecem

**Problema**: Salvei mas não vejo o pedido

**Solução**:
1. Recarregue a página
2. Verifique os filtros
3. Limpe a busca
4. Verifique o arquivo `data/pedidos.json`

### Design quebrado

**Problema**: Interface parece estranha

**Solução**:
1. Limpe o cache do navegador
2. Verifique se o CSS carregou
3. Use um navegador moderno
4. Verifique o console para erros

---

## 📊 Estrutura de Dados

### Pedido

```json
{
  "id": 1,
  "cliente": {
    "nome": "João Silva",
    "email": "joao@email.com",
    "telefone": "11999999999",
    "cpfCnpj": "12345678900"
  },
  "endereco": {
    "rua": "Rua Exemplo",
    "numero": "123",
    "complemento": "Apto 1",
    "bairro": "Centro",
    "cidade": "São Paulo",
    "estado": "SP",
    "cep": "01234567"
  },
  "produto": {
    "nome": "Produto Exemplo",
    "categoria": "eletronicos",
    "precoCusto": 50.00,
    "precoVenda": 100.00
  },
  "quantidade": 1,
  "frete": 15.00,
  "codigoRastreio": "BR123456789",
  "status": "pendente",
  "dataCadastro": "2026-04-23 10:00:00"
}
```

### Produto

```json
{
  "id": 1,
  "nome": "Produto Exemplo",
  "categoria": "eletronicos",
  "estoque": 10,
  "precoCusto": 50.00,
  "precoVenda": 100.00,
  "frete": 15.00,
  "embalagem": 2.00,
  "descricao": "Descrição do produto",
  "link": "https://exemplo.com/produto",
  "asin": "B08XXXXXXX",
  "dataCadastro": "2026-04-23 10:00:00"
}
```

---

## 🎨 Customização

### Cores

Edite `css/style_v2.css`:

```css
:root {
    --primary-color: #6366f1;      /* Cor principal */
    --success-color: #10b981;      /* Sucesso */
    --warning-color: #f59e0b;      /* Aviso */
    --danger-color: #ef4444;       /* Erro */
}
```

### Taxas

Edite `data/config.json`:

```json
{
  "taxaPadrao": 15,
  "categoriasAmazon": {
    "eletronicos": {"nome": "Eletrônicos", "taxa": 15},
    "livros": {"nome": "Livros", "taxa": 10}
  }
}
```

---

## 📞 Suporte

### Erros Comuns

| Erro | Solução |
|------|---------|
| "Erro de comunicação" | Verifique se o servidor está rodando |
| "Credenciais inválidas" | Verifique as chaves da Amazon |
| "Dados não fornecidos" | Preencha todos os campos obrigatórios |
| "Permissão negada" | Verifique permissões da pasta data/ |

### Logs

Verifique os logs do servidor:
- XAMPP: `C:\xampp\apache\logs\error.log`
- PHP: `C:\xampp\php\logs\php_error_log`

---

## 🚀 Próximos Passos

1. **Configure a integração Amazon**
2. **Crie seus primeiros produtos**
3. **Adicione pedidos manualmente**
4. **Sincronize com Amazon**
5. **Analise os resultados**

---

**Desenvolvido com ❤️ para gestão eficiente de e-commerce**