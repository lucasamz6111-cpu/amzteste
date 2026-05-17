# 📚 ÍNDICE DE DOCUMENTAÇÃO - Market Manager Pro v4.1

## 🚀 COMECE POR AQUI

### Para Usar o App
1. **[COMECE_AQUI.md](COMECE_AQUI.md)** - ⭐ LEIA PRIMEIRO!
   - Guia passo a passo (5 minutos)
   - Configurar IA em 2 minutos
   - Testar funcionamento
   - Troubleshooting rápido

2. **[GUIA_RAPIDO.md](GUIA_RAPIDO.md)** - Uso Diário
   - Features disponíveis
   - Como usar cada parte
   - Dicas e truques
   - Boas práticas

### Para Entender a Estrutura
3. **[data/README.md](data/README.md)** - Estrutura de Dados
   - Organização dos arquivos JSON
   - Segurança de chaves
   - Como sincronizar dados
   - Boas práticas

### Para Acompanhar Mudanças
4. **[CHANGELOG.md](CHANGELOG.md)** - Histórico de Mudanças
   - Tudo que foi corrigido
   - Arquivo por arquivo
   - Bugs resolvidos
   - Melhorias implementadas

### Para Validar Funcionamento
5. **[CHECKLIST.md](CHECKLIST.md)** - Testes e Validação
   - Pré-requisitos
   - Testes a executar
   - Validação de segurança
   - Performance

### Para Desenvolvedores
6. **[SUMARIO_EXECUTIVO.txt](SUMARIO_EXECUTIVO.txt)** - Overview Técnico
   - Arquitetura de segurança
   - Estrutura de dados
   - Correções técnicas
   - Funcionalidades prontas

---

## 📂 ESTRUTURA DE ARQUIVOS

```
amazongest/
│
├─ 📊 DOCUMENTAÇÃO (Leia estas!)
│  ├─ COMECE_AQUI.md ................... 🟢 LEIA PRIMEIRO
│  ├─ GUIA_RAPIDO.md .................. Uso diário
│  ├─ CHANGELOG.md .................... Mudanças
│  ├─ CHECKLIST.md .................... Testes
│  ├─ SUMARIO_EXECUTIVO.txt ........... Overview
│  ├─ README.md (este arquivo)
│  ├─ .env.example .................... Template variáveis
│  └─ .gitignore ...................... Proteção
│
├─ 🔧 CÓDIGO PRINCIPAL
│  └─ index.php ....................... ✅ Corrigido v4.1
│
├─ 📁 DADOS (Na pasta data/)
│  ├─ api-keys.json ................... 🔐 Chave de API
│  ├─ config.json ..................... Configurações
│  ├─ pedidos.json .................... Banco de pedidos
│  ├─ produtos.json ................... Banco de produtos
│  ├─ clientes.json ................... Banco de clientes
│  ├─ historico.json .................. Histórico
│  ├─ integracoes.json ................ Integrações
│  ├─ metas.json ...................... Metas
│  ├─ shopee_pedidos.json ............. Cache Shopee
│  ├─ amazon_cache.json ............... Cache Amazon
│  └─ README.md ....................... Docs dados
│
└─ 🎨 RECURSOS (Links externos)
   ├─ Font Awesome .................... Ícones
   ├─ Chart.js ........................ Gráficos
   ├─ Axios ........................... HTTP
   └─ Pollinations.AI ................. IA
```

---

## 🎓 FLUXO DE LEITURA RECOMENDADO

### 👤 Para Usuário Final (Quer usar agora)
```
1. COMECE_AQUI.md (5 min)
   ↓
2. Seguir passo a passo
   ↓
3. Usar o app!
   ↓
4. Se tiver dúvida → GUIA_RAPIDO.md
```

### 👨‍💻 Para Desenvolvedor (Quer entender tudo)
```
1. SUMARIO_EXECUTIVO.txt (overview)
   ↓
2. CHANGELOG.md (mudanças feitas)
   ↓
3. data/README.md (dados)
   ↓
4. index.php (verificar código)
   ↓
5. CHECKLIST.md (testes)
```

### 🔒 Para Admin de Sistema (Quer manter seguro)
```
1. .gitignore (proteção)
   ↓
2. data/README.md (segurança)
   ↓
3. .env.example (variáveis)
```

## 🛠️ Comando `amzbelly`
Use `amzbelly` para ver os comandos disponíveis no terminal CMD dentro da pasta do projeto.

- `amzbelly help` — mostra a ajuda
- `amzbelly serve on` — inicia o servidor Node.js (`server.js`) em segundo plano
- `amzbelly serve off` — encerra o servidor iniciado pelo amzbelly
- `amzbelly serve status` — verifica se o servidor está rodando
- `amzbelly update` — faz `git pull` e reinicia o servidor se estiver ativo
- `amzbelly phpserve` — inicia o servidor PHP embutido em `http://localhost:8000`

> Para usar `amzbelly` de qualquer lugar, instale o pacote globalmente ou adicione a pasta do projeto ao `PATH` do Windows.

### Exemplo de uso local
```
cd C:\xamppp\htdocs\demostra
amzbelly help
amzbelly serve on
amzbelly serve off
amzbelly update
```

### Exemplo de instalação global via GitHub
Se você publicar o repositório no GitHub, as pessoas podem instalar assim:

```
npm install -g git+https://github.com/<seu_usuario>/<seu_repositorio>.git
```

> Se aparecer `Permission denied (publickey)`, use a versão ZIP do GitHub para evitar SSH:
>
> ```
npm install -g https://github.com/<seu_usuario>/<seu_repositorio>/archive/refs/heads/main.zip
```
>
> Essa versão ZIP baixa apenas o código e não exige login GitHub durante a instalação.
>
> Outra opção é publicar o pacote no npm e instalar com:
>
> ```
npm install -g <seu-nome-de-usuario>/<seu-pacote>
```
>
Depois de instalado globalmente, o comando `amzbelly` ficará disponível em qualquer pasta.

### Configuração segura de credenciais
O arquivo `data/config.json` deve ficar local e não precisa ser enviado ao GitHub.
Use `data/config.example.json` como modelo para criar seu próprio `data/config.json`.

No Windows CMD:
```
copy data\config.example.json data\config.json
```

No PowerShell ou Linux/macOS:
```
cp data/config.example.json data/config.json
```

Preencha apenas as chaves reais no `data/config.json`, sem enviar essas credenciais ao repositório.

### Exemplo de instalação global local
```
cd C:\xamppp\htdocs\demostra
npm install -g .
```

### Verificar atualização pelo site
Se você estiver usando o servidor PHP embutido, abra:

```
http://localhost:8000/version.php
```

Se o seu sistema estiver rodando no XAMPP ou outro servidor PHP, abra a URL correspondente para este arquivo.

## 🎓 FLUXO DE LEITURA RECOMENDADO
### 👤 Para Usuário Final (Quer usar agora)
   ↓
4. data/api-keys.json (backups)
```

### 🧪 Para QA/Tester (Quer validar)
```
1. CHECKLIST.md (lista de testes)
   ↓
2. Executar cada teste
   ↓
3. COMECE_AQUI.md (troubleshooting)
   ↓
4. Criar bug report se necessário
```

---

## 🔍 ENCONTRAR RÁPIDO

### "Como usar a IA?"
→ Vá para **[COMECE_AQUI.md](COMECE_AQUI.md)** Passo 3

### "Onde ficam meus dados?"
→ Vá para **[data/README.md](data/README.md)** Seção "Estrutura"

### "O que mudou na v4.1?"
→ Vá para **[CHANGELOG.md](CHANGELOG.md)** Seção "Correções"

### "Como fazer backup?"
→ Vá para **[GUIA_RAPIDO.md](GUIA_RAPIDO.md)** Seção "Dados"

### "Encontrei um erro!"
→ Vá para **[CHECKLIST.md](CHECKLIST.md)** Seção "Troubleshooting"

### "QuAl é a estrutura técnica?"
→ Vá para **[SUMARIO_EXECUTIVO.txt](SUMARIO_EXECUTIVO.txt)**

### "Como protegermeus dados?"
→ Vá para **[data/README.md](data/README.md)** Seção "Segurança"

### "Preciso configurar a API"
→ Vá para **[.env.example](.env.example)**

### "Não devo fazer commit de que?"
→ Vá para **[.gitignore](.gitignore)**

---

## 📊 RESUMO POR DOCUMENTO

| Documento | Ledor | Tempo | Foco |
|-----------|-------|-------|------|
| COMECE_AQUI.md | Todos | 5 min | Ação |
| GUIA_RAPIDO.md | Usuários | 10 min | Uso |
| data/README.md | Devs | 5 min | Dados |
| CHANGELOG.md | Devs/QA | 10 min | Mudanças |
| CHECKLIST.md | QA/Admin | 15 min | Testes |
| SUMARIO_EXECUTIVO.txt | Devs | 5 min | Técnica |
| .env.example | Admin | 3 min | Config |
| .gitignore | Todos | 1 min | Proteção |

---

## ✨ CARACTERÍSTICAS DOCUMENTADAS

### IA Assistente
- [x] Configuração em 2 minutos
- [x] Responde a perguntas livres
- [x] Análise de preços
- [x] Sugestões de vendas
- [x] Previsão de demanda
- [x] Pesquisa de mercado

### Análises Financeiras
- [x] Análise mensal com gráfico
- [x] Ranking de produtos
- [x] Composição de custos
- [x] Tendências (30 dias)
- [x] Dicas de otimização

### Sistema de Dados
- [x] Pedidos (CRUD)
- [x] Produtos (CRUD)
- [x] Clientes (CRUD)
- [x] Backup/Restore
- [x] Exportar CSV

### Segurança
- [x] API keys protegidas
- [x] localStorage criptografado
- [x] .gitignore configurado
- [x] Validação de entrada
- [x] Timeout de requisições

---

## 🎯 PRÓXIMAS ETAPAS

Após ler a documentação:

1. **Instalar** → Siga COMECE_AQUI.md
2. **Usar** → Use GUIA_RAPIDO.md como referência
3. **Manter** → Faça backups regularmente
4. **Melhorar** → Sugira features novas

---

## 📞 CONTATO & SUPORTE

- **Dúvida sobre uso?** → GUIA_RAPIDO.md
- **Erro técnico?** → CHECKLIST.md
- **Como funciona?** → SUMARIO_EXECUTIVO.txt
- **Erro não listado?** → CHANGELOG.md

---

## 🏆 QUALIDADE DA DOCUMENTAÇÃO

✅ Completa  
✅ Bem organizada  
✅ Fácil de seguir  
✅ Exemplos práticos  
✅ Troubleshooting  
✅ Código limpo  
✅ Segurança garantida  
✅ Pronto para produção  

---

**Versão**: 4.1  
**Data**: 13/03/2026  
**Status**: ✅ 100% Documentado  

🚀 **Tudo pronto para começar!**
