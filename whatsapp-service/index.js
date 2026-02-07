const express = require('express')
const { connect } = require('./whatsapp')

const app = express()
app.use(express.json())

app.post('/connect', (req, res) => {
  const { session_id } = req.body
  connect(session_id)
  res.json({ status: 'connecting' })
})

app.post('/send', async (req, res) => {
  const { to, message } = req.body
  await sock.sendMessage(to, { text: message })
  res.json({ status: 'sent' })
})

app.listen(3001, () => {
  console.log('WhatsApp service running on port 3001')
})