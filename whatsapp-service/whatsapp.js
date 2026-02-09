const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason
} = require('@whiskeysockets/baileys')

const { Boom } = require('@hapi/boom')
const qrcode = require('qrcode')
const axios = require('axios')
const path = require('path')

// Configurar timeout global para axios (10 segundos)
axios.defaults.timeout = 10000
const fs = require('fs')
const pino = require('pino')

// Armazenar múltiplas sessões
const sessions = new Map()
const LARAVEL_API_URL = process.env.LARAVEL_URL || 'http://127.0.0.1';

// Configurações baseadas no modo de conexão
const CONNECTION_MODE = process.env.CONNECTION_MODE || 'ultra-safe'
console.log(`[CONFIG] Modo de conexão: ${CONNECTION_MODE}`)

// Configurações por modo
const CONNECTION_CONFIGS = {
    normal: {
        MAX_RECONNECT_ATTEMPTS: 10,
        HEARTBEAT_INTERVAL: 30000, // 30 segundos
        MAX_HEARTBEAT_MISSES: 3,
        LOOP_DETECTION_WINDOW: 5 * 60 * 1000, // 5 minutos
        MAX_RECONNECTS_IN_WINDOW: 3,
        CONFLICT_BASE_DELAY: 30000, // 30 segundos
        CONFLICT_MAX_DELAY: 120000, // 2 minutos
        NORMAL_BASE_DELAY: 3000, // 3 segundos
        NORMAL_MAX_DELAY: 300000, // 5 minutos
        SESSION_MAX_AGE_HOURS: 168, // 7 dias
        CLEANUP_INTERVAL_HOURS: 6
    },
    conservative: {
        MAX_RECONNECT_ATTEMPTS: 5, // Menos tentativas
        HEARTBEAT_INTERVAL: 60000, // 1 minuto - menos frequente
        MAX_HEARTBEAT_MISSES: 5, // Mais tolerante
        LOOP_DETECTION_WINDOW: 10 * 60 * 1000, // 10 minutos
        MAX_RECONNECTS_IN_WINDOW: 2, // Mais restritivo
        CONFLICT_BASE_DELAY: 120000, // 2 minutos
        CONFLICT_MAX_DELAY: 600000, // 10 minutos
        NORMAL_BASE_DELAY: 10000, // 10 segundos
        NORMAL_MAX_DELAY: 600000, // 10 minutos
        SESSION_MAX_AGE_HOURS: 168, // 7 dias
        CLEANUP_INTERVAL_HOURS: 12 // Menos frequente
    },
    safe: {
        MAX_RECONNECT_ATTEMPTS: 3, // Muito conservador
        HEARTBEAT_INTERVAL: 120000, // 2 minutos
        MAX_HEARTBEAT_MISSES: 10, // Muito tolerante
        LOOP_DETECTION_WINDOW: 30 * 60 * 1000, // 30 minutos
        MAX_RECONNECTS_IN_WINDOW: 1, // Uma tentativa por janela
        CONFLICT_BASE_DELAY: 300000, // 5 minutos
        CONFLICT_MAX_DELAY: 1800000, // 30 minutos
        NORMAL_BASE_DELAY: 30000, // 30 segundos
        NORMAL_MAX_DELAY: 1800000, // 30 minutos
        SESSION_MAX_AGE_HOURS: 72, // 3 dias (mais conservador)
        CLEANUP_INTERVAL_HOURS: 24 // Uma vez por dia
    },
    'ultra-safe': {
        MAX_RECONNECT_ATTEMPTS: 2, // Mínimo possível
        HEARTBEAT_INTERVAL: 300000, // 5 minutos - muito paciente
        MAX_HEARTBEAT_MISSES: 20, // Extremamente tolerante
        LOOP_DETECTION_WINDOW: 60 * 60 * 1000, // 1 hora
        MAX_RECONNECTS_IN_WINDOW: 1, // Uma tentativa por hora
        CONFLICT_BASE_DELAY: 1800000, // 30 minutos
        CONFLICT_MAX_DELAY: 7200000, // 2 horas
        NORMAL_BASE_DELAY: 120000, // 2 minutos
        NORMAL_MAX_DELAY: 3600000, // 1 hora
        SESSION_MAX_AGE_HOURS: 48, // 2 dias - mais conservador
        CLEANUP_INTERVAL_HOURS: 48 // Limpeza a cada 2 dias
    }
}

// Aplicar configuração do modo selecionado
const CONFIG = CONNECTION_CONFIGS[CONNECTION_MODE] || CONNECTION_CONFIGS.normal

// Controle de estado das conexões
const connectionStates = new Map() // sessionId -> {isConnected, reconnectAttempts, lastHeartbeat, heartbeatInterval, lastReconnectTime, reconnectHistory}
const activeSessions = new Map() // phoneNumber -> sessionId (controle de exclusividade)
const sessionLocks = new Map() // sessionId -> {locked: boolean, lockedAt: timestamp, reason: string}

// Monitoramento de conflitos
let conflictStats = {
    totalConflicts: 0,
    recentConflicts: [], // timestamps dos últimos conflitos
    lastAlertTime: 0
}

// Função para inicializar estado da conexão
function initializeConnectionState(sessionId) {
    connectionStates.set(sessionId, {
        isConnected: false,
        reconnectAttempts: 0,
        lastHeartbeat: Date.now(),
        heartbeatInterval: null,
        lastConnectionUpdate: Date.now(),
        lastReconnectTime: 0,
        reconnectHistory: [] // Array de timestamps das últimas reconexões
    })
}

// Função para atualizar estado da conexão
function updateConnectionState(sessionId, updates) {
    const currentState = connectionStates.get(sessionId)
    if (currentState) {
        connectionStates.set(sessionId, { ...currentState, ...updates })
    }
}

// Sistema de sessão exclusiva
function acquireSessionLock(sessionId, reason = 'connection_attempt') {
    const existingLock = sessionLocks.get(sessionId)
    if (existingLock?.locked) {
        const lockAge = Date.now() - existingLock.lockedAt
        if (lockAge < 300000) { // 5 minutos
            console.log(`[${sessionId}] Lock ativo (${Math.round(lockAge/1000)}s) - ${existingLock.reason}`)
            return false
        } else {
            console.log(`[${sessionId}] Lock expirado, liberando...`)
            releaseSessionLock(sessionId)
        }
    }

    sessionLocks.set(sessionId, {
        locked: true,
        lockedAt: Date.now(),
        reason: reason
    })

    console.log(`[${sessionId}] Lock adquirido: ${reason}`)
    return true
}

function releaseSessionLock(sessionId) {
    sessionLocks.delete(sessionId)
    console.log(`[${sessionId}] Lock liberado`)
}

// Verificar se uma sessão pode ser iniciada (sem conflitos)
async function canStartSession(sessionId) {
    console.log(`[${sessionId}] 🔍 Verificando condições para iniciar sessão...`)

    // Verificar se já existe uma sessão ativa para este sessionId
    if (connectionStates.has(sessionId)) {
        const state = connectionStates.get(sessionId)
        if (state.isConnected) {
            console.log(`[${sessionId}] ❌ Sessão já conectada - abortando`)
            return false
        }

        // Verificar se houve muitas tentativas recentes (sinal de problemas)
        const recentAttempts = state.reconnectHistory.filter(time =>
            Date.now() - time < (10 * 60 * 1000) // Últimos 10 minutos
        ).length

        if (recentAttempts >= 3) {
            console.log(`[${sessionId}] ❌ Muitas tentativas recentes (${recentAttempts}) - aguardando cooldown`)
            return false
        }
    }

    // Verificar se há conflitos frequentes no sistema
    const recentConflicts = conflictStats.recentConflicts.length
    if (recentConflicts >= 10) {
        console.log(`[${sessionId}] ❌ Sistema em estado de alto conflito (${recentConflicts}) - abortando`)
        return false
    }

    // Tentar adquirir lock
    if (!acquireSessionLock(sessionId, 'session_start')) {
        console.log(`[${sessionId}] ❌ Não foi possível adquirir lock - possível conflito ativo`)
        return false
    }

    // Verificar status no servidor Laravel
    try {
        console.log(`[${sessionId}] 📡 Verificando status no servidor Laravel...`)
        const statusResponse = await axios.get(`${LARAVEL_API_URL}/api/whatsapp/status/${sessionId}`, {
            timeout: 5000,
            headers: {
                'User-Agent': 'WhatsApp-Service/1.0'
            }
        })

        if (statusResponse.data?.connected) {
            console.log(`[${sessionId}] ❌ Sessão já ativa no servidor - abortando para prevenir conflito`)
            releaseSessionLock(sessionId)
            return false
        }

        console.log(`[${sessionId}] ✅ Status verificado: desconectado (OK para conectar)`)
    } catch (error) {
        console.log(`[${sessionId}] ⚠️ Não foi possível verificar status no servidor (${error.code || 'unknown'})`)

        // Em modo ultra-safe, abortar se não conseguir verificar
        if (CONNECTION_MODE === 'ultra-safe') {
            console.log(`[${sessionId}] ❌ Modo ultra-safe: abortando por não conseguir verificar status`)
            releaseSessionLock(sessionId)
            return false
        }

        // Em outros modos, prosseguir mas com cautela
        console.log(`[${sessionId}] ⚠️ Prosseguindo sem verificação (modo: ${CONNECTION_MODE})`)
    }

    console.log(`[${sessionId}] ✅ Todas as verificações passaram - pronto para conectar`)
    return true
}

// Registrar sessão como ativa (exclusiva)
function registerActiveSession(sessionId, phoneNumber = null) {
    // Se temos um número de telefone, registrar para controle de exclusividade
    if (phoneNumber) {
        // Remover qualquer sessão anterior para este número
        const previousSession = activeSessions.get(phoneNumber)
        if (previousSession && previousSession !== sessionId) {
            console.log(`[${phoneNumber}] Removendo sessão anterior ${previousSession} para número`)
            // Não desconectar aqui, apenas registrar a nova
        }
        activeSessions.set(phoneNumber, sessionId)
    }

    console.log(`[${sessionId}] Sessão registrada como ativa`)
}

// Desregistrar sessão
function unregisterActiveSession(sessionId) {
    // Remover de activeSessions
    for (const [phone, sid] of activeSessions.entries()) {
        if (sid === sessionId) {
            activeSessions.delete(phone)
            console.log(`[${sessionId}] Removida sessão ativa para ${phone}`)
            break
        }
    }

    // Liberar lock
    releaseSessionLock(sessionId)
}

// Sistema de monitoramento de conflitos
function recordConflict(sessionId, reason) {
    const now = Date.now()
    conflictStats.totalConflicts++
    conflictStats.recentConflicts.push(now)

    // Manter apenas conflitos da última hora
    const oneHourAgo = now - (60 * 60 * 1000)
    conflictStats.recentConflicts = conflictStats.recentConflicts.filter(time => time > oneHourAgo)

    console.log(`[${sessionId}] 🔴 CONFLITO REGISTRADO: ${reason}`)
    console.log(`[CONFLICTS] Total: ${conflictStats.totalConflicts}, Última hora: ${conflictStats.recentConflicts.length}`)

    // Verificar se deve alertar
    checkConflictAlert()
}

function checkConflictAlert() {
    const now = Date.now()
    const recentConflicts = conflictStats.recentConflicts.length
    const timeSinceLastAlert = now - conflictStats.lastAlertTime

    // Alertar se:
    // - Mais de 5 conflitos na última hora E não alertou nas últimas 30 minutos
    if (recentConflicts >= 5 && timeSinceLastAlert > (30 * 60 * 1000)) {
        conflictStats.lastAlertTime = now

        console.error('🚨 ALERTA CRÍTICO: MÚLTIPLOS CONFLITOS DETECTADOS!')
        console.error(`🚨 ${recentConflicts} conflitos na última hora`)
        console.error('🚨 POSSÍVEIS CAUSAS:')
        console.error('   - Múltiplas conexões simultâneas do mesmo número')
        console.error('   - WhatsApp Web/App conectados simultaneamente')
        console.error('   - Reconexões muito frequentes')
        console.error('🚨 AÇÕES RECOMENDADAS:')
        console.error('   - Verificar WhatsApp Web e desconectar dispositivos')
        console.error('   - Aguardar 30+ minutos antes de reconectar')
        console.error('   - Usar apenas uma conexão por número')
        console.error('   - Considerar mudar para modo "ultra-safe"')

        // Em produção, isso poderia enviar email/SMS para administradores
    }
}

// Função para detectar loops de reconexão
function detectReconnectionLoop(sessionId) {
    const state = connectionStates.get(sessionId)
    if (!state) return false

    const now = Date.now()
    const windowStart = now - CONFIG.LOOP_DETECTION_WINDOW

    // Filtrar reconexões na janela de detecção
    const recentReconnects = state.reconnectHistory.filter(time => time > windowStart)

    // Se teve muitas reconexões recentes, é um loop
    if (recentReconnects.length >= CONFIG.MAX_RECONNECTS_IN_WINDOW) {
        console.error(`[${sessionId}] LOOP DE RECONEXÃO DETECTADO! ${recentReconnects.length} reconexões em ${CONFIG.LOOP_DETECTION_WINDOW/1000}s (${CONNECTION_MODE} mode)`)
        return true
    }

    return false
}

// Função para registrar tentativa de reconexão
function recordReconnectionAttempt(sessionId) {
    const state = connectionStates.get(sessionId)
    if (!state) return

    const now = Date.now()
    state.reconnectHistory.push(now)

    // Manter apenas as últimas 10 tentativas no histórico
    if (state.reconnectHistory.length > 10) {
        state.reconnectHistory = state.reconnectHistory.slice(-10)
    }

    state.lastReconnectTime = now
    updateConnectionState(sessionId, {
        reconnectHistory: state.reconnectHistory,
        lastReconnectTime: now
    })
}

// Função para iniciar heartbeat
function startHeartbeat(sessionId) {
    const state = connectionStates.get(sessionId)
    if (!state) return

    // Limpar intervalo anterior se existir
    if (state.heartbeatInterval) {
        clearInterval(state.heartbeatInterval)
    }

    state.heartbeatInterval = setInterval(async () => {
        const sock = sessions.get(sessionId)
        if (!sock) return

        try {
            // Verificar se a conexão ainda está ativa
            const isConnected = sock.ws?.readyState === 1 // OPEN state

            if (!isConnected) {
                console.log(`[${sessionId}] Conexão perdida detectada pelo heartbeat (${CONNECTION_MODE} mode)`)
                await handleDisconnection(sessionId, 'heartbeat_timeout')
                return
            }

            updateConnectionState(sessionId, { lastHeartbeat: Date.now() })
            if (CONNECTION_MODE === 'safe') {
                console.log(`[${sessionId}] Heartbeat OK - conexão ativa`)
            }

        } catch (error) {
            console.error(`[${sessionId}] Erro no heartbeat:`, error.message)
            await handleDisconnection(sessionId, 'heartbeat_error')
        }
    }, CONFIG.HEARTBEAT_INTERVAL)

    updateConnectionState(sessionId, { heartbeatInterval: state.heartbeatInterval })
}

// Função para parar heartbeat
function stopHeartbeat(sessionId) {
    const state = connectionStates.get(sessionId)
    if (state?.heartbeatInterval) {
        clearInterval(state.heartbeatInterval)
        updateConnectionState(sessionId, { heartbeatInterval: null })
    }
}

// Função para calcular delay de reconexão com backoff exponencial
function calculateReconnectDelay(attemptNumber, isConflict = false) {
    if (isConflict) {
        const conflictDelay = CONFIG.CONFLICT_BASE_DELAY * Math.pow(1.5, attemptNumber - 1)
        const jitter = Math.random() * 5000 // 5 segundos de jitter
        return Math.min(conflictDelay + jitter, CONFIG.CONFLICT_MAX_DELAY)
    }

    const exponentialDelay = CONFIG.NORMAL_BASE_DELAY * Math.pow(2, attemptNumber - 1)
    const jitter = Math.random() * 1000
    return Math.min(exponentialDelay + jitter, CONFIG.NORMAL_MAX_DELAY)
}

// Função para lidar com desconexões
async function handleDisconnection(sessionId, reason) {
    const state = connectionStates.get(sessionId)
    if (!state) return

    // Detectar se é um conflito de stream
    const isConflict = reason.includes('conflict') || reason.includes('Stream Errored')

    console.log(`[${sessionId}] Desconexão detectada - razão: ${reason}${isConflict ? ' (CONFLITO DETECTADO)' : ''}`)

    updateConnectionState(sessionId, {
        isConnected: false,
        lastConnectionUpdate: Date.now()
    })

    stopHeartbeat(sessionId)

    // Para conflitos, adicionar delay extra antes de qualquer ação
    if (isConflict) {
        console.log(`[${sessionId}] Conflito detectado - aguardando 10 segundos antes de prosseguir...`)
        await new Promise(resolve => setTimeout(resolve, 10000))

        // Registrar conflito para monitoramento
        recordConflict(sessionId, reason)
    }

    // Atualizar status no Laravel
    await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
        session_id: sessionId,
        status: 'disconnected',
        error: `Desconexão por ${reason}`
    }).catch(err => console.error('Erro ao atualizar status de desconexão:', err.message))

    // Verificar se está em loop de reconexão
    if (detectReconnectionLoop(sessionId)) {
        console.error(`[${sessionId}] LOOP DE RECONEXÃO DETECTADO - Abortando reconexões automáticas`)
        console.error(`[${sessionId}] POSSÍVEL CAUSA: Conflito de múltiplas conexões ou problema no dispositivo WhatsApp`)

        sessions.delete(sessionId)
        connectionStates.delete(sessionId)

        await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
            session_id: sessionId,
            status: 'loop_detected',
            error: `Loop de reconexão detectado - intervenção manual necessária`
        }).catch(err => console.error('Erro ao atualizar status de loop:', err.message))

        return
    }

    // Tentar reconectar se não excedeu o limite de tentativas
    if (state.reconnectAttempts < CONFIG.MAX_RECONNECT_ATTEMPTS) {
        state.reconnectAttempts++
        const delay = calculateReconnectDelay(state.reconnectAttempts, isConflict)

        // Registrar tentativa de reconexão
        recordReconnectionAttempt(sessionId)

        console.log(`[${sessionId}] Tentativa de reconexão ${state.reconnectAttempts}/${CONFIG.MAX_RECONNECT_ATTEMPTS} em ${Math.round(delay/1000)}s${isConflict ? ' (delay especial para conflito)' : ''} [${CONNECTION_MODE}]`)

        setTimeout(() => {
            console.log(`[${sessionId}] Executando reconexão tentativa ${state.reconnectAttempts}`)
            connect(sessionId)
        }, delay)
    } else {
        console.error(`[${sessionId}] Máximo de tentativas de reconexão atingido (${CONFIG.MAX_RECONNECT_ATTEMPTS}). Sessão removida. [${CONNECTION_MODE}]`)
        sessions.delete(sessionId)
        connectionStates.delete(sessionId)

        await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
            session_id: sessionId,
            status: 'failed',
            error: `Máximo de tentativas de reconexão atingido [${CONNECTION_MODE}]`
        }).catch(err => console.error('Erro ao atualizar status final:', err.message))
    }
}

async function connect(sessionId) {
    console.log(`[${sessionId}] Iniciando conexão para sessão`);
    console.log(`[${sessionId}] Laravel API URL: ${LARAVEL_API_URL}`);

    // Verificar se pode iniciar sessão (prevenir conflitos)
    const canStart = await canStartSession(sessionId)
    if (!canStart) {
        console.log(`[${sessionId}] Conexão abortada por controle de conflitos`)
        return
    }

    // Inicializar estado da conexão
    initializeConnectionState(sessionId)

    try {
        const sessionsDir = path.join(__dirname, 'sessions')
        const sessionPath = path.join(sessionsDir, sessionId)

        if (!fs.existsSync(sessionsDir)) {
            fs.mkdirSync(sessionsDir, { recursive: true })
        }

        // Verificar se a sessão existe e não está corrompida
        if (fs.existsSync(sessionPath)) {
            try {
                const files = fs.readdirSync(sessionPath)
                if (files.length === 0) {
                    console.log(`[${sessionId}] Diretório de sessão vazio, removendo...`)
                    fs.rmdirSync(sessionPath)
                } else {
                    // Verificar se os arquivos essenciais existem
                    const hasCreds = files.some(file => file.includes('creds'))
                    if (!hasCreds) {
                        console.log(`[${sessionId}] Arquivos de credenciais não encontrados, sessão possivelmente corrompida`)
                        // Não remover automaticamente, deixar o Baileys tentar recuperar
                    }
                }
            } catch (error) {
                console.error(`[${sessionId}] Erro ao verificar sessão existente:`, error.message)
            }
        }

        const { state, saveCreds } = await useMultiFileAuthState(`sessions/${sessionId}`)

        const sock = makeWASocket({
            auth: state,
            printQRInTerminal: false,
            logger: pino({ level: 'silent' }) // Reduzir logs
        })

        // Salvar sessão no Map
        sessions.set(sessionId, sock)

        sock.ev.on('creds.update', saveCreds)

        sock.ev.on('connection.update', async (update) => {
            const { connection, qr, lastDisconnect } = update

            console.log(`[${sessionId}] Connection update:`, {
                connection,
                hasQR: !!qr,
                hasLastDisconnect: !!lastDisconnect,
                error: lastDisconnect?.error?.message
            })

            if (qr) {
                const qrImage = await qrcode.toDataURL(qr)
                console.log(`[${sessionId}] Gerando QR e enviando para Laravel...`);
                await axios.post(`${LARAVEL_API_URL}/api/whatsapp/qr`, {
                    session_id: sessionId,
                    qr: qrImage
                }).then(() => console.log(`[${sessionId}] QR enviado com sucesso!`))
                    .catch(err => console.error(`[${sessionId}] Erro ao enviar QR:`, err.message, err.response?.data))
            }

            if (connection === 'open') {
                console.log(`[${sessionId}] Conexão estabelecida com sucesso`)

                updateConnectionState(sessionId, {
                    isConnected: true,
                    reconnectAttempts: 0, // Resetar contador de tentativas
                    lastConnectionUpdate: Date.now()
                })

                // Registrar sessão como ativa (exclusiva)
                registerActiveSession(sessionId)

                // Iniciar heartbeat
                startHeartbeat(sessionId)

                await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
                    session_id: sessionId,
                    status: 'connected'
                }).catch(err => console.error(`[${sessionId}] Erro ao atualizar status de conexão:`, err.message))
            }

            if (connection === 'close') {
                const shouldReconnect =
                    (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut

                console.log(`[${sessionId}] Conexão fechada. Should reconnect: ${shouldReconnect}`)

                if (shouldReconnect) {
                    await handleDisconnection(sessionId, `connection_closed: ${lastDisconnect?.error?.message || 'unknown'}`)
                } else {
                    console.log(`[${sessionId}] Logout detectado - removendo sessão`)
                    stopHeartbeat(sessionId)

                    // Desregistrar sessão ativa
                    unregisterActiveSession(sessionId)

                    sessions.delete(sessionId)
                    connectionStates.delete(sessionId)

                    await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
                        session_id: sessionId,
                        status: 'logged_out',
                        error: lastDisconnect?.error?.message || 'User logged out'
                    }).catch(err => console.error(`[${sessionId}] Erro ao atualizar status de logout:`, err.message))
                }
            }

            if (connection === 'connecting') {
                console.log(`[${sessionId}] Conectando...`)
                updateConnectionState(sessionId, { lastConnectionUpdate: Date.now() })
            }
        })

        sock.ev.on('messages.upsert', async ({ messages, type }) => {
            if (type !== 'notify') return

            console.log(`Recebidas ${messages.length} mensagens para processamento`)

            for (const msg of messages) {
                console.log(`Processando mensagem ${msg.key.id} de ${msg.key.remoteJid}`)
                if (!msg.message || msg.key.fromMe) {
                    console.log(`Mensagem ${msg.key.id} ignorada: ${!msg.message ? 'sem conteúdo' : 'mensagem própria'}`)
                    continue
                }

                // Extrair dados da mensagem
                const messageData = {
                    session_id: sessionId,
                    message_id: msg.key.id,
                    from: msg.key.remoteJid,
                    timestamp: msg.messageTimestamp,
                    message: extractMessageContent(msg.message),
                    type: getMessageType(msg.message),
                    metadata: extractMetadata(msg.message)
                }

                console.log(`Dados extraídos para ${msg.key.id}:`, {
                    from: messageData.from,
                    type: messageData.type,
                    hasContent: !!messageData.message,
                    timestamp: new Date(messageData.timestamp * 1000).toISOString()
                })

                // Sistema de retry com tratamento de erro adequado
                let retries = 3
                let success = false

                while (retries > 0 && !success) {
                    try {
                        await axios.post(`${LARAVEL_API_URL}/api/whatsapp/message`, messageData)
                        success = true
                        console.log(`Mensagem ${msg.key.id} processada com sucesso`)
                    } catch (err) {
                        retries--
                        console.error(`Erro ao enviar mensagem ${msg.key.id} (tentativa ${4 - retries}/3):`, err.message)

                        if (retries > 0) {
                            // Aguardar 1 segundo antes de tentar novamente
                            await new Promise(resolve => setTimeout(resolve, 1000))
                        } else {
                            console.error(`Falha definitiva ao processar mensagem ${msg.key.id} - mensagem perdida`)
                        }
                    }
                }
            }
        })

    } catch (error) {
        console.error(`Erro ao conectar sessão ${sessionId}:`, error.message)
        await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
            session_id: sessionId,
            status: 'error',
            error: error.message
        }).catch(() => { })
    }
}

function extractMessageContent(message) {
    if (message.conversation) return message.conversation
    if (message.extendedTextMessage?.text) return message.extendedTextMessage.text
    if (message.imageMessage?.caption) return message.imageMessage.caption
    if (message.videoMessage?.caption) return message.videoMessage.caption
    return null
}

function getMessageType(message) {
    if (message.conversation || message.extendedTextMessage) return 'text'
    if (message.imageMessage) return 'image'
    if (message.videoMessage) return 'video'
    if (message.audioMessage) return 'audio'
    if (message.documentMessage) return 'document'
    if (message.stickerMessage) return 'sticker'
    if (message.locationMessage) return 'location'
    if (message.contactMessage) return 'contact'
    return 'unknown'
}

function extractMetadata(message) {
    const metadata = {}

    if (message.imageMessage) {
        metadata.width = message.imageMessage.width
        metadata.height = message.imageMessage.height
        metadata.mimetype = message.imageMessage.mimetype
        metadata.url = message.imageMessage.url
    }

    if (message.videoMessage) {
        metadata.width = message.videoMessage.width
        metadata.height = message.videoMessage.height
        metadata.duration = message.videoMessage.seconds
        metadata.mimetype = message.videoMessage.mimetype
        metadata.url = message.videoMessage.url
    }

    if (message.audioMessage) {
        metadata.duration = message.audioMessage.seconds
        metadata.mimetype = message.audioMessage.mimetype
        metadata.ptt = message.audioMessage.ptt
    }

    if (message.documentMessage) {
        metadata.filename = message.documentMessage.fileName
        metadata.mimetype = message.documentMessage.mimetype
        metadata.url = message.documentMessage.url
    }

    if (message.locationMessage) {
        metadata.latitude = message.locationMessage.degreesLatitude
        metadata.longitude = message.locationMessage.degreesLongitude
    }

    return metadata
}

async function sendMessage(sessionId, to, message, type = 'text') {
    const sock = sessions.get(sessionId)
    const connectionState = connectionStates.get(sessionId)

    if (!sock) {
        throw new Error(`Sessão ${sessionId} não encontrada`)
    }

    // Verificar se a conexão está ativa
    if (!connectionState?.isConnected) {
        throw new Error(`Sessão ${sessionId} não está conectada ao WhatsApp`)
    }

    // Verificar se o WebSocket está ativo
    if (!sock.ws || sock.ws.readyState !== 1) {
        throw new Error(`Conexão WebSocket para sessão ${sessionId} não está ativa`)
    }

    // Validar dados específicos por tipo
    switch (type) {
        case 'image':
        case 'video':
        case 'audio':
        case 'document':
            if (!message.url) {
                throw new Error(`URL é obrigatória para mensagens do tipo ${type}`)
            }
            break
        case 'text':
            if (!message || message.trim().length === 0) {
                throw new Error('Mensagem de texto não pode estar vazia')
            }
            break
        default:
            if (!message || message.trim().length === 0) {
                throw new Error(`Conteúdo é obrigatório para mensagens do tipo ${type}`)
            }
    }

    console.log(`[${sessionId}] Enviando mensagem ${type} para ${to}`)

    let messagePayload = {}

    switch (type) {
        case 'text':
            messagePayload = { text: message }
            break
        case 'image':
            messagePayload = { image: { url: message.url }, caption: message.caption }
            break
        case 'video':
            messagePayload = { video: { url: message.url }, caption: message.caption }
            break
        case 'audio':
            messagePayload = { audio: { url: message.url }, ptt: message.ptt || false }
            break
        case 'document':
            messagePayload = { document: { url: message.url }, mimetype: message.mimetype, fileName: message.filename }
            break
        default:
            messagePayload = { text: message }
    }

    try {
        const result = await sock.sendMessage(to, messagePayload)
        console.log(`[${sessionId}] Mensagem enviada com sucesso. ID: ${result.key.id}`)
        return result
    } catch (error) {
        console.error(`[${sessionId}] Erro ao enviar mensagem:`, error.message)

        // Se for erro de conexão, marcar como desconectada
        if (error.message.includes('connection') || error.message.includes('WebSocket')) {
            console.log(`[${sessionId}] Erro de conexão detectado no envio, forçando reconexão`)
            await handleDisconnection(sessionId, `send_message_error: ${error.message}`)
        }

        throw error
    }
}

function disconnect(sessionId) {
    console.log(`[${sessionId}] Desconectando sessão manualmente`)

    const sock = sessions.get(sessionId)
    if (sock) {
        sock.end()
    }

    // Desregistrar sessão ativa
    unregisterActiveSession(sessionId)

    // Limpar estado da conexão
    stopHeartbeat(sessionId)
    sessions.delete(sessionId)
    connectionStates.delete(sessionId)
}

function getSession(sessionId) {
    return sessions.get(sessionId)
}

// Função para validar e formatar número de telefone
function formatPhoneNumber(phone) {
    // Remover todos os caracteres não numéricos
    let cleanNumber = phone.replace(/\D/g, '')

    // Adicionar @s.whatsapp.net se não tiver
    if (!cleanNumber.includes('@')) {
        // Se começar com 55 (Brasil), manter como está
        // Caso contrário, pode precisar de ajustes para outros países
        cleanNumber = `${cleanNumber}@s.whatsapp.net`
    }

    return cleanNumber
}

// Função para enviar mensagem com retry
async function sendMessageWithRetry(sessionId, to, message, type = 'text', maxRetries = 3) {
    const formattedTo = formatPhoneNumber(to)

    console.log(`[${sessionId}] Tentando enviar ${type} para ${formattedTo} (original: ${to})`)

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            const result = await sendMessage(sessionId, formattedTo, message, type)
            return result
        } catch (error) {
            console.error(`[${sessionId}] Tentativa ${attempt}/${maxRetries} falhou:`, error.message)

            // Se for a última tentativa, relançar o erro
            if (attempt === maxRetries) {
                throw new Error(`Falha após ${maxRetries} tentativas: ${error.message}`)
            }

            // Aguardar antes da próxima tentativa (backoff simples)
            const delay = Math.min(1000 * attempt, 5000) // 1s, 2s, 3s (máx 5s)
            console.log(`[${sessionId}] Aguardando ${delay}ms antes da próxima tentativa...`)
            await new Promise(resolve => setTimeout(resolve, delay))
        }
    }
}

// Monitoramento global de conexões
// Limpar locks expirados
function cleanupExpiredLocks() {
    const now = Date.now()
    const expiredLocks = []

    for (const [sessionId, lock] of sessionLocks.entries()) {
        if (lock.locked) {
            const lockAge = now - lock.lockedAt
            const maxLockAge = CONNECTION_MODE === 'ultra-safe' ? 1800000 : 600000 // 30min ou 10min

            if (lockAge > maxLockAge) {
                expiredLocks.push(sessionId)
            }
        }
    }

    for (const sessionId of expiredLocks) {
        console.log(`[${sessionId}] Limpando lock expirado`)
        sessionLocks.delete(sessionId)
    }

    if (expiredLocks.length > 0) {
        console.log(`[LOCKS] ${expiredLocks.length} locks expirados removidos`)
    }
}

function startGlobalMonitoring() {
    console.log(`[MONITOR] Iniciando monitoramento global [${CONNECTION_MODE}]`)

    setInterval(() => {
        const now = Date.now()

        // Limpar locks expirados
        cleanupExpiredLocks()

        for (const [sessionId, state] of connectionStates.entries()) {
            const sock = sessions.get(sessionId)

            // Verificar se a sessão ainda existe
            if (!sock) {
                console.log(`[${sessionId}] Sessão não encontrada, removendo do monitoramento`)
                unregisterActiveSession(sessionId)
                connectionStates.delete(sessionId)
                continue
            }

            // Verificar se está conectada mas o heartbeat falhou
            if (state.isConnected) {
                const timeSinceLastHeartbeat = now - state.lastHeartbeat
                const maxHeartbeatAge = CONFIG.HEARTBEAT_INTERVAL * CONFIG.MAX_HEARTBEAT_MISSES

                if (timeSinceLastHeartbeat > maxHeartbeatAge) {
                    console.log(`[${sessionId}] Heartbeat perdido por ${(timeSinceLastHeartbeat / 1000).toFixed(1)}s, forçando reconexão [${CONNECTION_MODE}]`)
                    handleDisconnection(sessionId, 'global_monitor_heartbeat_lost')
                }
            }

            // Verificar se há conexões "presas" em connecting por muito tempo
            if (!state.isConnected) {
                const timeSinceLastUpdate = now - state.lastConnectionUpdate
                const stuckThreshold = CONNECTION_MODE === 'ultra-safe' ? 600000 : 120000 // 10min ou 2min

                if (timeSinceLastUpdate > stuckThreshold) {
                    console.log(`[${sessionId}] Conexão presa por ${(timeSinceLastUpdate / 1000).toFixed(1)}s, forçando reconexão [${CONNECTION_MODE}]`)
                    handleDisconnection(sessionId, 'global_monitor_stuck_connection')
                }
            }
        }

        // Log de status geral
        if (connectionStates.size > 0) {
            const connectedCount = Array.from(connectionStates.values()).filter(s => s.isConnected).length
            const lockCount = Array.from(sessionLocks.values()).filter(lock => lock.locked).length
            console.log(`[MONITOR] ${connectedCount}/${connectionStates.size} conexões ativas, ${lockCount} locks ativos`)
        }

        // Verificar conflitos recentes
        if (conflictStats.recentConflicts.length > 0) {
            console.log(`[CONFLICTS] ${conflictStats.recentConflicts.length} conflitos na última hora`)
        }

    }, CONNECTION_MODE === 'ultra-safe' ? 120000 : 60000) // 2min ou 1min
}

// Função para verificar se uma sessão está saudável para recuperação
function isSessionHealthy(sessionId) {
    const sessionPath = path.join(__dirname, 'sessions', sessionId)

    try {
        // Verificar se o diretório existe
        if (!fs.existsSync(sessionPath)) {
            console.log(`[${sessionId}] Diretório de sessão não encontrado`)
            return false
        }

        const stats = fs.statSync(sessionPath)
        const ageInHours = (Date.now() - stats.mtime.getTime()) / (1000 * 60 * 60)

        // Verificar se não está muito antiga
        if (ageInHours > CONFIG.SESSION_MAX_AGE_HOURS) {
            console.log(`[${sessionId}] Sessão muito antiga (${ageInHours.toFixed(1)}h > ${CONFIG.SESSION_MAX_AGE_HOURS}h)`)
            return false
        }

        // Verificar se tem arquivos de credenciais
        const files = fs.readdirSync(sessionPath)
        const hasCreds = files.some(file => file.includes('creds'))

        if (!hasCreds) {
            console.log(`[${sessionId}] Arquivos de credenciais não encontrados`)
            return false
        }

        // Verificar se os arquivos não estão corrompidos (tamanho mínimo)
        const credsFile = files.find(file => file.includes('creds'))
        if (credsFile) {
            const credsPath = path.join(sessionPath, credsFile)
            const credsStats = fs.statSync(credsPath)

            // Arquivo de credenciais deve ter pelo menos 1KB
            if (credsStats.size < 1024) {
                console.log(`[${sessionId}] Arquivo de credenciais muito pequeno (${credsStats.size} bytes)`)
                return false
            }
        }

        return true
    } catch (error) {
        console.error(`[${sessionId}] Erro ao verificar saúde da sessão:`, error.message)
        return false
    }
}

// Função para recuperação automática de sessões
async function autoRecoverSessions() {
    console.log(`[RECOVERY] Iniciando recuperação automática de sessões [${CONNECTION_MODE}]`)

    try {
        const sessionsDir = path.join(__dirname, 'sessions')
        if (!fs.existsSync(sessionsDir)) {
            console.log('[RECOVERY] Diretório de sessões não encontrado')
            return
        }

        const sessionDirs = fs.readdirSync(sessionsDir)
        let recoveredCount = 0

        for (const sessionDir of sessionDirs) {
            const sessionId = sessionDir

            // Pular se já está conectada
            if (connectionStates.has(sessionId) && connectionStates.get(sessionId).isConnected) {
                continue
            }

            // Verificar se está saudável
            if (!isSessionHealthy(sessionId)) {
                console.log(`[RECOVERY] Pulando sessão não saudável: ${sessionId}`)
                continue
            }

            console.log(`[RECOVERY] Tentando recuperar sessão: ${sessionId}`)
            try {
                await connect(sessionId)
                recoveredCount++
                console.log(`[RECOVERY] Sessão ${sessionId} recuperada com sucesso`)

                // Aguardar um pouco entre recuperações para não sobrecarregar
                await new Promise(resolve => setTimeout(resolve, 2000))
            } catch (error) {
                console.error(`[RECOVERY] Falha ao recuperar ${sessionId}:`, error.message)
            }
        }

        console.log(`[RECOVERY] Recuperação concluída. ${recoveredCount} sessões recuperadas.`)

    } catch (error) {
        console.error('[RECOVERY] Erro na recuperação automática:', error)
    }
}

// Função para limpeza de sessões orfãs/antigas
function cleanupOrphanedSessions() {
    console.log('Verificando sessões orfãs...')

    try {
        const sessionsDir = path.join(__dirname, 'sessions')
        if (!fs.existsSync(sessionsDir)) return

        const sessionDirs = fs.readdirSync(sessionsDir)

        for (const sessionDir of sessionDirs) {
            const sessionId = sessionDir
            const sessionPath = path.join(sessionsDir, sessionDir)

            // Se não está na memória, pode ser orfã
            if (!sessions.has(sessionId) && !connectionStates.has(sessionId)) {
                try {
                    const stats = fs.statSync(sessionPath)
                    const ageInHours = (Date.now() - stats.mtime.getTime()) / (1000 * 60 * 60)

                    // Remover sessões antigas sem atividade
                    if (ageInHours > CONFIG.SESSION_MAX_AGE_HOURS) {
                        console.log(`[${sessionId}] Removendo sessão orfã com ${ageInHours.toFixed(1)}h de idade`)
                        fs.rmSync(sessionPath, { recursive: true, force: true })
                    }
                } catch (error) {
                    console.error(`[${sessionId}] Erro ao verificar sessão orfã:`, error.message)
                }
            }
        }
    } catch (error) {
        console.error('Erro na limpeza de sessões orfãs:', error.message)
    }
}

// Iniciar monitoramento quando o módulo for carregado
setTimeout(() => {
    startGlobalMonitoring()

    // Aguardar mais um pouco antes de tentar recuperação automática
    setTimeout(async () => {
        await autoRecoverSessions()
        cleanupOrphanedSessions()

        // Executar limpeza baseada na configuração
        setInterval(cleanupOrphanedSessions, CONFIG.CLEANUP_INTERVAL_HOURS * 60 * 60 * 1000)
    }, 10000) // 10 segundos após o monitoramento
}, 5000)

module.exports = { connect, sendMessage, sendMessageWithRetry, disconnect, getSession }
