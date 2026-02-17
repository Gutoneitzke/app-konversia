# Makefile para gerenciamento do projeto Konversia

.PHONY: help up down build restart logs shell npm-dev npm-build queue schedule services

# Cores para output
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m # No Color

# Comando base do Sail
SAIL=./vendor/bin/sail

help: ## Mostra esta ajuda
	@echo "$(BLUE)🚀 Comandos do Projeto Konversia$(NC)"
	@echo "$(YELLOW)Serviços principais:$(NC)"
	@grep -E '^(start|up|down|services):.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(YELLOW)Ferramentas de debug:$(NC)"
	@grep -E '^(telescope|horizon|redis-cli):.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(YELLOW)Outros comandos:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | grep -v -E '^(start|up|down|services|telescope|horizon|redis-cli|help):' | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'

install: ## Instala dependências do projeto
	@echo "$(YELLOW)Instalando dependências do Laravel...$(NC)"
	cd konversia && composer install
	@echo "$(YELLOW)Instalando dependências do Node.js...$(NC)"
	cd konversia && npm install

up: ## Sobe todos os containers
	@echo "$(BLUE)Subindo containers do Laravel Sail...$(NC)"
	cd konversia && $(SAIL) up -d
	@echo "$(GREEN)Containers do Laravel inicializados!$(NC)"

down: ## Para todos os containers
	@echo "$(YELLOW)Parando containers...$(NC)"
	cd konversia && $(SAIL) down
	@echo "$(GREEN)Containers parados!$(NC)"

build: ## Build dos containers
	@echo "$(BLUE)Buildando containers...$(NC)"
	cd konversia && $(SAIL) build
	@echo "$(GREEN)Build concluído!$(NC)"

restart: ## Reinicia os containers
	@echo "$(YELLOW)Reiniciando containers...$(NC)"
	cd konversia && $(SAIL) restart
	@echo "$(GREEN)Containers reiniciados!$(NC)"

logs: ## Mostra logs dos containers
	cd konversia && $(SAIL) logs -f

shell: ## Acessa o shell do container Laravel
	cd konversia && $(SAIL) shell

migrate: ## Roda as migrações do banco
	@echo "$(BLUE)Rodando migrações...$(NC)"
	cd konversia && $(SAIL) artisan migrate
	@echo "$(GREEN)Migrações executadas!$(NC)"

seed: ## Roda os seeders
	@echo "$(BLUE)Rodando seeders...$(NC)"
	cd konversia && $(SAIL) artisan db:seed
	@echo "$(GREEN)Seeders executados!$(NC)"

fresh: ## Recria o banco do zero
	@echo "$(RED)Cuidado: Isso vai apagar todos os dados!$(NC)"
	@echo "$(BLUE)Recriando banco...$(NC)"
	cd konversia && $(SAIL) artisan migrate:fresh --seed
	@echo "$(GREEN)Banco recriado!$(NC)"

npm-dev: ## Roda o npm run dev
	@echo "$(BLUE)Iniciando desenvolvimento frontend...$(NC)"
	cd konversia && $(SAIL) npm run dev

npm-build: ## Build de produção do frontend
	@echo "$(BLUE)Buildando frontend para produção...$(NC)"
	cd konversia && $(SAIL) npm run build
	@echo "$(GREEN)Build concluído!$(NC)"

queue: ## Roda o worker de filas
	@echo "$(BLUE)Iniciando worker de filas...$(NC)"
	cd konversia && $(SAIL) artisan queue:work

schedule: ## Roda o scheduler
	@echo "$(BLUE)Iniciando scheduler...$(NC)"
	cd konversia && $(SAIL) artisan schedule:work

horizon: ## Roda o Laravel Horizon
	@echo "$(BLUE)Iniciando Laravel Horizon...$(NC)"
	cd konversia && $(SAIL) artisan horizon

horizon-pause: ## Pausa o Horizon
	@echo "$(YELLOW)Pausando Horizon...$(NC)"
	cd konversia && $(SAIL) artisan horizon:pause

horizon-continue: ## Continua o Horizon
	@echo "$(GREEN)Continuando Horizon...$(NC)"
	cd konversia && $(SAIL) artisan horizon:continue

queue-status: ## Mostra status das filas
	@echo "$(BLUE)=== Status das Filas ===$(NC)"
	@cd konversia && $(SAIL) artisan queue:monitor incoming,outgoing,automation,webhook --format=table || echo "  Comando não disponível"

queue-clear: ## Limpa todas as filas
	@echo "$(YELLOW)Limpando filas...$(NC)"
	@cd konversia && $(SAIL) artisan queue:clear redis incoming || echo "  Fila incoming limpa"
	@cd konversia && $(SAIL) artisan queue:clear redis outgoing || echo "  Fila outgoing limpa"
	@cd konversia && $(SAIL) artisan queue:clear redis automation || echo "  Fila automation limpa"
	@cd konversia && $(SAIL) artisan queue:clear redis webhook || echo "  Fila webhook limpa"
	@echo "$(GREEN)Filas limpas!$(NC)"

queue-monitor: ## Monitor avançado das filas WhatsApp
	@echo "$(BLUE)Monitor Avançado das Filas WhatsApp$(NC)"
	cd konversia && $(SAIL) artisan whatsapp:monitor-queues

queue-monitor-json: ## Monitor das filas em formato JSON
	@echo "$(BLUE)Monitor das Filas (JSON)$(NC)"
	cd konversia && $(SAIL) artisan whatsapp:monitor-queues --format=json

services: ## Roda queue, schedule e horizon simultaneamente
	@echo "$(BLUE)Iniciando serviços em background...$(NC)"
	@echo "$(YELLOW)Queue worker...$(NC)"
	cd konversia && $(SAIL) artisan queue:work &
	@echo "$(YELLOW)Scheduler...$(NC)"
	cd konversia && $(SAIL) artisan schedule:work &
	@echo "$(YELLOW)Horizon...$(NC)"
	cd konversia && $(SAIL) artisan horizon &
	@echo "$(GREEN)Serviços iniciados em background!$(NC)"

whatsapp-up: ## Sobe o serviço WhatsApp
	@echo "$(BLUE)Subindo serviço WhatsApp...$(NC)"
	cd whatsapp-service && docker compose up -d
	@echo "$(GREEN)Serviço WhatsApp inicializado!$(NC)"

whatsapp-down: ## Para o serviço WhatsApp
	@echo "$(YELLOW)Parando serviço WhatsApp...$(NC)"
	cd whatsapp-service && docker compose down
	@echo "$(GREEN)Serviço WhatsApp parado!$(NC)"

whatsapp-logs: ## Logs do serviço WhatsApp
	cd whatsapp-service && docker compose logs -f

redis-cli: ## Acessa o Redis CLI
	@echo "$(BLUE)Acessando Redis...$(NC)"
	cd konversia && $(SAIL) redis-cli

telescope: ## Abre Telescope no navegador
	@echo "$(BLUE)Abrindo Telescope...$(NC)"
	@echo "URL: http://localhost/telescope"
	@if command -v open >/dev/null 2>&1; then open http://localhost/telescope; fi
	@if command -v xdg-open >/dev/null 2>&1; then xdg-open http://localhost/telescope; fi

horizon-dashboard: ## Abre Horizon no navegador
	@echo "$(BLUE)Abrindo Horizon...$(NC)"
	@echo "URL: http://localhost/horizon"
	@if command -v open >/dev/null 2>&1; then open http://localhost/horizon; fi
	@if command -v xdg-open >/dev/null 2>&1; then xdg-open http://localhost/horizon; fi

start: up whatsapp-up npm-dev services ## Inicializa tudo (containers, WhatsApp, frontend e serviços)
	@echo "$(GREEN)🎉 Projeto Konversia totalmente inicializado!$(NC)"
	@echo "$(BLUE)Serviços rodando:$(NC)"
	@echo "  - Laravel Sail: http://localhost"
	@echo "  - Telescope: http://localhost/telescope"
	@echo "  - Horizon: http://localhost/horizon"
	@echo "  - WhatsApp Service: rodando em background"
	@echo "  - Frontend dev server: rodando"
	@echo "  - Queue worker: rodando em background"
	@echo "  - Scheduler: rodando em background"

stop: down whatsapp-down ## Para tudo
	@echo "$(GREEN)🛑 Todos os serviços parados!$(NC)"

status: ## Mostra status de todos os serviços
	@echo "$(BLUE)=== Status dos Serviços ===$(NC)"
	@echo "$(YELLOW)Laravel Sail:$(NC)"
	@cd konversia && $(SAIL) ps || echo "  Containers não encontrados"
	@echo "$(YELLOW)WhatsApp Service:$(NC)"
	@cd whatsapp-service && docker compose ps || echo "  Serviço não encontrado"
	@echo "$(YELLOW)Redis:$(NC)"
	@docker ps | grep redis || echo "  Redis não encontrado"
	@echo "$(YELLOW)Processos em execução:$(NC)"
	@ps aux | grep -E "(queue:work|schedule:work|horizon|npm)" | grep -v grep || echo "  Nenhum processo encontrado"