# Progresso — Bibliotequinha 2.0

Data da execução: 02/11/2026. O documento fornecido foi usado como especificação; dados e resultados são conferidos no repositório.

| Etapa | Situação inicial | Trabalho atual | Validação |
|---|---|---|---|
| 1 Diagnóstico | Laravel 12, Breeze, livros/categorias | Matriz em DIAGNOSTICO.md | Inspeção realizada |
| 2 Arquitetura | Exemplar com autor textual | Empréstimos com histórico e limite | ARQUITETURA.md |
| 3 PostgreSQL/Neon | PostgreSQL configurado, sem SSL variável | DB_SSLMODE configurável | Neon pendente de credenciais |
| 4 Dados | Migrations, factories de usuário, seeders existentes | Migrations incrementais e seed sem sobrescrever livros | Testado em PostgreSQL |
| 5 Autorização | admin/bibliotecario/consulta | Policy de empréstimos e autenticação de API | Testado |
| 6 Web | CRUD de livros/categorias existente | Registro, consulta e devolução de empréstimos | Testado |
| 7 Índices | Índice status/title existente | title/id e ativos por usuário/prazo; unique parcial de integridade | SQL versionado |
| 8 Functions | Ausentes | 2 funções de negócio | SELECT via LoanService e SQL |
| 9 Procedures | Ausentes | Registro e devolução | CALL via LoanService |
| 10 Triggers | Ausentes | Validação, auditoria/situação e proteção de livros | Testes PostgreSQL |
| 11 Services | Ausentes | BookService e LoanService | Controllers delegam operações |
| 12 API | Ausente | CRUD, Resource, token Bearer, PATCH parcial | BookApiTest |
| 13 Testes | Suite web em SQLite | Suite API e PostgreSQL isolado | Resultado em TESTES.md |
| 14 Entrega | README básico | SQL, backup, relatório Markdown/PDF e documentação | Backup/restauração executados |
| 15 Apresentação | Documentação anterior preservada | Roteiro 10 min e arguição | Material escrito |

Branch: feat/bibliotequinha-segundo-bimestre. Arquivos não rastreados preexistentes foram preservados. Não houve push/publicação. Nenhum banco real foi apagado.

Validação final: 45 testes/243 assertions aprovados no PostgreSQL; build de produção concluído; HTTP real /login 200 e /api/livros sem token 401. Backup/restauração em banco isolado com contagens e assinatura conferidas. Banco atual recebeu backup e migrations incrementais. Relatório PDF gerado. Pendências externas: credenciais Neon e autorização acadêmica de entrega individual. Sem revisão visual em navegador ou teste de carga.
