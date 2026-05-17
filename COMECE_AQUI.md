# 🎬 GUIA PASSO A PASSO - Primeiros 5 Minutos

## ⏱️ TEMPO ESTIMADO: 5 MINUTOS

---

## PASSO 1: ACESSAR O SITE (1 minuto)

1. Abra o navegador (Chrome, Firefox, Edge, Safari)
2. Digite na barra de endereço:
   ```
   http://localhost/amazongest/
   ```
3. Pressione ENTER
4. ✅ A página deve carregar com o dashboard vazio

**SE HOUVER ERRO**: Verifique se o Apache/PHP está rodando

---

## PASSO 2: CONFIGURAR A IA (2 minutos)

### 2.1 Ir para Configurações
- Procure no topo direito: **⚙️ Configurações**
- Clique nele
- Você deve ver várias abas/seções

### 2.2 Encontrar "Assistente IA"
- Procure por: **"Assistente IA Inteligente"**
- Você verá um painel com:
  - Esquema azul/gradiente
  - Ícone de cérebro 🧠
  - Instruções em português

### 2.3 Copiar Chave de API
1. No painel, clique no link azul: **"enter.pollinations.ai"**
2. Abrirá nova aba
3. Faça login ou cadastre (se necessário)
4. Procure por: **"API Keys"** ou **"Keys"**
5. Clique em: **"Copiar"** (ou selecione e Ctrl+C)
6. A chave começa com: **sk_**

### 2.4 Colar Chave no App
1. Volte para a aba do Market Manager Pro
2. Procure o campo: **"🔐 Sua Chave API Pollinations"**
3. Clique no campo de input (tipo password)
4. Ctrl+V para colar a chave
5. Campo deve ficar assim: `sk_abc123def456...`

### 2.5 Salvar Chave
1. Clique no botão: **"💾 Salvar Chave"** (lado direito)
2. Aguarde 2-3 segundos
3. Uma notificação verde deve aparecer: 
   ```
   ✅ Chave API salva! Você pode usar a IA agora.
   ```
4. Status muda para: **"✅ Configurada com sucesso!"**

✅ **FIM DA CONFIGURAÇÃO!** A IA está pronta!

---

## PASSO 3: TESTAR A IA (1 minuto)

### 3.1 Ir para Assistência de IA
1. Procure na navegação lateral ou superior
2. Clique em: **"Assistência de IA"** ou **"IA"**
3. Você verá:
   - Campo de pergunta (textarea grande)
   - Botões de análises rápidas
   - Campo de resposta (vazio por enquanto)

### 3.2 Fazer Primeira Pergunta
1. Clique no campo que diz: **"Pergunte ao assistente IA..."**
2. Digite algo simples como:
   ```
   Olá! Você está funcionando?
   ```
3. Clique no botão: **"📎 Enviar para IA"** ou **"➤ Enviar"**

### 3.3 Aguardar Resposta
- A IA vai processar (pode levar 5-30 segundos)
- Uma animação / loading pode aparecer
- Resposta vai aparecer no campo de resultado
- Algo como: **"Olá! Sim, estou aqui e pronto para ajudar!"**

✅ **SUCESSO!** A IA está respondendo!

---

## PASSO 4: ENTENDER O FLUXO

```
Você (Browser)
    ↓
[Campo de Pergunta]
    ↓
[Clique em Enviar]
    ↓
[JavaScript envia para Pollinations.AI]
    ↓
[Pollinations.AI processa]
    ↓
[Resposta volta]
    ↓
[Campo de Resultado mostra resposta]
    ↓
Você vê a resposta!
```

---

## PASSO 5: PRÓXIMAS AÇÕES

### Adicionar Dados
1. Clique em: **"📦 Produtos"**
2. Clique em: **"➕ Novo Produto"**
3. Preencha:
   - Nome: "Produto Teste"
   - Preço Custo: 50.00
   - Preço Venda: 100.00
   - Categoria: "Eletrônicos"
4. Clique: **"💾 Salvar"**

### Ver Análises
1. Clique em: **"📊 Análise Financeira"**
2. Veja as análises:
   - Análise Mensal (gráfico)
   - Top Produtos (ranking)
   - Despesas (pie chart)
   - Tendências (últimos 30 dias)
   - Dicas (sugestões personalizadas)

### Usar a IA para Analisar
1. Clique em: **"Assistência de IA"**
2. Use botões rápidos:
   - 💰 **Análise de Preços**
   - 🚀 **Sugestões de Vendas**
   - 🔮 **Previsão de Vendas**
   - 🔎 **Pesquisa de Mercado**
3. Ou faça pergunta personalizada

---

## ⚠️ TROUBLESHOOTING RÁPIDO

### Pergunta: "Página não carrega"
**Resposta**: Verificar:
- [ ] Apache rodando? (XAMPP deve estar ligado)
- [ ] URL está correta? (http://localhost/amazongest/)
- [ ] Pasta `data/` existe? (deve estar em c:\xampp\htdocs\amazongest\data\)

### Pergunta: "Chave não salva"
**Resposta**: 
- [ ] Chave começa com **sk_**? (ou **pk_**)
- [ ] Copiou inteira? (não faltam caracteres)
- [ ] Clicou no botão de "Salvar"?
- [ ] Viu notificação verde de sucesso?

### Pergunta: "IA não responde"
**Resposta**:
- [ ] Chave está salva? (verifique o campo)
- [ ] Internet está conectada?
- [ ] Chave tem créditos? (visite enter.pollinations.ai/account)
- [ ] Aguardou 30 segundos?

### Pergunta: "Erro no console (F12)"
**Resposta**:
- Abra: **F12 > Console**
- Procure por mensagens em vermelho
- Procure pelo erro em: [CHANGELOG.md](CHANGELOG.md)

---

## 📱 DICAS IMPORTANTES

✅ **Salvando a Chave**
- Fica no arquivo: `data/api-keys.json`
- Também fica em: `localStorage` do navegador
- Se trocar de navegador, busca no arquivo

✅ **Usando Análises**
- Cada análise usa contexto de seus dados
- Quanto mais dados, melhores as sugestões
- Rode análises toda semana para acompanhar

✅ **Backup de Segurança**
- Vá para: **"Relatórios & Exportar"**
- Clique: **"Fazer Backup"**
- Salve o arquivo em local seguro

---

## 🎯 VOCÊ CONSEGUIU!

Se chegou aqui, significa que:

✅ Instalação funcionando  
✅ Chave de API salva  
✅ IA respondendo  
✅ Já pode adicionar dados  
✅ Pronto para trabalhar!

---

## 📞 E AGORA?

Leia o **GUIA_RAPIDO.md** para:
- Features mais avançadas
- Boas práticas
- Dicas de segurança
- Troubleshooting completo

---

**Tempo gasto**: ~5 minutos  
**Status**: ✅ Pronto para usar!  
**Próximo passo**: Adicione seus primeiros produtos!

🚀 **Divirta-se com a IA!**
