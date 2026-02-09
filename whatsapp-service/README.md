# WhatsApp Service - Sistema de Reconexão Automática

Este serviço implementa um sistema robusto de reconexão automática, recuperação inteligente e envio confiável de mensagens para garantir que as conexões WhatsApp permaneçam ativas 24/7 com máxima segurança e estabilidade.

## 📤 Sistema de Envio de Mensagens

### Recursos de Envio:
- **Retry Automático**: Até 3 tentativas com backoff inteligente
- **Validação de Conexão**: Verifica se WhatsApp está conectado antes de enviar
- **Formatação de Números**: Adiciona automaticamente @s.whatsapp.net
- **Validação de Dados**: Verifica se todos os campos obrigatórios estão presentes
- **Logs Detalhados**: Rastreamento completo do processo de envio
- **Tratamento de Erros**: Mensagens específicas para diferentes tipos de falha

### Tipos de Mensagem Suportados:
- **Texto**: Mensagens de texto simples
- **Imagem**: Com URL e legenda opcional
- **Vídeo**: Com URL e legenda opcional
- **Áudio**: Com URL e opção PTT (push-to-talk)
- **Documento**: Com URL, tipo MIME e nome do arquivo

### Validações Automáticas:
- Conexão WebSocket ativa
- Sessão conectada ao WhatsApp
- Campos obrigatórios por tipo de mensagem
- Formato correto do número de telefone

## 🛡️ Sistema Anti-Conflitos

### Controle de Sessões Exclusivas
- **Locks de sessão**: Previne conexões simultâneas do mesmo número
- **Verificação proativa**: Checa status antes de conectar
- **Registro de atividade**: Rastreia sessões ativas por número
- **Liberação automática**: Remove locks expirados

### Monitoramento de Conflitos
- **Detecção automática**: Registra todos os conflitos "Stream Errored"
- **Alertas inteligentes**: Notifica quando há 5+ conflitos por hora
- **Histórico temporal**: Mantém registro dos últimos conflitos
- **Métricas em tempo real**: Dashboard de saúde do sistema

### Estratégias Anti-Loop
- **Detecção de padrões**: Identifica reconexões em loop
- **Interrupção automática**: Para tentativas excessivas
- **Cooldown inteligente**: Aumenta delays progressivamente
- **Modo de emergência**: Ativa proteções extras quando necessário

## 🔄 Recuperação Automática

### Recuperação na Inicialização:
- **Verificação automática** de sessões existentes ao iniciar
- **Validação de saúde** antes de recuperar
- **Reconexão sequencial** para evitar sobrecarga
- **Logs detalhados** do processo de recuperação

### Critérios de Recuperação:
- ✅ Arquivos de credenciais presentes
- ✅ Sessão não muito antiga (< 7 dias padrão)
- ✅ Arquivos não corrompidos
- ✅ Não já conectada

### Processo de Recuperação:
```
[RECOVERY] Iniciando recuperação automática [conservative]
[RECOVERY] Tentando recuperar sessão: session-123
[RECOVERY] Sessão session-123 recuperada com sucesso
[RECOVERY] Recuperação concluída. 3 sessões recuperadas.
```

## 🚀 Funcionalidades de Reconexão

### 1. **Sistema de Heartbeat**
- Verifica a saúde da conexão a cada 30 segundos
- Detecta desconexões silenciosas automaticamente
- Inicia reconexão proativa quando necessário

### 2. **Backoff Exponencial Inteligente**
- **Normal**: Delay inicial: 3 segundos → 3s, 6s, 12s, 24s, 48s... (máx. 5 min)
- **Conflitos**: Delay especial: 30s, 45s, 67s, 100s... (máx. 2 min)
- Jitter aleatório para evitar reconexões simultâneas

### 3. **Limite de Tentativas**
- Máximo de 10 tentativas de reconexão
- Após esgotar tentativas, remove sessão automaticamente
- Notifica sistema Laravel sobre falha permanente

### 4. **Monitoramento Global**
- Verifica todas as conexões ativamente a cada minuto
- Detecta conexões "presas" em estado connecting
- Remove sessões orfãs automaticamente
- Limpa sessões antigas (>24h sem atividade)

### 5. **Recuperação Automática**
- Detecta sessões corrompidas na inicialização
- Remove diretórios vazios automaticamente
- Permite que o Baileys tente recuperar credenciais válidas

### 6. **Detecção de Loops Infinitos**
- Monitora tentativas de reconexão em janelas de 5 minutos
- Detecta loops quando há 3+ reconexões em 5 minutos
- Interrompe automaticamente reconexões em loop
- Notifica sobre intervenção manual necessária

## 📊 Estados de Conexão

```
connecting → connected (sucesso)
    ↓
disconnected → reconexão automática com backoff
    ↓
failed (após 10 tentativas) → sessão removida
```

## 🔍 Logs de Monitoramento

O sistema gera logs detalhados para facilitar o diagnóstico:

```
[session-123] Iniciando conexão para sessão
[session-123] Conexão estabelecida com sucesso
[session-123] Heartbeat OK - conexão ativa
[MONITOR] 3/3 conexões ativas
```

## ⚙️ Configuração

As configurações podem ser ajustadas no código:

```javascript
const MAX_RECONNECT_ATTEMPTS = 10      // Máximo de tentativas
const HEARTBEAT_INTERVAL = 30000       // 30 segundos
const MAX_HEARTBEAT_MISSES = 3         // 3 heartbeats perdidos = desconexão
```

## 🛡️ Robustez

- **Timeout global**: 10 segundos para todas as requisições HTTP
- **Retry de mensagens**: Até 3 tentativas para envio de mensagens
- **Limpeza automática**: Remove sessões corrompidas/orfãs
- **Monitoramento contínuo**: Detecta problemas antes que afetem usuários

## ⚙️ Configuração de Segurança

Configure o modo de conexão através de variável de ambiente:

```bash
# Modo Conservador (Recomendado para produção)
export CONNECTION_MODE=conservative

# Ou no .env
CONNECTION_MODE=conservative
```

### Modos Disponíveis:

| Modo | Tentativas Máx | Heartbeat | Conflito Delay | Risco Bloqueio |
|------|----------------|-----------|----------------|----------------|
| `normal` | 10 | 30s | 30s-2min | Alto |
| `conservative` | 5 | 1min | 2min-10min | Médio |
| `safe` | 3 | 2min | 5min-30min | Baixo |
| `ultra-safe` | 2 | 5min | 30min-2h | Ultra Baixo |

### Funcionalidades por Modo:

#### 🟢 Normal (Desenvolvimento)
- Reconexão agressiva
- Detecção rápida de desconexões
- Ideal para desenvolvimento/debug

#### 🟡 Conservative (Produção Recomendado)
- Balanceado entre disponibilidade e segurança
- Menos reconexões para reduzir spam
- Maior tolerância a falhas temporárias

#### 🔴 Safe (Máxima Segurança)
- Prioriza não sobrecarregar WhatsApp
- Muito conservador com reconexões
- Mínimo risco de bloqueio

## 🧪 Teste do Sistema:

Para testar o envio de mensagens, use o script incluído:

```bash
node test-send.js
```

Este script irá:
- ✅ Verificar se a sessão está conectada
- ✅ Enviar uma mensagem de teste
- ✅ Testar validações de erro
- ✅ Mostrar logs detalhados

## 🚨 Cenários Tratados

1. **Desconexão de internet**: Reconexão automática com backoff
2. **Restart do servidor**: Sessões recuperadas automaticamente
3. **Sessões corrompidas**: Detecção e limpeza automática
4. **Timeout do WhatsApp Web**: Heartbeat detecta e reconecta
5. **Múltiplas desconexões**: Backoff evita sobrecarga

Este sistema garante que seus clientes nunca percam mensagens devido a problemas de conectividade, mantendo o serviço sempre disponível.

## 🔧 Troubleshooting

### Problemas de Envio de Mensagens

**Sintomas:**
```
[API] Erro ao enviar mensagem: Sessão xxx não está conectada ao WhatsApp
[API] Erro ao enviar mensagem: Conexão WebSocket para sessão xxx não está ativa
```

**Causas Possíveis:**
1. **Conexão perdida**: WhatsApp desconectou
2. **Número mal formatado**: Falta @s.whatsapp.net
3. **Dados inválidos**: Campos obrigatórios ausentes
4. **Arquivo não acessível**: URL de mídia inválida

**Soluções:**
1. **Verificar conexão**: Use `/status/:session_id`
2. **Formatar número**: `5511999999999@s.whatsapp.net`
3. **Validar dados**: Verifique campos obrigatórios
4. **Testar conectividade**: Envie uma mensagem simples primeiro

### Conflitos de Conexão (Stream Errored)

**Sintomas:**
```
[e2158164-7678-4a45-a917-95d9ad370b42] Desconexão detectada - razão: connection_closed: Stream Errored (conflict) (CONFLITO DETECTADO)
🚨 ALERTA CRÍTICO: MÚLTIPLOS CONFLITOS DETECTADOS!
```

**Causas Possíveis:**
1. **Múltiplas conexões simultâneas** do mesmo número
2. **WhatsApp Web/App ativo** em outro dispositivo
3. **Sessões duplicadas** rodando simultaneamente
4. **Reconexões muito frequentes** (spam detection)

**Soluções Imediatas:**
1. **Desconectar WhatsApp Web** em todos os navegadores
2. **Fechar aplicativo WhatsApp** no celular
3. **Verificar múltiplas instâncias** do serviço rodando
4. **Aguardar 30+ minutos** antes de reconectar

**Soluções Técnicas:**
1. **Usar modo ultra-safe**: `CONNECTION_MODE=ultra-safe`
2. **Verificar locks ativos**: Monitorar logs de `[LOCKS]`
3. **Limpar sessões antigas**: Verificar `/sessions/` directory
4. **Monitorar conflitos**: Logs de `[CONFLICTS]`

**Códigos de Erro:**
- `404`: Sessão não encontrada
- `503`: Serviço indisponível (não conectado)
- `500`: Erro interno (problema na mensagem)

### Loop de Conexão/Desconexão

**Sintomas:**
```
[session-123] Desconexão detectada - razão: connection_closed: Stream Errored (conflict)
[session-123] LOOP DE RECONEXÃO DETECTADO!
```

**Causas Possíveis:**
1. **Múltiplas conexões simultâneas** do mesmo número
2. **WhatsApp Web aberto** em outro navegador/dispositivo
3. **Aplicativo WhatsApp** conectado simultaneamente
4. **Sessão duplicada** em outro servidor

**Soluções:**
1. **Feche outras sessões WhatsApp** (Web/App)
2. **Aguarde 5-10 minutos** antes de reconectar
3. **Verifique se há múltiplas instâncias** do serviço rodando
4. **Reinicie o serviço** após resolver conflitos

### Problemas de Autenticação (Erro 302)

**Sintomas:**
```
http://localhost/conversations/8/messages 302
Erro ao enviar mensagem. Sua sessão expirou.
```

**Causas Possíveis:**
1. **Sessão do Laravel expirada**
2. **Token CSRF inválido**
3. **Usuário não logado** no frontend
4. **Timeout da sessão** do navegador

**Soluções:**
1. **Faça login novamente** no sistema
2. **Limpe cookies** do navegador se necessário
3. **Verifique se a sessão** não expirou (30s de verificação automática)
4. **Recarregue a página** para renovar tokens

**Recursos de Recuperação:**
- ✅ Verificação automática de autenticação a cada 30s
- ✅ Redirecionamento automático para login quando detectado
- ✅ Tratamento específico de erros 401/302
- ✅ Endpoint dedicado `/auth/check` para verificação

### Status de Conexão

O sistema agora reporta status específicos:
- `loop_detected`: Loop de reconexão detectado
- `failed`: Máximo de tentativas atingido
- `logged_out`: Usuário fez logout
- `disconnected`: Desconexão temporária
- `connected`: Conexão ativa
