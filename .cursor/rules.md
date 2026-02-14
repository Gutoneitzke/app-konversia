# Regras do Projeto Konversia — WhatsApp SaaS (Laravel + Inertia + whatsmeow)

Este projeto é um SaaS B2B de atendimento via WhatsApp.
O foco é estabilidade, simplicidade e entrega rápida.

Estas regras DEVEM ser respeitadas em todo código gerado.

---

## 1. STACK (NÃO ALTERAR)

- Backend: Laravel (versão mais recente)
- Autenticação: Jetstream
- Frontend: Vue 3 + Inertia
- Banco de dados: MySQL (Laravel) + PostgreSQL (WhatsApp Service)
- Filas: Laravel Queue
- Tempo real: Polling (NÃO usar WebSockets)
- Integração WhatsApp: Microserviço Go usando whatsmeow

NÃO introduzir:
- React
- Next.js
- Nuxt
- WebSockets
- Gerenciadores de estado adicionais
- Bibliotecas experimentais
- Node.js/Baileys (substituído por Go/whatsmeow)

---

## 2. PRINCÍPIOS DE ARQUITETURA

- Laravel é a fonte da verdade do sistema.
- O serviço Go (whatsapp-service) é responsável SOMENTE pela conexão com o WhatsApp.
- Regras de negócio NUNCA ficam no Go.
- O Go não deve conhecer filas, usuários ou planos.
- A comunicação entre Laravel e Go ocorre via webhooks HTTP.

NUNCA:
- Conectar Laravel diretamente ao WhatsApp
- Misturar lógica de WhatsApp dentro de Controllers do Laravel
- Colocar regra de negócio no serviço Go
- Usar filas para comunicação com o serviço WhatsApp

---

## 3. MULTIEMPRESA (CRÍTICO)

- Todas as tabelas relevantes DEVEM ter company_id.
- Todas as queries DEVEM ser filtradas por company_id.
- Usuários pertencem a exatamente uma empresa.
- Cada empresa possui apenas 1 número de WhatsApp.
- Cada empresa possui apenas 1 assinatura ativa.

Se a empresa estiver inativa:
- Bloquear imediatamente qualquer ação no sistema.

---

## 4. REGRAS DE BACKEND (LARAVEL)

- Controllers devem ser simples e enxutos.
- Lógica de negócio deve ficar em Services.
- Operações assíncronas devem usar Jobs.
- Usar transações de banco em fluxos críticos.
- Código explícito e legível é prioridade.
- Seguir convenções do Laravel.

NÃO:
- Superengenheirar soluções
- Criar abstrações desnecessárias
- Refatorar código fora do escopo solicitado

---

## 5. REGRAS DE FRONTEND (VUE + INERTIA)

- Interface simples e funcional.
- Estado mínimo nos componentes.
- Preferir lógica no backend.
- Atualizações em tempo real via polling.
- Priorizar previsibilidade e clareza.
- Criar soluções responsivas.

NÃO:
- Usar WebSockets
- Criar lógica complexa no frontend
- Adicionar animações ou bibliotecas visuais sem solicitação

---

## 6. REGRAS DE WHATSAPP (WHATSMEOW)

- A conexão com o WhatsApp existe SOMENTE no Go (whatsapp-service).
- Uma sessão de WhatsApp por empresa.
- Sessões são persistidas em PostgreSQL via whatsmeow.
- Reconexão automática é obrigatória.
- Todas as mensagens e eventos são enviados ao Laravel via webhooks.
- O Laravel decide como tratar mensagens, filas e atendimentos.

NÃO:
- Gerenciar filas no Go
- Atribuir atendimentos no Go
- Salvar dados de negócio no Go
- Misturar responsabilidades entre Laravel e Go

---

## 6.1. SERVIÇO WHATSAPP (GO)

- Framework: Echo v5
- Biblioteca WhatsApp: whatsmeow
- Persistência: PostgreSQL
- Comunicação: Webhooks HTTP para Laravel
- API: RESTful simples com header X-Number-Id
- Gerenciamento de estado: In-memory com mutex para concorrência

ENDPOINTS:
- POST /number: Criar conexão (QR Code)
- GET /number: Status da conexão
- DELETE /number: Desconectar
- POST /number/message: Enviar mensagem

REGRAS:
- Um cliente whatsmeow por empresa
- Restauração automática na inicialização
- Event handlers notificam Laravel via webhook
- Tratamento de erros deve ser robusto
- Logs devem ser informativos

---

## 7. CONTROLE DE ESCOPO (MUITO IMPORTANTE)

Implementar APENAS o que for explicitamente solicitado.

Este projeto NÃO inclui:
- Chatbots
- Respostas automáticas com IA
- Automações avançadas
- Múltiplos números de WhatsApp por empresa
- Métricas de SLA
- Dashboards avançados
- Integrações com terceiros

Se algo não estiver claramente solicitado, NÃO implementar.

---

## 8. PADRÃO DE CÓDIGO

- Clareza é mais importante que código “inteligente”.
- Código explícito > abstrações.
- Evitar otimização prematura.
- Mudanças devem ser pequenas e incrementais.
- Não quebrar funcionalidades existentes sem aviso.

---

## 9. DESENVOLVIMENTO E DEPLOYMENT

- Laravel: Desenvolvimento local com `php artisan serve`
- WhatsApp Service: Docker Compose para desenvolvimento
- Ambiente de produção: Docker containers separados
- Comunicação: Webhooks configurados via variáveis de ambiente
- Monitoramento: Logs em stdout/stderr de ambos os serviços

---

## 10. COMPORTAMENTO DA IA

Ao gerar código:
- Respeitar estas regras rigorosamente.
- Não expandir escopo sem autorização.
- Não assumir requisitos inexistentes.
- Não inventar funcionalidades.
- Perguntar antes de qualquer decisão fora do padrão.
- Considerar tanto Laravel quanto Go quando relevante.

Se alguma solicitação conflitar com estas regras, PERGUNTAR antes de prosseguir.
