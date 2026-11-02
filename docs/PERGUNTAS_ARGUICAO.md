# Perguntas para arguição

1. **O que é MVC?** Model representa dados/relações, View exibe e Controller coordena a requisição.
2. **Onde está MVC aqui?** Book, BookController e resources/views/books.
3. **O que é Eloquent?** ORM que permite consultar tabelas por Models e relações.
4. **O que é migration?** Alteração versionada de estrutura, com up e down.
5. **O que é seeder?** Rotina para preparar dados de demonstração; não deve sobrescrever dados reais.
6. **O que é factory?** Gerador de registros de teste, como UserFactory.
7. **Para que serve Breeze?** Fornece o fluxo de autenticação e telas iniciais.
8. **Quais são os papéis?** Admin, bibliotecario e consulta; consulta representa leitor.
9. **Middleware e Policy diferem como?** Middleware filtra requisições; Policy autoriza ações sobre recursos.
10. **Por que esconder botão não basta?** Requisições diretas ainda precisam ser negadas pelo servidor.
11. **O que faz um Form Request?** Autoriza e valida dados antes do Controller.
12. **Como evita SQL injection?** Bindings parametrizados, Eloquent e validação; não concatenar entrada em SQL.
13. **Para que serve índice?** Facilita localizar/ordenar dados; tem custo em escrita e não garante ganho em tabelas pequenas.
14. **Quais são os dois índices adicionais?** title/id em books e user_id/due_at em empréstimos ativos.
15. **O que retorna uma function?** Um valor; aqui quantidade de ativos e disponibilidade.
16. **O que faz uma procedure?** Executa operação via CALL; aqui registra retirada ou devolução.
17. **O que é trigger?** Código automático disparado por evento da tabela.
18. **As functions de trigger contam como as duas de negócio?** Não. São auxiliares com retorno trigger.
19. **Como evita empréstimo duplicado?** Lock do exemplar, validação e índice único parcial dos ativos.
20. **Como aplica o limite por pessoa?** Lock da linha de usuário e contagem de ativos antes da inserção.
21. **O que é uma transação?** Conjunto atômico: falha desfaz empréstimo, auditoria e situação juntos.
22. **Por que Service?** Centraliza operação de domínio reutilizável por Controllers.
23. **O que é API REST?** Interface HTTP usando recursos, métodos e respostas JSON.
24. **O que faz API Resource?** Define os campos públicos e estrutura da resposta.
25. **Quais códigos HTTP são usados?** 200 sucesso, 201 criação, 204 exclusão, 401 sem autenticação, 403 proibido, 404 ausente, 422 inválido.
26. **Como autentica a API?** Bearer aleatório, hash no banco, expiração e revogação por CLI.
27. **Por que testar PostgreSQL?** SQLite não executa PL/pgSQL, procedures e triggers específicas.
28. **Como proteger o banco durante testes?** Instância local isolada e guarda que recusa alvos inesperados antes de migrations.
29. **O que é Neon?** Serviço hospedado PostgreSQL; conexão externa requer credenciais e SSL.
30. **Como restaura backup?** pg_dump -Fc, banco vazio separado, pg_restore e validação de dados/objetos.
31. **Por que não excluir empréstimos?** Para preservar histórico e auditoria; a operação de atualização é a devolução.
32. **Quem desenvolveu?** Projeto individual de Rhayanna, com assistência técnica de Codex; não há colegas fictícios.
