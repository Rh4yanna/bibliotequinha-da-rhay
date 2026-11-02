# API REST

Todas as rotas usam token Bearer e limite de 60 requisições/minuto. Tokens são aleatórios, armazenados somente como SHA-256 e expiram em sete dias. Emita localmente no terminal administrativo:

```sh
php artisan api:token admin@biblioteca.test
php artisan api:token admin@biblioteca.test --revoke
```

O primeiro comando exibe o token uma única vez. Guarde-o em variável de ambiente, não em commits ou URLs. Use HTTPS fora da máquina local. Não existe endpoint público de emissão de token. A remoção da conta revoga seus tokens por FK.

| Método | Endpoint | Permissão | Sucesso |
|---|---|---|---|
| GET | /api/livros | Todos os três perfis | 200 com data/links/meta |
| GET | /api/livros/{id} | Todos os três perfis | 200 com data |
| POST | /api/livros | admin/bibliotecario | 201 com data |
| PUT | /api/livros/{id} | admin/bibliotecario | 200 com data |
| PATCH | /api/livros/{id} | admin/bibliotecario | 200 com data |
| DELETE | /api/livros/{id} | admin, sem histórico | 204 sem conteúdo |

POST/PUT exigem title (2–200 caracteres), author (3–150), code (único, até 20, letras/números/hífen/underscore), publication_year (1450 até ano atual), category_id existente e status. publisher/synopsis são opcionais. PATCH valida apenas campos enviados. code é normalizado em maiúsculas. Use `disponivel` ou `indisponivel`; `emprestado` é atribuído pelo fluxo de empréstimos. Um registro legado pode manter seu estado até reconciliação.

```sh
curl -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8001/api/livros
curl -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"title":"Livro exemplo","author":"Autor Exemplo","code":"API-001","publication_year":2020,"category_id":1,"status":"disponivel"}' http://127.0.0.1:8001/api/livros
curl -X PATCH -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"title":"Título alterado"}' http://127.0.0.1:8001/api/livros/1
```

Exemplo de retorno: `{"data":{"id":1,"title":"Livro exemplo","author":"Autor Exemplo","code":"API-001","publication_year":2020,"publisher":null,"category_id":1,"status":"disponivel","synopsis":null,"category":{"id":1,"name":"Literatura"}}}`.

Erros JSON: 401 token ausente/inválido/expirado; 403 perfil sem permissão; 404 registro inexistente; 422 validação ou regra de domínio; 429 limite excedido. Erros são JSON mesmo sem Accept; segredos nunca fazem parte do Resource.
