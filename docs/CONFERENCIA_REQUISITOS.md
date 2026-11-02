# Conferência final

| Requisito | Evidência / arquivo | Como verificar | Situação |
|---|---|---|---|
| Laravel/MVC/Blade/Breeze | app/Models, Controllers, resources/views | suite auth/web | Testado |
| Migrations, seeders, Eloquent | database/migrations e seeders | Test-Postgres.ps1 | Testado |
| Três roles, middleware e Policy | EnsureRole, BookPolicy, LoanPolicy | testes 403/isolamento | Testado |
| Form Requests | BookRequest, CategoryRequest, LoanRequest | testes de validação | Testado |
| CRUD principal web | BookController, CategoryController | LibraryTest | Testado |
| Empréstimo e devolução | LoanController, LoanService | PostgresLoanTest | Testado |
| Services | app/Services | web e API usam BookService; LoanService chama SQL | Testado |
| API REST e Resource | routes/api.php, BookResource | BookApiTest | Testado |
| Autenticação API | AuthenticateApiToken, api:token | 401/403/expiração | Testado |
| PostgreSQL | config/database.php | suite PostgreSQL 18 | Testado localmente |
| Neon/SSL | DB_SSLMODE no exemplo/config | configurar credenciais e migrate:status | Pendente externo |
| Dois índices de desempenho | books_title_order_idx, loans_active_user_idx | 01_library_objects.sql, EXPLAIN em evidência | Criados/testados |
| Duas functions de negócio | fn_quantidade_emprestimos_ativos, fn_livro_disponivel | SELECT/LoanService | Criadas/testadas |
| Duas procedures | sp_registrar_emprestimo/devolucao | CALL/LoanService | Criadas/testadas |
| Duas triggers (mínimo) | loans_validate, loans_audit; extra books_protect_status | PostgresLoanTest | Criadas/testadas |
| Integração efetiva SQL/Laravel | LoanService + auditoria | teste web de retirada/devolução | Testada |
| 2 testes web + 2 API | LibraryTest e BookApiTest | 45 testes PostgreSQL | Superado |
| Backup e restauração | scripts/Test-Postgres.ps1 -Backup | docs/evidencias/postgres-validacao.txt | Testados localmente |
| Scripts SQL e README | database/sql, README.md | leitura e execução no teste | Preparados |
| Relatório e PDF | RELATORIO_ENTREGA.md/.pdf | scripts/generate-report.py | Preparados |
| Apresentação/arguição | ROTEIRO_APRESENTACAO, PERGUNTAS_ARGUICAO | roteiro 10 minutos, 32 perguntas | Preparados |
| Git e privacidade | branch feat/bibliotequinha-segundo-bimestre, .gitignore | git status/diff/check-ignore | Histórico preservado, sem push |
| Entrega individual | RELATORIO_ENTREGA.md | confirmar com professor | Pendente externo |

Limites honestos: Neon não foi acessado; não houve publicação/push, teste visual em navegador nem benchmark de carga. Autor foi preservado como texto e consulta como papel leitor, sem entidades ou integrantes inventados. Empréstimos não permitem exclusão de histórico; cadastro, consulta e devolução atendem ao domínio. Documentação antiga pode descrever a versão anterior.
