<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>WhatsApp Gestão Profissional | AmazonGest</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            overflow: hidden;
            background: radial-gradient(circle at 20% 30%, #0B1120, #030712);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif;
            color: #EFF3F6;
        }

        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #1E293B;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #3B82F6;
            border-radius: 10px;
        }

        .app {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 28px;
            background: rgba(15, 25, 45, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            flex-shrink: 0;
        }
        .brand h1 {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, #FFFFFF, #94A3F8);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .brand p {
            font-size: 0.75rem;
            color: #94A3B8;
        }
        .actions {
            display: flex;
            gap: 12px;
        }
        .btn {
            padding: 8px 18px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(4px);
            color: #E2E8F0;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .btn-primary {
            background: linear-gradient(95deg, #2563EB, #1E40AF);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }
        .btn-primary:hover {
            background: linear-gradient(95deg, #3B82F6, #2563EB);
            transform: translateY(-1px);
        }
        .btn-danger {
            background: rgba(220, 38, 38, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            color: #FCA5A5;
        }
        .btn-danger:hover {
            background: #DC2626;
            color: white;
        }
        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: #3B82F6;
        }

        .main-layout {
            display: flex;
            flex: 1;
            overflow: hidden;
            gap: 20px;
            padding: 16px 20px 20px 20px;
        }

        .sidebar {
            width: 320px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            border-radius: 28px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .sidebar-section {
            padding: 20px 18px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        .section-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            color: #9CA3AF;
            margin-bottom: 16px;
        }
        .status-card {
            background: #0F172A;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #1E293B;
            width: fit-content;
            margin-bottom: 12px;
        }
        .status-badge.connected { background: #064E3B; color: #A7F3D0; }
        .status-badge.disconnected { background: #7F1D1D; color: #FECACA; }
        .status-badge.connecting { background: #78350F; color: #FEF3C7; }
        .qr-container {
            background: #020617;
            border-radius: 20px;
            padding: 12px;
            text-align: center;
            margin: 12px 0;
            border: 1px dashed #3B82F6;
        }
        .qr-container img {
            max-width: 180px;
            border-radius: 16px;
        }
        .info-note {
            font-size: 0.7rem;
            color: #6B7280;
            margin-top: 10px;
        }
        .conversation-item {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 18px;
            padding: 14px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .conversation-item:hover {
            background: #1E293B;
            border-color: #3B82F6;
            transform: translateX(3px);
        }
        .conversation-item.active {
            background: linear-gradient(135deg, #1E293B, #0F172A);
            border-left: 4px solid #3B82F6;
        }
        .conv-name {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .conv-preview {
            font-size: 0.7rem;
            color: #94A3B8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(8, 14, 26, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            overflow: hidden;
        }
        .chat-header {
            padding: 18px 24px;
            border-bottom: 1px solid rgba(59, 130, 246, 0.15);
            flex-shrink: 0;
        }
        .chat-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
        }
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 0;
            scroll-behavior: smooth;
        }
        .message {
            max-width: 75%;
            padding: 10px 16px;
            border-radius: 22px;
            font-size: 0.9rem;
            line-height: 1.4;
            animation: fadeSlideUp 0.2s ease;
            word-break: break-word;
        }
        .message.sent {
            background: linear-gradient(115deg, #2563EB, #1D4ED8);
            align-self: flex-end;
            border-bottom-right-radius: 4px;
            color: white;
        }
        .message.received {
            background: #1E293B;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            color: #F1F5F9;
            border: 1px solid #334155;
        }
        .message time {
            font-size: 0.6rem;
            opacity: 0.7;
            display: block;
            margin-top: 5px;
            text-align: right;
        }
        .message.received time {
            text-align: left;
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-container {
            flex-shrink: 0;
            padding: 16px 20px 20px 20px;
            background: rgba(15, 23, 42, 0.5);
            border-top: 1px solid rgba(59, 130, 246, 0.15);
        }
        .input-row {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }
        .input-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .input-number, .message-input {
            background: #0F172A;
            border: 1px solid #334155;
            border-radius: 24px;
            padding: 10px 16px;
            color: white;
            font-size: 0.85rem;
            font-family: inherit;
            resize: none;
            outline: none;
        }
        .message-input {
            min-height: 44px;
            max-height: 100px;
        }
        .input-number:focus, .message-input:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
        }
        .send-button {
            background: linear-gradient(95deg, #2563EB, #1E40AF);
            border: none;
            border-radius: 40px;
            padding: 0 22px;
            height: 48px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .attach-button {
            background: linear-gradient(95deg, #10B981, #059669);
            border: none;
            border-radius: 40px;
            width: 48px;
            height: 48px;
            font-size: 18px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            transition: all 0.2s ease;
        }
        .attach-button:hover {
            background: linear-gradient(95deg, #059669, #047857);
            transform: translateY(-1px);
        }
        .empty-state {
            text-align: center;
            color: #6B7280;
            padding: 30px 20px;
            font-size: 0.85rem;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge-time {
            font-size: 0.7rem;
            color: #6B7280;
        }
        /* Estilos para mídia */
        .message-image {
            max-width: 250px;
            max-height: 200px;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-bottom: 8px;
        }
        .message-image:hover {
            transform: scale(1.02);
        }
        .message-video, .message-audio {
            max-width: 250px;
            border-radius: 12px;
            margin-bottom: 8px;
        }
        .message-file {
            background: rgba(255,255,255,0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .message-file a {
            color: #3B82F6;
            text-decoration: none;
        }
        .message-file a:hover {
            text-decoration: underline;
        }
        .message-placeholder {
            color: #9CA3AF;
            font-style: italic;
        }
        /* Modal para imagens */
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            cursor: pointer;
        }
        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        .image-modal .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
        }
        /* Upload de arquivos */
        .file-preview {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.3);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.8rem;
            color: #10B981;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .file-preview .remove-file {
            background: none;
            border: none;
            color: #EF4444;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            margin-left: 8px;
        }
    </style>
</head>
<body>
<div class="app">
    <div class="topbar">
        <div class="brand">
            <h1>⚡ AmazonGest Chat</h1>
            <p>WhatsApp Business — Conexão profissional via QR</p>
        </div>
        <div class="actions">
            <button class="btn btn-secondary" id="refreshButton">⟳ Atualizar</button>
            <button class="btn btn-success" id="startServerButton">⚡ Ligar Servidor</button>
            <button class="btn btn-secondary" id="restartButton">🔄 Reiniciar</button>
            <button class="btn btn-primary" id="connectButton">📲 Conectar</button>
        </div>
    </div>

    <div class="main-layout">
        <div class="sidebar">
            <div class="sidebar-section">
                <div class="section-title">📡 STATUS DA SESSÃO</div>
                <div class="status-card">
                    <div id="statusBadge" class="status-badge disconnected">⚪ Desconectado</div>
                    <div id="statusDescription" class="info-note">Clique em "Conectar" para iniciar o servidor e escanear o QR.</div>
                    <div id="qrBox" class="qr-container">
                        <div id="qrPlaceholder">📷 QR Code aparecerá aqui</div>
                    </div>
                    <button class="btn btn-danger" id="disconnectButton" style="width:100%; margin-top: 10px;">🔌 Desconectar</button>
                </div>
            </div>
            <div class="sidebar-section" style="flex:1;">
                <div class="section-title">💬 CONVERSAS RECENTES</div>
                <div id="conversationsList" style="display: flex; flex-direction: column; gap: 6px;">
                    <div class="empty-state">Nenhuma conversa carregada</div>
                </div>
            </div>
        </div>

        <div class="chat-panel">
            <div class="chat-header">
                <div class="flex-between">
                    <div>
                        <h2 id="chatHeader">📭 Nenhum contato</h2>
                        <p id="chatSubtitle">Digite um número ou selecione uma conversa</p>
                    </div>
                    <div class="badge-time">🕒 <span id="lastCheck">--:--:--</span></div>
                </div>
            </div>
            <div id="messagesArea" class="messages-area">
                <div class="empty-state">💬 As mensagens aparecerão aqui após a conexão.</div>
            </div>
            <div class="input-container">
                <div class="input-row">
                    <div class="input-group" style="flex: 1.2;">
                        <input type="text" id="targetNumber" class="input-number" placeholder="Número (ex: 5511999999999)">
                    </div>
                    <div class="input-group" style="flex: 3;">
                        <textarea id="messageText" class="message-input" rows="1" placeholder="Digite sua mensagem... (Enter para enviar)"></textarea>
                    </div>
                    <button class="attach-button" id="attachBtn" title="Anexar arquivo">
                        <span>📎</span>
                    </button>
                    <button class="send-button" id="sendButton">
                        <span>✈️</span> Enviar
                    </button>
                </div>
                <div id="filePreview" class="file-preview" style="display: none; margin-top: 8px;">
                    <span id="fileName"></span>
                    <button class="remove-file" id="removeFileBtn">×</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Input file oculto -->
<input type="file" id="fileInput" class="file-input" accept="image/*,video/*,audio/*,.pdf,.doc,.docx" style="display: none;">

<!-- Modal para imagens -->
<div id="imageModal" class="image-modal" style="display: none;" onclick="closeImageModal()">
    <button class="close-btn" onclick="closeImageModal()">×</button>
    <img id="modalImage" src="" alt="Imagem ampliada">
</div>

<script>
    const API_BASE = 'http://localhost:3001';
    const NODE_CONTROL_API = 'whatsapp_start.php';
    
    const statusBadge = document.getElementById('statusBadge');
    const statusDescription = document.getElementById('statusDescription');
    const qrBox = document.getElementById('qrBox');
    const refreshButton = document.getElementById('refreshButton');
    const startServerButton = document.getElementById('startServerButton');
    const restartButton = document.getElementById('restartButton');
    const connectButton = document.getElementById('connectButton');
    const disconnectButton = document.getElementById('disconnectButton');
    const conversationsList = document.getElementById('conversationsList');
    const chatHeader = document.getElementById('chatHeader');
    const chatSubtitle = document.getElementById('chatSubtitle');
    const messagesArea = document.getElementById('messagesArea');
    const lastCheckSpan = document.getElementById('lastCheck');
    const sendButton = document.getElementById('sendButton');
    const targetNumber = document.getElementById('targetNumber');
    const messageText = document.getElementById('messageText');
    
    // Elementos para upload de arquivos
    const fileInput = document.getElementById('fileInput');
    const attachBtn = document.getElementById('attachBtn');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const removeFileBtn = document.getElementById('removeFileBtn');
    
    // Modal de imagem
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');

    let connected = false;
    let polling = null;
    let conversationsPolling = null;
    let messagesPolling = null;
    let selectedChatId = null;
    let currentContactName = null;
    let selectedFile = null;

    // Cache para evitar recriação desnecessária das mensagens (elimina o "balão atualizando")
    let lastMessagesCache = new Map(); // chatId -> stringified messages

    const formatTime = () => new Date().toLocaleTimeString('pt-BR');

    // Funções para modal de imagem
    function openImageModal(src) {
        modalImage.src = src;
        imageModal.style.display = 'flex';
    }

    function closeImageModal() {
        imageModal.style.display = 'none';
    }

    // Funções para upload de arquivos
    function handleFileSelect(event) {
        const file = event.target.files[0];
        console.log('Arquivo selecionado:', file ? file.name : 'nenhum');
        if (file) {
            selectedFile = file;
            fileName.textContent = file.name;
            filePreview.style.display = 'flex';
            console.log('selectedFile definido:', selectedFile.name);
        }
    }

    function removeSelectedFile() {
        selectedFile = null;
        fileInput.value = '';
        filePreview.style.display = 'none';
    }
    const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

    function setStatus(type, text, description) {
        statusBadge.textContent = text;
        statusBadge.className = `status-badge ${type}`;
        statusDescription.textContent = description;
    }

    function setQrImage(src) {
        qrBox.innerHTML = `<img src="${src}" alt="QR Code" style="max-width:180px; border-radius: 16px;">`;
    }

    function clearQr() {
        qrBox.innerHTML = '<div id="qrPlaceholder">📷 QR Code aparecerá aqui</div>';
    }

    async function fetchJson(url, options = {}) {
        const res = await fetch(url, options);
        return res.json();
    }

    async function startNodeServer() {
        try {
            const result = await fetchJson(`${NODE_CONTROL_API}?action=start`);
            if (!result.success) {
                setStatus('disconnected', 'Erro no servidor', result.message || 'Falha ao iniciar');
                return false;
            }
            setStatus('connecting', 'Iniciando motor', 'Aguardando o servidor interno...');
            for (let i = 0; i < 12; i++) {
                await delay(1000);
                try {
                    const status = await fetchJson(`${NODE_CONTROL_API}?action=status`);
                    if (status.running) return true;
                } catch(e) {}
            }
            setStatus('disconnected', 'Servidor offline', 'Node.js não respondeu a tempo');
            return false;
        } catch (error) {
            setStatus('disconnected', 'Erro boot', 'Não foi possível iniciar o servidor');
            return false;
        }
    }

    async function ensureNodeRunning() {
        try {
            const status = await fetchJson(`${NODE_CONTROL_API}?action=status`);
            if (status.running) return true;
            return await startNodeServer();
        } catch {
            return await startNodeServer();
        }
    }

    async function updateStatus() {
        try {
            const data = await fetchJson(`${API_BASE}/status`);
            connected = data.connected;
            lastCheckSpan.textContent = formatTime();
            if (connected) {
                setStatus('connected', '✅ Conectado', 'WhatsApp ativo e pronto para conversar.');
                clearQr();
                await loadConversations();
            } else if (data.qrCode) {
                setStatus('connecting', '📲 Aguardando QR', 'Escaneie o código com o WhatsApp');
                setQrImage(data.qrCode);
            } else {
                setStatus('disconnected', '⛔ Desconectado', 'QR não disponível. Reinicie se necessário.');
                clearQr();
                conversationsList.innerHTML = '<div class="empty-state">Nenhuma conversa carregada</div>';
            }
            return true;
        } catch (err) {
            setStatus('disconnected', '📡 Servidor inativo', 'Clique em "Conectar" para iniciar');
            clearQr();
            conversationsList.innerHTML = '<div class="empty-state">Servidor indisponível</div>';
            return false;
        }
    }

    async function loadConversations() {
        if (!connected) return;
        try {
            const list = await fetchJson(`${API_BASE}/conversations`);
            conversationsList.innerHTML = '';
            if (!list.length) {
                conversationsList.innerHTML = '<div class="empty-state">📭 Nenhuma conversa encontrada</div>';
                return;
            }
            list.forEach(conv => {
                const div = document.createElement('div');
                div.className = 'conversation-item';
                if (selectedChatId === conv.id) div.classList.add('active');
                div.innerHTML = `
                    <div class="conv-name"><strong>${escapeHtml(conv.name || conv.id)}</strong></div>
                    <div class="conv-preview">${escapeHtml(conv.lastMessage?.substring(0, 35) || 'Nova conversa')}</div>
                `;
                div.addEventListener('click', () => selectConversation(conv.id, conv.name || conv.id, div));
                conversationsList.appendChild(div);
            });
        } catch (err) {
            console.error('Erro conversas', err);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    async function selectConversation(contactId, contactName, element) {
        document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
        if (element) element.classList.add('active');
        selectedChatId = contactId;
        currentContactName = contactName;
        targetNumber.value = '';
        chatHeader.textContent = contactName.length > 25 ? contactName.slice(0,25)+'…' : contactName;
        chatSubtitle.textContent = `Conversa ativa com ${contactName}`;
        await loadMessages(contactId);
        startMessagesPolling(contactId);
    }

    async function loadMessages(contactId) {
        if (!contactId) return;
        try {
            const list = await fetchJson(`${API_BASE}/messages/${encodeURIComponent(contactId)}`);
            const newCacheKey = JSON.stringify(list.map(m => ({ body: m.body, timestamp: m.timestamp, type: m.type })));
            const oldCache = lastMessagesCache.get(contactId);
            
            // Se não houve alteração nas mensagens, não recria o DOM (evita flickering)
            if (oldCache === newCacheKey) return;
            
            // Atualiza cache e renderiza
            lastMessagesCache.set(contactId, newCacheKey);
            messagesArea.innerHTML = '';
            if (!list.length) {
                messagesArea.innerHTML = '<div class="empty-state">📨 Nenhuma mensagem ainda. Envie algo!</div>';
                return;
            }
            list.forEach(msg => {
                const msgDiv = document.createElement('div');
                msgDiv.className = `message ${msg.type === 'sent' ? 'sent' : 'received'}`;
                const timeStr = msg.timestamp ? new Date(msg.timestamp * 1000).toLocaleTimeString('pt-BR') : new Date().toLocaleTimeString();
                
                let content = '';
                
                // Verificar se é mensagem com mídia
                if (msg.media && msg.media.data) {
                    const mimeType = msg.media.mimetype;
                    const base64Data = msg.media.data;
                    const filename = msg.media.filename || 'arquivo';
                    
                    if (mimeType.startsWith('image/')) {
                        content += `<img src="data:${mimeType};base64,${base64Data}" alt="${filename}" class="message-image" onclick="openImageModal(this.src)" />`;
                    } else if (mimeType.startsWith('video/')) {
                        content += `<video controls class="message-video"><source src="data:${mimeType};base64,${base64Data}" type="${mimeType}"></video>`;
                    } else if (mimeType.startsWith('audio/')) {
                        content += `<audio controls class="message-audio"><source src="data:${mimeType};base64,${base64Data}" type="${mimeType}"></audio>`;
                    } else {
                        content += `<div class="message-file"><a href="data:${mimeType};base64,${base64Data}" download="${filename}">📎 ${filename}</a></div>`;
                    }
                }
                
                // Adicionar texto da mensagem se existir
                if (msg.body && msg.body.trim()) {
                    content += `<div>${escapeHtml(msg.body)}</div>`;
                }
                
                // Se não há conteúdo, mostrar placeholder
                if (!content) {
                    content = '<div class="message-placeholder">📄 Mensagem vazia</div>';
                }
                
                msgDiv.innerHTML = `${content}<time>${timeStr}</time>`;
                messagesArea.appendChild(msgDiv);
            });
            messagesArea.scrollTop = messagesArea.scrollHeight;
        } catch (err) {
            console.error('Erro mensagens', err);
        }
    }

    function startMessagesPolling(contactId) {
        if (messagesPolling) clearInterval(messagesPolling);
        messagesPolling = setInterval(async () => {
            if (selectedChatId === contactId && connected) {
                await loadMessages(contactId);
            }
        }, 2500);
    }

    function stopMessagesPolling() {
        if (messagesPolling) {
            clearInterval(messagesPolling);
            messagesPolling = null;
        }
    }

    async function sendMessage() {
        const manualTo = targetNumber.value.trim();
        const to = selectedChatId || manualTo;
        const text = messageText.value.trim();

        console.log('=== SEND MESSAGE DEBUG ===');
        console.log('manualTo:', manualTo);
        console.log('selectedChatId:', selectedChatId);
        console.log('to:', to);
        console.log('text:', text ? `"${text}"` : 'empty');
        console.log('selectedFile:', selectedFile ? `File: ${selectedFile.name} (${selectedFile.size} bytes)` : 'null');
        console.log('connected:', connected);

        if (!to || (!text && !selectedFile)) {
            console.log('Bloqueado: to=', !!to, 'text=', !!text, 'selectedFile=', !!selectedFile);
            if (!to) {
                targetNumber.focus();
            } else if (!text && !selectedFile) {
                messageText.focus();
            }
            return;
        }
        if (!connected) {
            alert('❌ Aguarde a conexão do WhatsApp via QR.');
            return;
        }
        sendButton.disabled = true;
        sendButton.innerHTML = '⏳ Enviando...';
        try {
            const formData = new FormData();
            formData.append('to', to);
            if (text) {
                formData.append('message', text);
                console.log('Enviando com texto:', text);
            }
            if (selectedFile) {
                formData.append('media', selectedFile);
                console.log('Enviando com arquivo:', selectedFile.name, 'Tamanho:', selectedFile.size, 'Tipo:', selectedFile.type);
            }

            console.log('Enviando FormData para:', `${API_BASE}/send`);
            const response = await fetch(`${API_BASE}/send`, {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Resposta do servidor:', result);

            if (result.success) {
                messageText.value = '';
                removeSelectedFile();
                autoResizeTextarea();
                if (selectedChatId && selectedChatId === to) {
                    // Limpa o cache forçando recarga
                    lastMessagesCache.delete(to);
                    await loadMessages(to);
                } else if (!selectedChatId && manualTo) {
                    await loadConversations();
                }
                messageText.focus();
            } else {
                alert('Erro: ' + (result.error || 'Falha no envio'));
            }
        } catch (error) {
            alert('Falha na conexão: ' + error.message);
        } finally {
            sendButton.disabled = false;
            sendButton.innerHTML = '<span>✈️</span> Enviar';
        }
    }

    function autoResizeTextarea() {
        if (messageText) {
            messageText.style.height = 'auto';
            messageText.style.height = Math.min(messageText.scrollHeight, 100) + 'px';
        }
    }

    async function connectWhatsApp() {
        setStatus('connecting', '🔄 Iniciando...', 'Ligando o serviço WhatsApp');
        const started = await ensureNodeRunning();
        if (started) {
            await updateStatus();
            if (!polling) polling = setInterval(updateStatus, 4000);
            if (!conversationsPolling) conversationsPolling = setInterval(() => { if(connected) loadConversations(); }, 5000);
        }
    }

    async function startServer() {
        setStatus('connecting', '⚡ Ligando servidor', 'Verificando status atual...');
        try {
            // Primeiro, verificar se já está rodando
            try {
                const checkResponse = await fetch(`${API_BASE}/status`, {
                    method: 'HEAD',
                    signal: AbortSignal.timeout(2000)
                });
                if (checkResponse.ok) {
                    alert('✅ Servidor já está rodando!');
                    await updateStatus();
                    return;
                }
            } catch (e) {
                // Servidor não está respondendo, vamos iniciar
            }

            setStatus('connecting', '⚡ Ligando servidor', 'Executando: node server.js');

            // Enviar comando para iniciar
            const response = await fetch('server_control.php?action=start');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            // Aguardar o processo iniciar
            await delay(3000);

            // Fazer polling até o servidor responder (máximo 30 segundos)
            let attempts = 0;
            const maxAttempts = 60; // 60 tentativas = 30 segundos

            setStatus('connecting', '⏳ Aguardando servidor', `Tentativa ${attempts + 1}/${maxAttempts}...`);

            while (attempts < maxAttempts) {
                try {
                    const statusCheck = await fetch(`${API_BASE}/status`, {
                        method: 'HEAD',
                        signal: AbortSignal.timeout(1000) // Timeout de 1 segundo por tentativa
                    });

                    if (statusCheck.ok || statusCheck.status === 404) {
                        // Servidor respondeu!
                        setStatus('connected', '✅ Servidor ligado', 'Pronto para usar');
                        await delay(1000);
                        await updateStatus();
                        if (!polling) polling = setInterval(updateStatus, 4000);
                        if (!conversationsPolling) conversationsPolling = setInterval(() => { if(connected) loadConversations(); }, 5000);
                        alert('✅ Servidor iniciado com sucesso!\n\nAgora clique em "📲 Conectar" para conectar o WhatsApp.');
                        return;
                    }
                } catch (e) {
                    // Servidor ainda não respondeu, continua tentando
                }

                attempts++;
                if (attempts % 10 === 0) {
                    setStatus('connecting', '⏳ Aguardando servidor', `Tentativa ${attempts}/${maxAttempts}...`);
                }

                await delay(500);
            }

            // Se chegou aqui, servidor não respondeu a tempo
            setStatus('disconnected', '❌ Timeout', 'Servidor não respondeu');
            alert('⏱️ Servidor iniciou mas não respondeu em 30 segundos.\n\nVerifique se o Node.js está instalado e tente novamente.');
            
        } catch (err) {
            console.error('Erro ao ligar servidor:', err);
            setStatus('disconnected', '❌ Falha', err.message);
            alert('❌ Erro ao ligar servidor: ' + err.message);
        }
    }

    async function restartWhatsAppServer() {
        setStatus('connecting', '🔄 Reiniciando motor', 'Recarregando servidor...');
        try {
            const result = await fetchJson(`${NODE_CONTROL_API}?action=restart`);
            if (!result.success) throw new Error(result.message);
            await delay(1500);
            await updateStatus();
            if (!polling) polling = setInterval(updateStatus, 4000);
            if (!conversationsPolling) conversationsPolling = setInterval(() => { if(connected) loadConversations(); }, 5000);
        } catch (err) {
            setStatus('disconnected', 'Falha restart', err.message);
            alert('Erro ao reiniciar servidor: ' + err.message);
        }
    }

    async function disconnectWhatsApp() {
        try {
            await fetchJson(`${API_BASE}/disconnect`, { method: 'POST' });
            connected = false;
            setStatus('disconnected', '🔌 Desconectado', 'Sessão encerrada. Clique em Conectar para novo QR.');
            clearQr();
            conversationsList.innerHTML = '<div class="empty-state">Sessão finalizada</div>';
            messagesArea.innerHTML = '<div class="empty-state">💬 Conecte novamente para conversar</div>';
            chatHeader.textContent = 'Nenhum contato';
            chatSubtitle.textContent = 'Digite um número ou selecione conversa';
            selectedChatId = null;
            lastMessagesCache.clear();
            if (polling) clearInterval(polling);
            if (conversationsPolling) clearInterval(conversationsPolling);
            stopMessagesPolling();
            polling = conversationsPolling = null;
        } catch (err) { console.error(err); }
    }

    messageText.addEventListener('input', autoResizeTextarea);
    messageText.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    refreshButton.addEventListener('click', updateStatus);
    startServerButton.addEventListener('click', startServer);
    restartButton.addEventListener('click', restartWhatsAppServer);
    connectButton.addEventListener('click', connectWhatsApp);
    disconnectButton.addEventListener('click', disconnectWhatsApp);
    sendButton.addEventListener('click', sendMessage);
    
    // File upload events
    attachBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFileSelect);
    removeFileBtn.addEventListener('click', removeSelectedFile);

    window.addEventListener('load', async () => {
        const started = await ensureNodeRunning();
        if (started) {
            await updateStatus();
            polling = setInterval(updateStatus, 5000);
            conversationsPolling = setInterval(() => { if(connected) loadConversations(); }, 6000);
        }
    });
</script>
</body>
</html>