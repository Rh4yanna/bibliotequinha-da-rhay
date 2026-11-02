# Bibliotequinha da Rhay — Sistema de Biblioteca

Aplicação web para organizar o acervo de uma biblioteca, com cadastro de livros, categorias, pesquisa e controle de acesso. Desenvolvida com Laravel, PostgreSQL e autenticação Laravel Breeze, seguindo MVC e SRP.


## Integrante e participação

Projeto individual de **Rhayanna** — GitHub: [@Rh4yanna](https://github.com/Rh4yanna).

Participação: definição do tema e das funcionalidades, orientação das personalizações da interface e organização da entrega.

## Funcionalidades

- Login, cadastro, logout e manutenção da própria conta com Breeze.
- Painel com total de exemplares, disponíveis, emprestados e categorias.
- CRUD completo de livros: cadastro, listagem, ficha, edição e exclusão.
- Busca por título, autor ou código do exemplar; filtros por categoria e situação; paginação.
- Categorias com descrição e quantidade de livros relacionados.
- Validação no servidor e mensagens em português.
- Três papéis com permissões efetivas por Middleware e Policy.

Cada registro de livro representa **um exemplar físico**, identificado por um código interno único, como LIV-0001. Vários exemplares podem ter o mesmo título e autor, desde que tenham códigos diferentes. O código não é um ISBN.

A versão 2.0 acrescenta empréstimos com tomador, prazo, devolução e auditoria. Cada pessoa pode ter três ativos, e cada exemplar apenas um. A situação emprestado é controlada pelo empréstimo; disponível/indisponível continuam administráveis quando não há retirada ativa. Registros antigos sem tomador são preservados para conferência manual. Reservas e multas não fazem parte do escopo.

## Tecnologias

PHP 8.2+, Laravel 12, PostgreSQL, Eloquent ORM, Blade, Laravel Breeze 2, Tailwind CSS, Alpine.js, Vite, PHPUnit, Laravel Pint, Git e GitHub Actions. Use Node.js 22+ e Composer para instalar as dependências.

## Perfis

| Ação                                  | admin| bibliotecario | consulta |
|---|---|---|---|
| Consultar painel, livros e categorias | Sim  | Sim           | Sim      |
| Cadastrar e editar livros             | Sim  | Sim           | Não      |
| Excluir livros                        | Sim  | Não           | Não      |
| Cadastrar, editar e excluir categorias| Sim  | Não           | Não      |
| Alterar a própria conta               | Sim  | Sim           | Sim      |
| Registrar e devolver empréstimos      | Sim  | Sim           | Não      |
| Consultar todos os empréstimos        | Sim  | Sim           | Só seus próprios |

O cadastro público recebe o papel consulta. O usuário não pode alterar seu papel pelo cadastro ou pela edição de perfil. Categorias com livros não podem ser excluídas.


## Instalação em outra máquina

Instale PHP com pdo_pgsql e demais extensões exigidas pelo Laravel, Composer, Node.js/npm e PostgreSQL. Crie um banco vazio chamado biblioteca.

```bash
git clone https://github.com/Rh4yanna/bibliotequinha-da-rhay.git
cd bibliotequinha-da-rhay
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
```

No PowerShell, use Copy-Item .env.example .env no lugar de cp.

Configure no .env:

```dotenv
APP_NAME="Bibliotequinha da Rhay - Sistema de Biblioteca"
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=biblioteca
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
DB_SSLMODE=disable
```

```bash
php artisan migrate --seed
php artisan serve --port=8001
```

Abra http://127.0.0.1:8001. Para modificar o frontend, use npm run dev em outro terminal. Execute npm run build para gerar novamente os arquivos compilados.

O envio de e-mails está configurado como log para desenvolvimento. Links de recuperação de senha aparecem em storage/logs/laravel.log. Configure SMTP no .env para envio real.

## Usuários de demonstração

| Perfil        | E-mail                        | Senha          |
|---|---|---|
| Administrador | admin@biblioteca.test         | Biblioteca@123 |
| Bibliotecário | bibliotecario@biblioteca.test | Biblioteca@123 |
| Consulta      | consulta@biblioteca.test      | Biblioteca@123 |

Essas contas são apenas para desenvolvimento/testes: **não usar em produção**. Seeders não redefinem contas já existentes e não alteram exemplares existentes. Para produção, execute migrations sem seed de demonstração. O papel `consulta` é o leitor.

## Neon e extensão PHP

Crie um projeto PostgreSQL no console Neon e copie os dados de conexão para seu `.env` local: DB_CONNECTION=pgsql, DB_HOST (host fornecido), DB_PORT=5432, DB_DATABASE e DB_USERNAME fornecidos, DB_PASSWORD e **DB_SSLMODE=require**. Não comite `.env` nem URLs com senha. Use host direto para backup/restauração; não dependa do pooler para operações administrativas. A conexão real ao Neon ainda depende das credenciais da estudante.

Habilite `extension=pdo_pgsql` no php.ini do PHP CLI. Nesta máquina Windows, o driver também pode ser habilitado sem alterar o PHP global:

```powershell
$env:PHP_INI_SCAN_DIR = Join-Path $PWD 'scripts/php-conf'
php artisan migrate
php artisan serve --port=8001
```

Para aplicar a versão nova em banco existente, faça backup primeiro e execute `php artisan migrate`. As migrations acrescentam tabelas/objetos e preservam livros, usuários e registros acadêmicos anteriores. Não execute migrate:fresh no banco real.

## API e programação SQL

CRUD autenticado em `/api/livros`, com API Resource e os mesmos Form Requests/Policies do web. Emita token local com `php artisan api:token admin@biblioteca.test`; veja campos, status, exemplos e revogação em [docs/API.md](docs/API.md).

As migrations instalam automaticamente [database/sql/01_library_objects.sql](database/sql/01_library_objects.sql): dois índices de consulta, um índice único parcial, duas functions de negócio, duas procedures e três triggers. LoanService integra SELECT/CALL; auditoria/situação são automáticas. Exemplos e EXPLAIN em [database/sql/02_demonstracao.sql](database/sql/02_demonstracao.sql). Não é necessário executar o instalador SQL manualmente após migrate.

## Testes e backup

```powershell
php artisan test
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/Test-Postgres.ps1 -Backup
```

O primeiro comando usa SQLite em memória para auth/web/API; os testes PostgreSQL são explicitamente pulados. O segundo prepara instância **isolada** na porta 55433, executa a suite PostgreSQL, demonstra objetos SQL e restaura um dump em outro banco vazio. Requer PostgreSQL 18 no caminho padrão ou parâmetro `-PostgresBin`. Nunca aponte testes para Neon/produção. A guarda em tests/TestCase.php recusa banco fora do alvo esperado. Não rode duas suites simultaneamente no mesmo cache Blade.

Resultados efetivos em [docs/TESTES.md](docs/TESTES.md). Procedimento seguro de backup em [docs/BACKUP_RESTAURACAO.md](docs/BACKUP_RESTAURACAO.md). Dumps, cluster de testes, dependências e credenciais são ignorados pelo Git.

## Entrega e limites

- [Arquitetura](docs/ARQUITETURA.md), [diagnóstico](docs/DIAGNOSTICO.md), [progresso](docs/PROGRESSO_PROJETO.md) e [conferência de requisitos](docs/CONFERENCIA_REQUISITOS.md).
- [Relatório de entrega](docs/RELATORIO_ENTREGA.md), PDF correspondente, [roteiro de 10 minutos](docs/ROTEIRO_APRESENTACAO.md) e [perguntas de arguição](docs/PERGUNTAS_ARGUICAO.md).
- CRUD principal de livros/categorias preservado. Empréstimos usam registro/consulta/devolução; exclusão de histórico é proibida por integridade. Autor continua campo textual.
- Não há gestão de outras contas no escopo atual. Exclusão da própria conta com histórico de empréstimos é bloqueada para preservar dados.
- Confirme com o professor autorização de entrega individual: o resumo do enunciado informa grupos de 3 a 4. Não foi informado prazo de entrega.
- Documentação antiga em docs foi preservada e pode descrever a versão anterior; os documentos acima representam a versão 2.0.
