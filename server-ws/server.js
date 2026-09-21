const http = require('http');
const url = require('url');
const { WebSocketServer, WebSocket } = require('ws');

const PORT = process.env.PORT || 3001;
const BIND_HOST = '127.0.0.1';
const SHARED_SECRET = process.env.PULSE_WS_SECRET || 'pulse_internal_ws_secret_key_2026';

// Mapeamento de salas por Loja: Map<lojaId, Set<WebSocket>>
const rooms = new Map();

// Servidor HTTP Base
const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;

    // Rota de Health Check
    if (req.method === 'GET' && (pathname === '/health' || pathname === '/ws/health')) {
        let totalConns = 0;
        rooms.forEach(set => totalConns += set.size);

        res.writeHead(200, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({
            status: 'ok',
            uptime: Math.round(process.uptime()),
            total_connections: totalConns,
            total_rooms: rooms.size,
            memory_mb: Math.round(process.memoryUsage().rss / 1024 / 1024),
            timestamp: new Date().toISOString()
        }));
    }

    // Rota de Publicação de Eventos (chamada pelo Yii2 em localhost)
    if (req.method === 'POST' && (pathname === '/publish' || pathname === '/ws/publish')) {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', () => {
            try {
                const data = JSON.parse(body || '{}');

                if (data.secret !== SHARED_SECRET) {
                    res.writeHead(403, { 'Content-Type': 'application/json' });
                    return res.end(JSON.stringify({ success: false, error: 'Acesso negado: secret inválido' }));
                }

                const lojaId = String(data.loja_id || '');
                const event = data.event || {};

                if (!lojaId) {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    return res.end(JSON.stringify({ success: false, error: 'loja_id obrigatório' }));
                }

                const clientSet = rooms.get(lojaId);
                let sentCount = 0;

                if (clientSet && clientSet.size > 0) {
                    const payloadStr = JSON.stringify(event);
                    clientSet.forEach(ws => {
                        if (ws.readyState === WebSocket.OPEN) {
                            ws.send(payloadStr);
                            sentCount++;
                        }
                    });
                }

                res.writeHead(200, { 'Content-Type': 'application/json' });
                return res.end(JSON.stringify({
                    success: true,
                    loja_id: lojaId,
                    clients_notified: sentCount,
                    total_clients_in_room: clientSet ? clientSet.size : 0
                }));
            } catch (err) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                return res.end(JSON.stringify({ success: false, error: 'JSON inválido: ' + err.message }));
            }
        });
        return;
    }

    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Rota não encontrada' }));
});

// Servidor WebSocket acoplado
const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (request, socket, head) => {
    const parsedUrl = url.parse(request.url, true);
    const pathname = parsedUrl.pathname;

    // Aceita conexões em / ou /ws ou /ws/
    if (pathname === '/' || pathname === '/ws' || pathname === '/ws/') {
        const lojaId = String(parsedUrl.query.loja_id || '');
        if (!lojaId) {
            socket.write('HTTP/1.1 400 Bad Request\r\n\r\nLoja ID obrigatorio');
            socket.destroy();
            return;
        }

        wss.handleUpgrade(request, socket, head, (ws) => {
            wss.emit('connection', ws, request, lojaId);
        });
    } else {
        socket.destroy();
    }
});

wss.on('connection', (ws, request, lojaId) => {
    ws.isAlive = true;
    ws.lojaId = lojaId;

    // Adiciona na sala da loja
    if (!rooms.has(lojaId)) {
        rooms.set(lojaId, new Set());
    }
    rooms.get(lojaId).add(ws);

    // Heartbeat pong
    ws.on('pong', () => {
        ws.isAlive = true;
    });

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message.toString());
            // Responde a ping do cliente se enviado como JSON
            if (data.type === 'ping') {
                ws.send(JSON.stringify({ type: 'pong', ts: Date.now() }));
            }
        } catch (e) {
            // Ignora mensagens mal formatadas
        }
    });

    ws.on('close', () => {
        const set = rooms.get(ws.lojaId);
        if (set) {
            set.delete(ws);
            if (set.size === 0) {
                rooms.delete(ws.lojaId);
            }
        }
    });

    ws.on('error', (err) => {
        console.error(`[WS Error Loja ${lojaId}]:`, err.message);
    });

    // Envia confirmação de boas-vindas
    ws.send(JSON.stringify({
        type: 'conectado',
        loja_id: lojaId,
        mensagem: 'Conexão em tempo real estabelecida com sucesso.',
        ts: Date.now()
    }));
});

// Intervalo de verificação de conexões inativas (Ping/Pong a cada 30 segundos)
const heartbeatInterval = setInterval(() => {
    wss.clients.forEach(ws => {
        if (ws.isAlive === false) {
            return ws.terminate();
        }
        ws.isAlive = false;
        ws.ping();
    });
}, 30000);

wss.on('close', () => {
    clearInterval(heartbeatInterval);
});

server.listen(PORT, BIND_HOST, () => {
    console.log(`[Pulse WebSocket Broker] Rodando em http://${BIND_HOST}:${PORT}`);
    console.log(`[Pulse WebSocket Broker] Rota WSS: ws://${BIND_HOST}:${PORT}/ws/?loja_id=<id>`);
});
