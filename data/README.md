# 📋 Guia de Configurações - Market Manager Pro

## 📂 Estrutura de Arquivos na Pasta `/data/`

```
data/
├── config.json          # Configurações gerais do aplicativo
├── api-keys.json       # ⚠️ SEGURO - Chaves de API sensíveis
├── pedidos.json        # Base de dados de pedidos
├── produtos.json       # Base de dados de produtos
├── clientes.json       # Base de dados de clientes
├── historico.json      # Histórico de operações
├── integracoes.json    # Dados de integrações
├── metas.json          # Metas e objetivos
├── shopee_pedidos.json # Ordem de pedidos Shopee
└── amazon_cache.json   # Cache de dados Amazon
```

## 🔐 Segurança das Chaves de API

O arquivo `api-keys.json` contém informações sensíveis:
- **Chave Pollinations.AI**: `sk_k0MqrwOilO90knqfaTQtzb1760DnG73o` ✅
- Armazenadas de forma segura no servidor
- Carregadas automaticamente ao iniciar o app
- Nunca aparece no console do navegador

## ⚙️ Configurações Disponíveis

### 1. **Configurações Gerais** (`config.json`)

```json
{
  "sistema": {
    "versao": "4.1",
    "nome": "Market Manager Pro",
    "ultimoBackup": "2026-03-13"
  },
  "marketplace": {
    "amazon": { "taxaPadrao": 15 },
    "shopee": { "taxaPadrao": 12 }
  },
  "notificacoes": {
    "habilitadas": true,
    "som": true,
    "alertasEstoque": true
  },
  "tema": {
    "modo": "escuro",
    "cor_primaria": "#00a8ff"
  }
}
```

### 2. **Chaves de API** (`api-keys.json`)

```json
{
  "pollinationsAI": {
    "chave": "sk_k0MqrwOilO90knqfaTQtzb1760DnG73o",
    "ativa": true,
    "dataCriacao": "2024-03-13"
  },
  "amazon": {
    "chave": "",
    "ativa": false
  },
  "shopee": {
    "chave": "",
    "ativa": false
  }
}
```

## 🚀 Como Usar as Configurações

### Carregar Configurações via JavaScript
```javascript
// Já carregado automaticamente na inicialização
console.log(marketManager.config);
console.log(marketManager.apiKeyIA);
```

### Via PHP
```php
$config = json_decode(file_get_contents(__DIR__ . '/data/config.json'), true);
$apiKeys = json_decode(file_get_contents(__DIR__ . '/data/api-keys.json'), true);
```

## 🔄 Sincronização de Dados

1. **Servidor PHP** → Carrega dados dos arquivos JSON
2. **JavaScript (Frontend)** → Recebe dados via AJAX
3. **localStorage** → Mantém cópia local em cache
4. **Atualização** → Salva automaticamente no servidor

## 🛡️ Boas Práticas de Segurança

✅ **Fazer:**
- Manter `api-keys.json` privado
- Adicionar ao `.gitignore`
- Fazer backup regular
- Usar HTTPS em produção

❌ **Não Fazer:**
- Compartilhar `api-keys.json`
- Colocar em repositório público
- Exibir chaves no console do navegador
- Salvar em cookies inseguros

## 🔧 Como Adicionar Novas Configurações

1. Edite o arquivo `config.json`
2. Recarregue a página
3. Acesse via `this.config.novaConfiguracao`

Exemplo:
```json
{
  "novaConfiguracao": {
    "valor": "exemplo"
  }
}
```

## 📞 Suporte

- **Pollinations.AI**: https://enter.pollinations.ai
- **Recarregar Créditos**: https://enter.pollinations.ai/account
- **Documentação**: https://pollinations.ai/docs

---

**Last Updated**: 2026-03-13
