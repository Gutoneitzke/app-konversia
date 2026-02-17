# 🚀 Inicialização do Projeto Konversia

## Pré-requisitos

- Docker e Docker Compose instalados
- PHP 8.2+
- Node.js 18+
- Composer
- Git
- Redis (via Docker)

## Inicialização Rápida

### Usando Make (Recomendado)
```bash
# Inicializar tudo
make start

# Ou passo a passo:
make install    # Instala dependências
make up         # Sobe containers Laravel
make whatsapp-up # Sobe serviço WhatsApp
make services   # Inicia queue, scheduler e horizon
make npm-dev    # Inicia frontend
```

## Comandos Individuais

### Laravel Sail + Ferramentas
```bash
cd konversia

# Subir containers
./vendor/bin/sail up -d

# Parar containers
./vendor/bin/sail down

# Acessar shell do container
./vendor/bin/sail shell

# Ver logs
./vendor/bin/sail logs -f
```

### Serviços em Background
```bash
# Worker de filas (processa jobs)
./vendor/bin/sail artisan queue:work

# Scheduler (agenda jobs automáticos)
./vendor/bin/sail artisan schedule:work

# Horizon (monitor de filas)
./vendor/bin/sail artisan horizon

# Ou todos simultaneamente
make services
```

### WhatsApp Service
```bash
cd whatsapp-service

# Subir serviço
docker compose up -d

# Parar serviço
docker compose down

# Ver logs
docker compose logs -f
```

### Frontend
```bash
cd konversia

# Desenvolvimento
./vendor/bin/sail npm run dev

# Build para produção
./vendor/bin/sail npm run build
```

## URLs de Acesso

Após inicialização:
- **Aplicação Laravel**: http://localhost
- **Laravel Telescope**: http://localhost/telescope
- **Laravel Horizon**: http://localhost/horizon
- **WhatsApp Service**: Porta configurada no docker-compose.yml
- **Frontend Dev Server**: Porta 3000 (geralmente)
- **Redis**: Porta 6379 (interno aos containers)

## Banco de Dados

```bash
cd konversia

# Migrar
./vendor/bin/sail artisan migrate

# Popular com dados de teste
./vendor/bin/sail artisan db:seed

# Recriar banco do zero
./vendor/bin/sail artisan migrate:fresh --seed
```

## Organização de Filas e Waits

### 📋 Sistema de Filas

O sistema utiliza múltiplas filas especializadas com Redis e Horizon para garantir performance e organização:

#### 🧵 Filas Configuradas:

| Fila | Propósito | Wait | Supervisor | Workers | Prioridade |
|------|-----------|------|------------|---------|------------|
| **incoming** | Mensagens recebidas | 30s | incoming-supervisor | 3-10 | 🔴 Alta |
| **webhook** | Eventos WhatsApp | 15s | incoming-supervisor | 3-10 | 🔴 Crítica |
| **outgoing** | Envio de mensagens | 60s | outgoing-supervisor | 1-3 | 🟡 Média |
| **automation** | Bots e regras | 60s | automation-supervisor | 2-5 | 🟢 Baixa |

#### ⏱️ Wait Times (Alertas de Congestionamento):
- **incoming**: 30s - Mensagens precisam ser rápidas
- **webhook**: 15s - Evitar reenvio duplicado
- **outgoing**: 60s - Controle de taxa anti-ban
- **automation**: 60s - Não crítico em tempo real

### 🎯 Jobs por Fila:

| Job | Fila | Descrição |
|-----|------|-----------|
| `ProcessIncomingMessage` | incoming | Processar mensagens recebidas |
| `ProcessWhatsAppWebhookEvent` | webhook | Eventos de entrega/leitura |
| `SendWhatsAppMessage` | outgoing | Envio de mensagens (com lock) |
| `CheckWhatsAppConnectionsStatus` | automation | Verificação periódica de status |
| `ConnectWhatsAppJob` | automation | Conexão de números |

### ⚠️ Lock de Segurança (Envio de Mensagens):
- Mesmo com filas, **nunca envie mensagens em paralelo** pelo mesmo número
- Sistema usa lock Redis para evitar conflitos e bloqueios da conta WhatsApp

## Monitoramento e Debug

### Telescope (Debug/Inspeção)
- **URL**: http://localhost/telescope
- **Função**: Monitora requests, queries, jobs, etc.

### Horizon (Gerenciamento de Filas)
- **URL**: http://localhost/horizon
- **Função**: Dashboard para filas Redis
- **Comandos**:
  ```bash
  make horizon        # Iniciar
  make horizon-pause  # Pausar
  make horizon-continue # Continuar
  ```

## Monitoramento

```bash
# Status de todos os serviços
make status

# Logs dos containers
make logs

# Logs do WhatsApp service
make whatsapp-logs

# Ver processos rodando
ps aux | grep -E "(queue|sail|npm|horizon)"
```

## Arquitetura dos Serviços

```
┌─────────────────┐    ┌─────────────────┐
│   Laravel App   │    │ WhatsApp Service│
│   (PHP 8.2)     │    │    (Go)         │
│                 │    │                 │
│ • Web Server    │    │ • WhatsApp API  │
│ • API           │    │ • Webhooks      │
│ • Jobs/Queues   │◄──►│                 │
│ • Database      │    └─────────────────┘
│ • Redis Cache   │
│ • Horizon       │
│ • Telescope     │
└─────────────────┘
         ▲
         │
    ┌─────────────┐
    │   Frontend   │
    │  (Vue.js)    │
    │ • Vite Dev   │
    │ • Hot Reload │
    └─────────────┘
```

## Estrutura de Diretórios

```
app-konversia/
├── konversia/          # Aplicação Laravel
│   ├── app/           # Código da aplicação
│   ├── database/      # Migrações e seeds
│   ├── resources/     # Views e assets
│   ├── routes/        # Definições de rotas
│   ├── storage/       # Logs e cache
│   ├── vendor/        # Dependências Composer
│   ├── docker-compose.yml
│   └── .env
├── whatsapp-service/   # Serviço WhatsApp (Go)
├── Makefile           # Comandos automatizados
└── PROJECT-STARTUP.md # Esta documentação
```

## Troubleshooting

### Serviços não sobem
```bash
# Limpar containers e tentar novamente
make down
make build
make up
```

### Portas ocupadas
```bash
# Verificar portas em uso
lsof -i :8000
lsof -i :3000

# Ou mudar portas no docker-compose.yml
```

### Problemas de permissão
```bash
# Ajustar permissões
sudo chown -R $USER:$USER .
```

### Filas congestionadas
```bash
# Monitor avançado das filas WhatsApp
make queue-monitor

# Ver status das filas (Laravel padrão)
make queue-status

# Limpar filas congestionadas
make queue-clear

# Pausar processamento temporariamente
make horizon-pause

# Retomar processamento
make horizon-continue
```

### Monitoramento em tempo real
```bash
# Monitor contínuo das filas (atualiza a cada 5s)
watch -n 5 make queue-monitor

# Ou em formato JSON para scripts
make queue-monitor-json
```

### Jobs não processados
```bash
# Verificar se workers estão rodando
make status

# Reiniciar workers
make services

# Verificar logs do Laravel
make logs
```

### Redis não conecta
```bash
# Verificar se Redis está rodando
docker ps | grep redis

# Reiniciar Redis
make restart
```

## Comandos Úteis do Make

```bash
make help           # Lista todos os comandos disponíveis
make start          # Inicializa tudo
make stop           # Para tudo
make restart        # Reinicia containers
make status         # Mostra status dos serviços
make logs           # Logs dos containers
make shell          # Acessa shell do Laravel
  make horizon           # Inicia Horizon
  make horizon-pause     # Pausa processamento de filas
  make horizon-continue  # Retoma processamento
  make queue-monitor     # Monitor avançado das filas WhatsApp
  make queue-status      # Ver status das filas (Laravel)
  make queue-clear       # Limpar filas congestionadas
  make locks-monitor     # Monitor dos locks WhatsApp
  make locks-monitor-stale # Locks expirados/stale
  make locks-test         # Testar sistema de locks
```

## 🔒 Sistema de Locks WhatsApp

### Controle de Concorrência no Envio

Para evitar problemas de concorrência no WhatsApp, implementamos um sistema de locks Redis:

#### Como Funciona:
- **Cada número WhatsApp (JID)** pode ter apenas **1 job de envio ativo por vez**
- Jobs concorrentes aguardam ou são reagendados automaticamente
- **Timeout do lock**: 30 segundos por envio
- **Retry automático**: Até 3 tentativas com 10 segundos de delay

#### Benefícios:
- ✅ **Mensagens em ordem** - evita mensagens fora de sequência
- ✅ **Sem conflitos** - previne falhas de envio
- ✅ **Anti-ban** - evita sobrecarga na conta WhatsApp
- ✅ **Escalável** - múltiplos números em paralelo, mas sequencial por número

#### Monitoramento:
```bash
# Ver todos os locks ativos
make locks-monitor

# Ver apenas locks expirados (stale)
make locks-monitor-stale

# Testar locks com mensagens simultâneas
make locks-test JID=5511999999999@s.whatsapp.net COUNT=3
```

#### Como Testar:
```bash
# 1. Envie múltiplas mensagens simultâneas
make locks-test JID=5511999999999@s.whatsapp.net

# 2. Monitore os locks em tempo real
make locks-monitor

# 3. Observe no Horizon como apenas 1 job processa por vez
make horizon-dashboard
```

#### Configuração:
- **Lock Key**: `whatsapp:send_lock:{jid}`
- **Timeout**: 30 segundos
- **Block Time**: 5 segundos (espera pelo lock)
- **TTL**: Automático no Redis

---

**Dica**: Use `make start` para inicializar tudo rapidamente com Telescope e Horizon! 🎉