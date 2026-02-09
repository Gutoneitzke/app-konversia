#!/usr/bin/env node

// Script para verificar status do WhatsApp Service
// Execute com: node check-status.js

const axios = require('axios')

const WHATSAPP_SERVICE_URL = 'http://localhost:3001'

async function checkStatus() {
    console.log('🔍 Verificando status do WhatsApp Service...\n')

    try {
        // Verificar se o serviço está rodando
        console.log('1️⃣ Verificando conectividade do serviço...')
        const healthResponse = await axios.get(`${WHATSAPP_SERVICE_URL}/status/test`, { timeout: 5000 })
            .catch(() => ({ data: { error: 'Serviço não responde' } }))

        if (healthResponse.data.error) {
            console.log('❌ Serviço não está rodando ou não responde')
            console.log('💡 Execute: npm start')
            return
        }

        console.log('✅ Serviço está rodando')

        // Verificar configurações
        console.log('\n2️⃣ Verificando configurações ativas...')

        // Tentar uma requisição de status (usando um ID de teste)
        const testSessionId = 'status-check-' + Date.now()
        const statusResponse = await axios.get(`${WHATSAPP_SERVICE_URL}/status/${testSessionId}`, { timeout: 5000 })
            .catch(err => ({ data: { error: 'Erro na requisição' } }))

        console.log('📊 Status da API:', statusResponse.data)

        // Verificar conflitos recentes (se houver endpoint)
        console.log('\n3️⃣ Verificando saúde geral...')

        // Simulação de verificação de conflitos (olhando logs seria melhor)
        console.log('💡 Para ver conflitos em tempo real, monitore os logs do serviço')
        console.log('💡 Procure por mensagens como:')
        console.log('   - [CONFLICTS] X conflitos na última hora')
        console.log('   - 🚨 ALERTA CRÍTICO: MÚLTIPLOS CONFLITOS')

        console.log('\n✅ Verificação concluída!')

    } catch (error) {
        console.error('❌ Erro na verificação:', error.message)
    }
}

// Executar se chamado diretamente
if (require.main === module) {
    checkStatus()
}

module.exports = { checkStatus }
