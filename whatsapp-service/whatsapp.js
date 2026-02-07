const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason
} = require('@whiskeysockets/baileys')

const { Boom } = require('@hapi/boom')
const qrcode = require('qrcode')
const axios = require('axios')

let sock

async function connect(sessionId) {
    const { state, saveCreds } = await useMultiFileAuthState(`sessions/${sessionId}`)

    sock = makeWASocket({
        auth: state,
        printQRInTerminal: false
    })

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('connection.update', async (update) => {
        const { connection, qr, lastDisconnect } = update

        if (qr) {
            const qrImage = await qrcode.toDataURL(qr)
            await axios.post('http://laravel.test/api/whatsapp/qr', {
                session_id: sessionId,
                qr: qrImage
            })
        }

        if (connection === 'close') {
            const shouldReconnect =
                (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut

            if (shouldReconnect) {
                connect(sessionId)
            }
        }
    })

    sock.ev.on('messages.upsert', async ({ messages }) => {
        const msg = messages[0]
        if (!msg.message || msg.key.fromMe) return

        await axios.post('http://laravel.test/api/whatsapp/message', {
            session_id: sessionId,
            from: msg.key.remoteJid,
            message: msg.message.conversation || ''
        })
    })
}

module.exports = { connect }
