# Bibliotequinha da Rhay — Sistema de Biblioteca

Aplicação web para organizar o acervo de uma biblioteca, com cadastro de livros, categorias, pesquisa e controle de acesso. Desenvolvida com Laravel, PostgreSQL e autenticação Laravel Breeze, seguindo MVC e SRP.


## Funcionalidades

- Login, cadastro, logout e manutenção da própria conta com Breeze.
- Painel com total de exemplares, disponíveis, emprestados e categorias.
- CRUD completo de livros: cadastro, listagem, ficha, edição e exclusão.
- Busca por título, autor ou código do exemplar; filtros por categoria e situação; paginação.
- Categorias com descrição e quantidade de livros relacionados.
- Validação no servidor e mensagens em português.
- Três papéis com permissões efetivas por Middleware e Policy.

Cada registro de livro representa **um exemplar físico**, identificado por um código interno único, como LIV-0001. Vários exemplares podem ter o mesmo título e autor, desde que tenham códigos diferentes. O código não é um ISBN.

A situação é atualizada manualmente por administrador ou bibliotecário: disponível, emprestado ou indisponível. O escopo é controle de acervo; não há cadastro de tomadores, histórico de empréstimos, prazos, reservas ou multas.

## Tecnologias

PHP 8.2+, Laravel 12, PostgreSQL, Eloquent ORM, Blade, Laravel Breeze 2, Tailwind CSS, Alpine.js, Vite, PHPUnit, Laravel Pint, Git e GitHub Actions. Use Node.js 22+ e Composer para instalar as dependências.

## Perfis

| Ação                                  | admin| bibliotecario | consulta |

| Consultar painel, livros e categorias | Sim  | Sim           | Sim      |
| Cadastrar e editar livros             | Sim  | Sim           | Não      |
| Excluir livros                        | Sim  | Não           | Não      |
| Cadastrar, editar e excluir categorias| Sim  | Não           | Não      |
| Alterar a própria conta               | Sim  | Sim           | Sim      |

O cadastro público recebe o papel consulta. O usuário não pode alterar seu papel pelo cadastro ou pela edição de perfil. Categorias com livros não podem ser excluídas.


## Instalação em outra máquina

Instale PHP com pdo_pgsql e demais extensões exigidas pelo Laravel, Composer, Node.js/npm e PostgreSQL. Crie um banco vazio chamado biblioteca.

```bash
git clone URL_DO_REPOSITORIO
cd NOME_DA_PASTA
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
```

```bash
php artisan migrate --seed
php artisan serve --port=8001
```

Abra http://127.0.0.1:8001. Para modificar o frontend, use npm run dev em outro terminal. Execute npm run build para gerar novamente os arquivos compilados.

O envio de e-mails está configurado como log para desenvolvimento. Links de recuperação de senha aparecem em storage/logs/laravel.log. Configure SMTP no .env para envio real.

## Usuários de demonstração

| Perfil        | E-mail                        | Senha          |

| Administrador | admin@biblioteca.test         | Biblioteca@123 |
| Bibliotecário | bibliotecario@biblioteca.test | Biblioteca@123 |
| Consulta      | consulta@biblioteca.test      | Biblioteca@123 |
