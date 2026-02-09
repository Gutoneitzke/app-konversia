require('dotenv').config()
const express = require('express')
const { connect, sendMessage, disconnect, getSession } = require('./whatsapp')

const app = express()
app.use(express.json())

// Conectar número WhatsApp
app.post('/connect', async (req, res) => {
  try {
    const { session_id } = req.body

    if (!session_id) {
      return res.status(400).json({ error: 'session_id é obrigatório' })
    }

    await connect(session_id)
    res.json({ status: 'connecting', session_id })
  } catch (error) {
    res.status(500).json({ error: error.message })
  }
})

// Desconectar número WhatsApp
app.post('/disconnect', async (req, res) => {
  try {
    const { session_id } = req.body

    if (!session_id) {
      return res.status(400).json({ error: 'session_id é obrigatório' })
    }

    disconnect(session_id)
    res.json({ status: 'disconnected', session_id })
  } catch (error) {
    res.status(500).json({ error: error.message })
  }
})

// Enviar mensagem
app.post('/send', async (req, res) => {
  try {
    const { session_id, to, message, type } = req.body

    console.log(`[API] Recebida solicitação de envio:`, {
      session_id,
      to,
      message_length: message?.length || 0,
      type: type || 'text'
    })

    if (!session_id || !to || !message) {
      console.error(`[API] Dados inválidos na solicitação de envio:`, { session_id, to, message })
      return res.status(400).json({
        error: 'session_id, to e message são obrigatórios',
        received: { session_id, to, has_message: !!message }
      })
    }

    const result = await sendMessageWithRetry(session_id, to, message, type || 'text')

    console.log(`[API] Mensagem enviada com sucesso:`, {
      session_id,
      to,
      message_id: result.key.id,
      type: type || 'text'
    })

    res.json({
      status: 'sent',
      message_id: result.key.id,
      timestamp: new Date().toISOString()
    })
  } catch (error) {
    console.error(`[API] Erro ao enviar mensagem:`, {
      error: error.message,
      session_id: req.body.session_id,
      to: req.body.to,
      type: req.body.type || 'text'
    })

    // Determinar código de status apropriado
    let statusCode = 500
    if (error.message.includes('não encontrada')) {
      statusCode = 404
    } else if (error.message.includes('não está conectada')) {
      statusCode = 503 // Service Unavailable
    }

    res.status(statusCode).json({
      error: error.message,
      session_id: req.body.session_id,
      timestamp: new Date().toISOString()
    })
  }
})

// Verificar status da sessão
app.get('/status/:session_id', (req, res) => {
  const { session_id } = req.params
  const sock = getSession(session_id)

  res.json({
    session_id,
    connected: sock !== undefined,
    status: sock ? 'connected' : 'disconnected'
  })
})

app.listen(3001, () => {
  console.log('WhatsApp service running on port 3001')
})
