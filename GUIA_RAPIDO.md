# 🎓 Guia de Uso - Market Manager Pro 4.1

## 🚀 INÍCIO RÁPIDO

### 1️⃣ Primeira Vez? Configure a IA em 2 MINUTOS

1. Clique em **⚙️ Configurações** (canto superior direito)
2. Procure por **"Assistente IA Inteligente"**
3. Clique em **"enter.pollinations.ai"** (link azul)
4. Copie sua chave (começa com `sk_`)
5. Cole no campo de input
6. Clique em **"💾 Salvar Chave"**
7. ✅ Pronto! A IA está ativa

### 2️⃣ Usando a IA

Na aba **"Assistência de IA"**:
- **Campo de pergunta**: Digite sua pergunta
- **Botões rápidos**: Use análises pré-prontas
- **Histórico**: Conversas salvas automaticamente

## 📊 FEATURES DISPONÍVEIS

### Dashboard
- 📈 Resumo financeiro
- 📦 Estoque baixo (alertas)
- 💰 Lucro total e margem
- 📅 Últimos pedidos

### Pedidos
- ➕ Criar novo pedido
- ✏️ Editar existentes
- 🗑️ Remover
- 🔍 Filtrar por status
- 📊 Exportar em CSV

### Produtos
- ➕ Adicionar produto
- 💲 Gerenciar preços
- 📦 Controlar estoque
- 🏷️ Por categoria (Amazon)
- 📈 Análise de rentabilidade

### Análise Financeira
- 📊 Análises mensais
- 🏆 Produtos mais rentáveis
- 💸 Composição de custos
- 📈 Tendências de vendas
- 💡 Dicas de otimização

### Alertas Inteligentes
- ⚠️ Estoque baixo
- 🚨 Pedidos atrasados
- 🆕 Novos clientes
- 💔 Produtos com prejuízo

### IA Avançada
- 🧠 Perguntas livres com contexto
- 💰 Análise de preços
- 🚀 Sugestões de vendas
- 🔮 Previsão de demanda
- 🔎 Pesquisa de mercado

## 🔧 CONFIGURAÇÕES

### Obrigatórias
- **Chave Pollinations.AI**: Sem ela, a IA não funciona

### Opcionais
- **Tema**: Escuro (padrão) ou Claro
- **Notificações**: Ativar/desativar
- **Backup automático**: Diário (recomendado)
- **Taxa de plataforma**: Por marketplace

## 💾 DADOS

Todos os seus dados (pedidos, produtos, clientes) são salvos em:
```
📁 data/
  ├── pedidos.json
  ├── produtos.json
  ├── clientes.json
  ├── config.json
  └── api-keys.json
```

### Fazer Backup
1. Dashboard → **⬇️ Backup** (rodapé)
2. Escolha: Pedidos, Produtos ou Ambos
3. Arquivo baixa automaticamente

### Restaurar Backup
1. Configurações → **Importação**
2. Selecione arquivo `.json`
3. Dados são restaurados automaticamente

## 🆘 TROUBLESHOOTING

### ❌ "API Key não configurada"
**Solução**: Vá em Configurações → Cole sua chave → Clique em Salvar

### ❌ "Saldo insuficiente"
**Solução**: Visite [enter.pollinations.ai/account](https://enter.pollinations.ai/account) para recarregar créditos

### ❌ "Resposta inválida da API"
**Solução**: Sua chave pode estar expirada. Gere uma nova em [enter.pollinations.ai](https://enter.pollinations.ai)

### ❌ Dados não salvando
**Solução**: Verifique se a pasta `data/` tem permissões de escrita (chmod 777)

## 📱 DICAS & TRUQUES

✅ **Salve a chave no servidor**
- Seu navegador só guarda em `localStorage`
- Ao trocar de computador, ela se perde
- Use a chave salva em `data/api-keys.json`

✅ **Use os botões rápidos de IA**
- Análise de Preços: Comparar com concorrentes
- Sugestões de Vendas: Aumentar ticket médio
- Previsão de Vendas: Planejar estoque

✅ **Acompanhe as dicas**
- A IA oferece 6-9 sugestões personalizadas
- Baseadas em seus dados reais
- Implementar = Lucro +15-25%

✅ **Organize por Categoria**
- Amazon: +30 categorias com taxas diferentes
- Shopee: Estrutura similar
- Margem varia muito por categoria!

## 🎯 BOAS PRÁTICAS

### Diário
- ✅ Adicionar pedidos no app
- ✅ Atualizar estoque quando vender
- ✅ Verificar alertas de atrasos

### Semanal
- ✅ Gerar relatório de análise
- ✅ Revisar dicas da IA
- ✅ Fazer backup

### Mensal
- ✅ Análise de rentabilidade
- ✅ Revisar preços com IA
- ✅ Fazer backup em nuvem

## 🔐 SEGURANÇA

⚠️ **Importante**:
1. Sua API Key fica no seu navegador
2. Nunca é enviada para nossos servidores
3. Use `api-keys.json` para sincronizar entre dispositivos
4. Não compartilhe sua chave com ninguém

## 📞 SUPORTE

- **Documentação**: Veja `data/README.md`
- **Changelog**: Veja `CHANGELOG.md`
- **Pollinations**: [https://pollinations.ai](https://pollinations.ai)

---

**Versão**: 4.1  
**Última Atualização**: 13/03/2026  
**Status**: ✅ Tudo Funcionando
