# Backup e restauração PostgreSQL

Instale ferramentas cliente compatíveis com a versão do servidor. Use host direto fornecido pelo Neon para operações administrativas. Credenciais via prompt `-W`, `.pgpass` com permissões adequadas ou variável temporária; não inclua senha na linha de comando ou no relatório.

```sh
pg_dump -h HOST -p 5432 -U USUARIO -W -d BANCO -Fc -f biblioteca.dump
createdb -h HOST_TESTE -p 5432 -U USUARIO_TESTE -W biblioteca_restore_test
pg_restore -h HOST_TESTE -p 5432 -U USUARIO_TESTE -W -d biblioteca_restore_test --no-owner --no-acl --exit-on-error biblioteca.dump
psql -h HOST_TESTE -U USUARIO_TESTE -W -d biblioteca_restore_test -f database/sql/02_demonstracao.sql
```

Configure `PGSSLMODE=require` para Neon. `createdb` exige permissão; alternativamente crie um banco/branch vazio no console Neon e use seu destino. Restaure somente em destino isolado vazio, sem `--clean`. Não execute em banco de produção. Confirme host, usuário e nome antes de restaurar.

Compare contagens de users/categories/books/loans/loan_audits; execute as duas functions e confirme procedures/triggers. No projeto, `scripts/Test-Postgres.ps1 -Backup` automatiza essas verificações com dados fictícios em porta 55433, cria destino novo com sufixo de timestamp, compara contagens e testa devolução no banco restaurado. Dump fica em storage/app/backups (ignorado pelo Git). Nunca publique dump real com dados pessoais. Evidências efetivas: docs/TESTES.md e docs/evidencias, quando geradas.

As migrations instalam o SQL especial automaticamente. Não é preciso reaplicar 01_library_objects.sql depois de pg_restore: os objetos já fazem parte do dump.
