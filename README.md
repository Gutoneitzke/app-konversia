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

### Usando Script Bash
```bash
# Inicializar tudo automaticamente
./start-project.sh

# Parar tudo
./stop-project.sh
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
├── start-project.sh   # Script de inicialização
├── stop-project.sh    # Script de parada
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
make horizon        # Inicia Horizon
make horizon-pause  # Pausa processamento de filas
make horizon-continue # Retoma processamento
```

---

**Dica**: Use `make start` para inicializar tudo rapidamente com Telescope e Horizon! 🎉