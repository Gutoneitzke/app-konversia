# Regras do Projeto Konversia — WhatsApp SaaS (Laravel + Inertia + Baileys)

Este projeto é um SaaS B2B de atendimento via WhatsApp.
O foco é estabilidade, simplicidade e entrega rápida.

Estas regras DEVEM ser respeitadas em todo código gerado.

---

## 1. STACK (NÃO ALTERAR)

- Backend: Laravel (versão mais recente)
- Autenticação: Jetstream
- Frontend: Vue 3 + Inertia
- Banco de dados: MySQL
- Filas: Laravel Queue
- Tempo real: Polling (NÃO usar WebSockets)
- Integração WhatsApp: Microserviço Node.js usando Baileys

NÃO introduzir:
- React
- Next.js
- Nuxt
- WebSockets
- Gerenciadores de estado adicionais
- Bibliotecas experimentais

---

## 2. PRINCÍPIOS DE ARQUITETURA

- Laravel é a fonte da verdade do sistema.
- O serviço Node.js é responsável SOMENTE pela conexão com o WhatsApp.
- Regras de negócio NUNCA ficam no Node.
- O Node não deve conhecer filas, usuários ou planos.
- A comunicação entre Laravel e Node ocorre via HTTP ou filas.

NUNCA:
- Conectar Laravel diretamente ao WhatsApp
- Misturar lógica de WhatsApp dentro de Controllers do Laravel
- Colocar regra de negócio no serviço Node

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

## 6. REGRAS DE WHATSAPP (BAILEYS)

- A conexão com o WhatsApp existe SOMENTE no Node.js.
- Uma sessão de WhatsApp por empresa.
- Sessões devem ser persistidas em disco.
- Reconexão automática é obrigatória.
- Todas as mensagens recebidas devem ser enviadas ao Laravel.
- O Laravel decide como tratar mensagens, filas e atendimentos.

NÃO:
- Gerenciar filas no Node
- Atribuir atendimentos no Node
- Salvar dados de negócio no Node

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

## 9. COMPORTAMENTO DA IA

Ao gerar código:
- Respeitar estas regras rigorosamente.
- Não expandir escopo sem autorização.
- Não assumir requisitos inexistentes.
- Não inventar funcionalidades.
- Perguntar antes de qualquer decisão fora do padrão.

Se alguma solicitação conflitar com estas regras, PERGUNTAR antes de prosseguir.
