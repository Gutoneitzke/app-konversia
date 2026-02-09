// Script de teste para envio de mensagens WhatsApp
// Execute com: node test-send.js

const axios = require('axios')

const WHATSAPP_SERVICE_URL = 'http://localhost:3001'

console.log('🧪 WhatsApp Service - Script de Teste')
console.log('💡 Modo de conexão:', process.env.CONNECTION_MODE || 'ultra-safe (recomendado)')
console.log('📡 Service URL:', WHATSAPP_SERVICE_URL)
console.log('⚠️ IMPORTANTE: Certifique-se de que não há WhatsApp Web/App conectados!')
console.log('');

async function testMessageSending() {
    try {
        console.log('🧪 Iniciando testes de envio de mensagens...\n')

        // Teste 1: Verificar status da sessão
        console.log('1️⃣ Verificando status da sessão...')
        const sessionId = 'e2158164-7678-4a45-a917-95d9ad370b42'
        const statusResponse = await axios.get(`${WHATSAPP_SERVICE_URL}/status/${sessionId}`)
        console.log('Status:', statusResponse.data)

        if (!statusResponse.data.connected) {
            console.log('⚠️ Sessão não está conectada.')
            console.log('💡 Execute: npm start (para iniciar com recuperação automática)')
            console.log('💡 Ou conecte via interface web do Laravel')
            return
        }

        if (!statusResponse.data.connected) {
            console.log('❌ Sessão não está conectada. Conecte primeiro.')
            return
        }

        // Teste 2: Enviar mensagem de texto
        console.log('\n2️⃣ Enviando mensagem de texto...')
        const testPhone = '5511999999999' // SUBSTITUA pelo seu número de teste

        const textMessage = {
            session_id: 'e2158164-7678-4a45-a917-95d9ad370b42',
            to: testPhone,
            message: `🧪 Teste automático - ${new Date().toLocaleString()}`,
            type: 'text'
        }

        const sendResponse = await axios.post(`${WHATSAPP_SERVICE_URL}/send`, textMessage)
        console.log('✅ Mensagem enviada:', sendResponse.data)

        // Teste 3: Tentar enviar para número inválido (deve falhar)
        console.log('\n3️⃣ Testando validação de erro...')
        try {
            await axios.post(`${WHATSAPP_SERVICE_URL}/send`, {
                session_id: 'e2158164-7678-4a45-a917-95d9ad370b42',
                to: 'numero-invalido',
                message: '',
                type: 'text'
            })
        } catch (error) {
            console.log('✅ Validação funcionou:', error.response?.data?.error)
        }

        console.log('\n🎉 Todos os testes concluídos!')

    } catch (error) {
        console.error('❌ Erro no teste:', error.response?.data || error.message)
    }
}

// Executar teste se chamado diretamente
if (require.main === module) {
    testMessageSending()
}

module.exports = { testMessageSending }
