const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const cors = require('cors');
const multer = require('multer');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Configurar multer para upload de arquivos
const upload = multer({
    limits: { fileSize: 16 * 1024 * 1024 }, // 16MB limite
    fileFilter: (req, file, cb) => {
        // Aceitar imagens, vídeos, áudios e documentos
        const allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/avi', 'video/mov',
            'audio/mp3', 'audio/wav', 'audio/ogg',
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        if (allowedTypes.includes(file.mimetype)) {
            cb(null, true);
        } else {
            cb(new Error('Tipo de arquivo não suportado'), false);
        }
    }
});

// Store para dados
let qrCodeData = null;
let isConnected = false;
let messages = [];
let conversations = new Map();

// Inicializar cliente WhatsApp
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
});

// Eventos do WhatsApp
client.on('qr', async (qr) => {
    console.log('QR Code gerado!');
    qrCodeData = await qrcode.toDataURL(qr);
    console.log('QR Code pronto para exibição');
});

client.on('ready', () => {
    console.log('WhatsApp conectado!');
    isConnected = true;
});

client.on('disconnected', (reason) => {
    console.log('WhatsApp desconectado:', reason);
    isConnected = false;
    qrCodeData = null;
});

client.on('message', async (message) => {
    console.log('Nova mensagem:', message.from, message.body, message.type);

    try {
        const contact = await message.getContact();
        const contactId = (contact && contact.id && contact.id._serialized) ? contact.id._serialized : message.from;
        const contactName = contact && (contact.pushname || contact.name) ? (contact.pushname || contact.name) : (message._data && message._data.notifyName ? message._data.notifyName : contactId);

        let msgData = {
            id: message.id.id,
            chatId: contactId,
            from: message.from,
            to: message.to,
            body: message.body,
            timestamp: message.timestamp,
            type: 'received',
            messageType: message.type
        };

        // Processar mensagens de mídia (imagens, vídeos, etc.)
        if (message.hasMedia) {
            try {
                const media = await message.downloadMedia();
                msgData.media = {
                    mimetype: media.mimetype,
                    data: media.data, // Base64
                    filename: media.filename || `media_${Date.now()}.${media.mimetype.split('/')[1]}`
                };
                console.log(`Mídia recebida: ${media.mimetype}, tamanho: ${media.data.length} bytes`);
            } catch (error) {
                console.error('Erro ao baixar mídia:', error);
                msgData.media = { error: 'Falha ao baixar mídia' };
            }
        }

        messages.push(msgData);
        console.log(`Mensagem armazenada: chatId=${contactId}, type=${message.type}, hasMedia=${message.hasMedia}`);

        // Atualizar conversa
        let lastMessageText = message.body;
        if (message.hasMedia) {
            lastMessageText = message.type === 'image' ? '📷 Imagem' :
                             message.type === 'video' ? '🎥 Vídeo' :
                             message.type === 'audio' ? '🎵 Áudio' :
                             message.type === 'document' ? '📄 Documento' :
                             '📎 Arquivo';
        }

        if (!conversations.has(contactId)) {
            conversations.set(contactId, {
                id: contactId,
                name: contactName,
                lastMessage: lastMessageText,
                timestamp: message.timestamp
            });
        } else {
            const conv = conversations.get(contactId);
            conv.lastMessage = lastMessageText;
            conv.timestamp = message.timestamp;
        }
    } catch (error) {
        console.error('Erro processando mensagem:', error);
    }
});

// Inicializar cliente
client.initialize();

// Rotas da API

// Status da conexão
app.get('/status', (req, res) => {
    res.json({
        connected: isConnected,
        qrCode: qrCodeData
    });
});

// Obter QR Code
app.get('/qrcode', (req, res) => {
    if (qrCodeData) {
        res.json({ qrCode: qrCodeData });
    } else {
        res.status(404).json({ error: 'QR Code não disponível' });
    }
});

// Obter conversas
app.get('/conversations', (req, res) => {
    const convs = Array.from(conversations.values())
        .sort((a, b) => b.timestamp - a.timestamp);
    res.json(convs);
});

// Obter mensagens de um contato
app.get('/messages/:contactId', (req, res) => {
    const contactId = decodeURIComponent(req.params.contactId);
    console.log(`Buscando mensagens para contactId: ${contactId}`);
    console.log(`Total de mensagens no storage: ${messages.length}`);
    
    const contactMessages = messages.filter(msg => {
        const fromMatch = msg.from === contactId || msg.from.split('@')[0] === contactId.split('@')[0];
        const toMatch = msg.to === contactId || msg.to.split('@')[0] === contactId.split('@')[0];
        const chatIdMatch = msg.chatId === contactId;
        return fromMatch || toMatch || chatIdMatch;
    });
    
    console.log(`Mensagens encontradas: ${contactMessages.length}`);
    res.json(contactMessages);
});

// Enviar mensagem ou mídia
app.post('/send', upload.single('media'), async (req, res) => {
    console.log('=== RECEBENDO REQUEST /send ===');
    console.log('Headers:', req.headers['content-type']);
    console.log('Body keys:', Object.keys(req.body));
    console.log('Body:', req.body);
    console.log('File:', req.file ? { originalname: req.file.originalname, size: req.file.size, mimetype: req.file.mimetype } : 'nenhum arquivo');

    const { to, message } = req.body;
    const mediaFile = req.file;

    if (!to || typeof to !== 'string') {
        return res.status(400).json({ error: 'Dados inválidos. Informe o destinatário.' });
    }

    if (!message && !mediaFile) {
        return res.status(400).json({ error: 'Dados inválidos. Informe mensagem ou arquivo de mídia.' });
    }

    console.log('Recebido - to:', to, 'message:', message ? `"${message}"` : 'undefined', 'mediaFile:', mediaFile ? mediaFile.originalname : 'nenhum');

    if (!isConnected) {
        return res.status(400).json({ error: 'WhatsApp não conectado' });
    }

    try {
        let chatId = to.trim();

        if (chatId.includes('@')) {
            console.log('Chat ID com domínio detectado:', chatId);
        } else {
            const digits = chatId.replace(/\D/g, '');
            if (!/^[0-9]+$/.test(digits) || digits.length < 8) {
                return res.status(400).json({ error: 'Número inválido. Use apenas dígitos e inclua o DDD.' });
            }

            if (!digits.startsWith('55') && digits.length <= 11) {
                chatId = '55' + digits + '@c.us';
            } else {
                chatId = digits + '@c.us';
            }
        }

        console.log('Enviando para:', chatId, 'Mensagem:', message ? 'sim' : 'não', 'Mídia:', mediaFile ? mediaFile.originalname : 'não');

        let chat;
        try {
            chat = await client.getChatById(chatId);
        } catch (err) {
            console.log('Erro ao obter chat por ID, tentando sendMessage direto:', err.message);
            chat = null;
        }

        let sentMessage;
        if (mediaFile) {
            // Enviar mídia
            const { MessageMedia } = require('whatsapp-web.js');
            let media;
            if (mediaFile.buffer) {
                // Arquivo em memória (buffer)
                media = new MessageMedia(mediaFile.mimetype, mediaFile.buffer.toString('base64'), mediaFile.originalname);
            } else if (mediaFile.path) {
                // Arquivo salvo em disco
                media = MessageMedia.fromFilePath(mediaFile.path);
            } else {
                throw new Error('Arquivo não possui buffer nem path válido');
            }
            const caption = message || '';

            if (chat) {
                sentMessage = await chat.sendMessage(media, { caption });
            } else {
                sentMessage = await client.sendMessage(chatId, media, { caption });
            }

            // Limpar arquivo temporário se existir
            if (mediaFile.path && fs.existsSync(mediaFile.path)) {
                fs.unlinkSync(mediaFile.path);
            }
        } else {
            // Enviar apenas texto
            if (chat) {
                sentMessage = await chat.sendMessage(message);
            } else {
                sentMessage = await client.sendMessage(chatId, message);
            }
        }

        const msgData = {
            id: sentMessage.id.id,
            chatId: chatId,
            from: 'me',
            to: chatId,
            body: message || (mediaFile ? '📎 Arquivo enviado' : ''),
            timestamp: Math.floor(Date.now() / 1000),
            type: 'sent',
            messageType: mediaFile ? 'media' : 'text'
        };

        if (mediaFile) {
            msgData.media = {
                mimetype: mediaFile.mimetype,
                filename: mediaFile.originalname,
                size: mediaFile.size
            };
        }

        messages.push(msgData);
        console.log(`Mensagem enviada armazenada: chatId=${chatId}, type=${msgData.messageType}`);

        res.json({ success: true });
    } catch (error) {
        console.error('Erro ao enviar:', error);
        // Limpar arquivo temporário em caso de erro
        if (req.file && fs.existsSync(req.file.path)) {
            fs.unlinkSync(req.file.path);
        }
        res.status(500).json({ error: 'Erro ao enviar: ' + (error.message || 'Falha desconhecida') });
    }
});

// Desconectar
app.post('/disconnect', async (req, res) => {
    try {
        await client.logout();
        isConnected = false;
        qrCodeData = null;
        conversations.clear();
        messages = [];
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: 'Erro ao desconectar' });
    }
});

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`Servidor WhatsApp API rodando na porta ${PORT}`);
});