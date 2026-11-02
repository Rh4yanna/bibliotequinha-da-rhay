# Resultados verificados — 02/11/2026

## PostgreSQL 18 isolado

Comando executado: `powershell -NoProfile -ExecutionPolicy Bypass -File scripts/Test-Postgres.ps1 -Backup`.

Resultado final: **45 testes, 243 assertions, todos aprovados**, em 5,579 segundos. PHP 8.2.12, PHPUnit 11.5.56, Laravel 12.69.2. Banco `bibliotequinha_test`, host 127.0.0.1, porta 55433; cluster separado em storage/app/postgres-test, sem acesso ao banco real. A guarda de tests/TestCase.php verifica o alvo antes de RefreshDatabase.

Cobertura efetiva: login/registro/logout, senha/perfil, permissões dos três papéis, CRUD web de livros/categorias, busca/validações, API GET/POST/PUT-PATCH/DELETE e erros 401/403/404/422; empréstimo via rota web, SELECT function, CALL das duas procedures, auditoria/situação via triggers, rejeição de duplicidade, limite de três, devolução repetida, leitor isolado e proteção de livro com histórico. INSERT SQL direto também dispara validação/auditoria. Não foi executado teste de carga/concorrência em múltiplas conexões.

A suite utiliza RefreshDatabase no alvo descartável; novas migrations e objetos SQL foram reconstruídos durante a execução. Depois da suite, `php artisan migrate --env=testing --force` foi executado duas vezes: ambas retornaram Nothing to migrate, confirmando estado incremental consistente. Seed executado no mesmo alvo isolado.

## Backup e restauração reais

pg_dump customizado e pg_restore em **novo banco vazio** local. Contagens origem/destino: users=3, books=16, categories=4, loans=1, loan_audits=1. Assinatura dos dados em ambas as bases: `6aa90bd2663540a8fc78bf06d8d3491d`. No destino, CALL de devolução executou com sucesso, contagem de ativos retornou 0, auditoria passou a conter INSERT e UPDATE. Dumps privados em storage/app/backups, fora do Git. Evidência textual real: [evidencias/postgres-validacao.txt](evidencias/postgres-validacao.txt).

EXPLAIN efetivo: consulta de ativos usou loans_active_user_idx; listagem de 16 livros usou Seq Scan e Sort, escolha normal em tabela pequena. Nenhum ganho de desempenho foi inventado.

## Verificações complementares

- Suite SQLite: 45 testes, 216 assertions, **41 aprovados e 4 pulados** porque os quatro exigem PostgreSQL; execução em 22,012 segundos. Não é evidência substituta do SQL PostgreSQL.
- `npm run build`: sucesso, Vite 6.4.3, 59 módulos. O sandbox impediu o primeiro acesso Node; execução autorizada fora do sandbox passou.
- `php artisan route:list --except-vendor`: 43 rotas registradas, incluindo API e empréstimos.
- Banco atual local (`campus`, porta 55432): backup privado prévio; duas novas migrations aplicadas incrementalmente; todas as migrations constam como Ran. Nenhum migrate:fresh foi executado no banco atual.
- Servidor real em http://127.0.0.1:8001: `/login` respondeu HTTP 200 e `/api/livros` sem token respondeu HTTP 401.
- Relatório PDF gerado com Python sem dependências adicionais: 2 páginas, 7.459 bytes. `git diff --check` passou.

Nas primeiras rodadas, testes antigos esperavam situação emprestado manual e derrubavam a tabela books mesmo com novas FKs; foram ajustados à regra e à estrutura nova. Foi corrigida precisão de borrowed_at/returned_at para microssegundos, evitando arredondamento para um instante futuro. Uma leitura de cache Blade falhou durante suites simultâneas; após limpeza e execução sequencial, a suite inteira passou. O script de backup passou a localizar IDs reais, pois sequências PostgreSQL avançam mesmo com rollback dos testes.

## Limites e execução futura

Atualização Neon: conexão com SSL, transferência com backups e operações Laravel validadas; veja NEON.md. Suite atual: 46 testes/248 assertions aprovados no PostgreSQL local isolado, incluindo o conector compatível com libpq 11.4. Evidências em evidencias/neon-*.txt. Sem teste visual em navegador ou teste de carga. Instância de testes é encerrada ao final quando iniciada pelo script; a instância local da aplicação permanece separada. Use o script para repetir a validação completa; não rode suites concorrentes no mesmo cache Blade. Fora do script: carregue .env.testing e habilite pdo_pgsql, depois execute `php vendor/phpunit/phpunit/phpunit --configuration=phpunit.pgsql.xml`.
