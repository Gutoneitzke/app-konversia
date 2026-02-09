const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason
} = require('@whiskeysockets/baileys')

const { Boom } = require('@hapi/boom')
const qrcode = require('qrcode')
const axios = require('axios')
const path = require('path')
const fs = require('fs')
const pino = require('pino')

// Armazenar múltiplas sessões
const sessions = new Map()
const LARAVEL_API_URL = process.env.LARAVEL_URL || 'http://127.0.0.1';

async function connect(sessionId) {
    console.log(`Iniciando conexão para sessão: ${sessionId}`);
    console.log(`Laravel API URL: ${LARAVEL_API_URL}`);

    try {
        const sessionsDir = path.join(__dirname, 'sessions')
        if (!fs.existsSync(sessionsDir)) {
            fs.mkdirSync(sessionsDir, { recursive: true })
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

            if (qr) {
                const qrImage = await qrcode.toDataURL(qr)
                console.log('Tentando enviar QR para o Laravel...');
                await axios.post(`${LARAVEL_API_URL}/api/whatsapp/qr`, {
                    session_id: sessionId,
                    qr: qrImage
                }).then(() => console.log('QR enviado com sucesso!'))
                    .catch(err => console.error('Erro ao enviar QR:', err.message, err.response?.data))
            }

            if (connection === 'open') {
                await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
                    session_id: sessionId,
                    status: 'connected'
                }).catch(err => console.error('Erro ao atualizar status:', err.message))
            }

            if (connection === 'close') {
                const shouldReconnect =
                    (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut

                await axios.post(`${LARAVEL_API_URL}/api/whatsapp/status`, {
                    session_id: sessionId,
                    status: 'disconnected',
                    error: lastDisconnect?.error?.message || null
                }).catch(err => console.error('Erro ao atualizar status:', err.message))

                if (shouldReconnect) {
                    setTimeout(() => connect(sessionId), 3000) // Reconectar após 3s
                } else {
                    sessions.delete(sessionId)
                }
            }
        })

        sock.ev.on('messages.upsert', async ({ messages, type }) => {
            if (type !== 'notify') return

            for (const msg of messages) {
                if (!msg.message || msg.key.fromMe) continue

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

                await axios.post(`${LARAVEL_API_URL}/api/whatsapp/message`, messageData)
                    .catch(err => console.error('Erro ao enviar mensagem:', err.message))
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

    if (!sock) {
        throw new Error(`Sessão ${sessionId} não encontrada ou desconectada`)
    }

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

    const result = await sock.sendMessage(to, messagePayload)
    return result
}

function disconnect(sessionId) {
    const sock = sessions.get(sessionId)
    if (sock) {
        sock.end()
        sessions.delete(sessionId)
    }
}

function getSession(sessionId) {
    return sessions.get(sessionId)
}

module.exports = { connect, sendMessage, disconnect, getSession }
