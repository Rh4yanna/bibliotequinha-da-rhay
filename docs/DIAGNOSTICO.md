# Diagnóstico inicial

Inspeção em 02/11/2026: PHP CLI 8.2.12, Composer 2.8.9, Laravel instalado 12.69.2. Node disponível 20.16.0. PostgreSQL 18 instalado em `C:\Program Files\PostgreSQL\18\bin`. PHP padrão sem pdo_pgsql; `scripts/php-conf/postgres.ini` existente habilita o driver via PHP_INI_SCAN_DIR. Não foi localizado PDF do enunciado no projeto.

| Requisito | Situação inicial | Ação | Arquivo principal |
|---|---|---|---|
| MVC/Blade/Breeze | Existente | Preservar | app/Http/Controllers/Auth, resources/views/auth |
| Três papéis | admin/bibliotecario/consulta | consulta continua sendo leitor | app/Policies/BookPolicy.php |
| Validação e Policy | Existentes em livros | Reaproveitar na API, acrescentar empréstimos | app/Http/Requests |
| CRUD | Livros e categorias existentes | Preservar e centralizar escrita de livros | app/Services/BookService.php |
| Relacionamentos | Categoria → livros, autor textual | Acrescentar usuário/livro → empréstimos | app/Models/Loan.php |
| PostgreSQL | Configuração já presente | SSL por variável, testes isolados | config/database.php |
| Functions/procedures/triggers | Ausentes | Implementar no banco via migration | database/sql/01_library_objects.sql |
| API/Resources | Ausentes | CRUD JSON e token com hash/expiração | routes/api.php |
| Testes | Web/auth em SQLite | API e integração PostgreSQL | tests/Feature |
| Backup/relatório | Ausentes no código rastreado | Script verificável e documentação | scripts/Test-Postgres.ps1 |

Cuidados encontrados: estado emprestado era manual e não possuía tomador; seed redefinia situação de livros e senhas; API não estava registrada. O código novo impede criar estado emprestado sem empréstimo e preserva registros legados; a migração não inventa tomadores para exemplares antigos. As entidades acadêmicas anteriores (`courses`, `students`) e arquivos locais em docs foram preservados.
