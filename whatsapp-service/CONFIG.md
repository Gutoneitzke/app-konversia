# Configuração do WhatsApp Service

## Variáveis de Ambiente

Crie um arquivo `.env` na raiz do projeto com as seguintes configurações:

```bash
# URL da API Laravel
LARAVEL_URL=http://127.0.0.1

# Modo de conexão (IMPORTANTE!)
# - normal: Desenvolvimento, reconexão agressiva
# - conservative: Produção, balanceado
# - safe: Máxima segurança, mínimo risco de bloqueio
# - ultra-safe: ULTRA seguro, delays extremos (USAR EM CASOS CRÍTICOS)
CONNECTION_MODE=ultra-safe
```

## Como Aplicar as Configurações

### Opção 1: Arquivo .env
```bash
# Criar arquivo .env
echo "LARAVEL_URL=http://127.0.0.1" > .env
echo "CONNECTION_MODE=conservative" >> .env
```

### Opção 2: Variáveis de Ambiente
```bash
export CONNECTION_MODE=conservative
export LARAVEL_URL=http://127.0.0.1
npm start
```

### Opção 3: Inline
```bash
CONNECTION_MODE=conservative LARAVEL_URL=http://127.0.0.1 npm start
```

## Verificação

Para verificar se a configuração está ativa, olhe os logs de inicialização:

```
[CONFIG] Modo de conexão: conservative
[RECOVERY] Iniciando recuperação automática [conservative]
```

## Modos Disponíveis

| Modo | Uso | Reconexões | Segurança |
|------|-----|------------|-----------|
| `normal` | Desenvolvimento | Agressiva | Baixa |
| `conservative` | Produção | Moderada | Alta |
| `safe` | Crítico | Mínima | Máxima |

**Recomendação**: Use `ultra-safe` para máxima estabilidade em produção.

## Troubleshooting de Conflitos

### Verificar Locks Ativos
```bash
# Nos logs, procure por:
[LOCKS] X locks expirados removidos
[session-123] Lock adquirido: session_start
[session-123] Lock liberado
```

### Verificar Conflitos
```bash
# Nos logs, procure por:
[CONFLICTS] X conflitos na última hora
🚨 ALERTA CRÍTICO: MÚLTIPLOS CONFLITOS DETECTADOS!
```

### Limpar Sessões Problemáticas
```bash
# Parar o serviço
pkill -f whatsapp-service

# Remover sessões antigas (com cuidado!)
find sessions/ -name "*" -type f -mtime +1 -delete

# Reiniciar
npm start
```

### Modo de Emergência
Se conflitos persistirem:
```bash
# Mudar para ultra-safe temporariamente
export CONNECTION_MODE=ultra-safe
npm start

# Aguardar 1-2 horas sem reconectar
# Depois voltar para conservative
```
