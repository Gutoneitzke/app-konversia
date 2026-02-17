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
	@echo "$(BLUE)Comandos disponíveis:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'

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

services: ## Roda queue e schedule simultaneamente
	@echo "$(BLUE)Iniciando serviços em background...$(NC)"
	@echo "$(YELLOW)Queue worker...$(NC)"
	cd konversia && $(SAIL) artisan queue:work &
	@echo "$(YELLOW)Scheduler...$(NC)"
	cd konversia && $(SAIL) artisan schedule:work &
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

start: up whatsapp-up npm-dev services ## Inicializa tudo (containers, WhatsApp, frontend e serviços)
	@echo "$(GREEN)🎉 Projeto Konversia totalmente inicializado!$(NC)"
	@echo "$(BLUE)Serviços rodando:$(NC)"
	@echo "  - Laravel Sail: http://localhost"
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
	@echo "$(YELLOW)Processos em execução:$(NC)"
	@ps aux | grep -E "(queue:work|schedule:work|npm)" | grep -v grep || echo "  Nenhum processo encontrado"