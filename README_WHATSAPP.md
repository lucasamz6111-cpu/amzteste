# WhatsApp API Integration - AmazonGest

## Como Usar

### 1. Iniciar o Servidor WhatsApp
```bash
cd c:\xamppp\htdocs\amazongest
node server.js
```

### 2. Acessar a Interface
- Abra o sistema AmazonGest
- Clique na aba "WhatsApp"
- Clique em "Gerar QR Code"

### 3. Conectar WhatsApp
- Abra o WhatsApp no seu celular
- Vá em Configurações > Dispositivos Vinculados > Vincular um Dispositivo
- Escaneie o QR code exibido na tela

### 4. Usar o Chat
- Após conectar, você verá as conversas recentes
- Clique em uma conversa para abrir
- Digite o número do destinatário (formato: 5511999999999)
- Envie mensagens

## Funcionalidades

- ✅ Conexão real via QR code
- ✅ Receber mensagens automaticamente
- ✅ Enviar mensagens para qualquer número
- ✅ Histórico de conversas
- ✅ Interface integrada ao sistema

## API Endpoints

- `GET /status` - Verificar status da conexão
- `GET /qrcode` - Obter QR code atual
- `GET /conversations` - Listar conversas
- `GET /messages/:contactId` - Obter mensagens de um contato
- `POST /send` - Enviar mensagem
- `POST /disconnect` - Desconectar

## Dependências

- Node.js v20+
- whatsapp-web.js
- Express
- qrcode
- cors

## Observações

- Mantenha o servidor Node.js rodando enquanto usar
- O QR code expira após alguns minutos
- As mensagens são armazenadas em memória (reinicia ao parar servidor)