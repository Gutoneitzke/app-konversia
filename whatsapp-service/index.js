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
    
    if (!session_id || !to || !message) {
      return res.status(400).json({ error: 'session_id, to e message são obrigatórios' })
    }

    const result = await sendMessage(session_id, to, message, type || 'text')
    res.json({ status: 'sent', message_id: result.key.id })
  } catch (error) {
    res.status(500).json({ error: error.message })
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
