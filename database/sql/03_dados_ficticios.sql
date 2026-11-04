-- Dados exclusivamente fictícios para banco LOCAL vazio criado por 00_estrutura.sql.
-- Nenhuma senha conhecida ou token: hashes aleatórios sem credenciais utilizáveis.
-- Não executar no Neon ou em banco com dados existentes.
INSERT INTO "users" ("id", "name", "email", "email_verified_at", "password", "remember_token", "created_at", "updated_at", "role") VALUES ('47', 'Pessoa fictícia — admin', 'admin@biblioteca.test', '2026-11-04 20:24:06', '$2y$10$an1lxI5KzjOOCOAYoKvRsu7qY21hs7Oy7gJxVIiRuanKEmTcTAfYa', NULL, '2026-11-04 20:24:06', '2026-11-04 20:24:06', 'admin');
INSERT INTO "users" ("id", "name", "email", "email_verified_at", "password", "remember_token", "created_at", "updated_at", "role") VALUES ('48', 'Pessoa fictícia — bibliotecario', 'bibliotecario@biblioteca.test', '2026-11-04 20:24:06', '$2y$10$E.gdnjawX7NfClvQjOUXO.itcelr.Xd307/kc4w3t5qcexzPySkuS', NULL, '2026-11-04 20:24:06', '2026-11-04 20:24:06', 'bibliotecario');
INSERT INTO "users" ("id", "name", "email", "email_verified_at", "password", "remember_token", "created_at", "updated_at", "role") VALUES ('49', 'Pessoa fictícia — consulta', 'consulta@biblioteca.test', '2026-11-04 20:24:07', '$2y$10$tn19rcd9dQhr7flmUSAWQ.hiZsNw0u9x1fNyYC.aoaGqsKgLGjuju', NULL, '2026-11-04 20:24:07', '2026-11-04 20:24:07', 'consulta');
SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT max(id) FROM "users"), true);
INSERT INTO "categories" ("id", "name", "code", "description", "created_at", "updated_at") VALUES ('20', 'Literatura brasileira', 'LIT', 'Romances, contos e histórias da nossa literatura.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "categories" ("id", "name", "code", "description", "created_at", "updated_at") VALUES ('21', 'Ficção e fantasia', 'FIC', 'Mundos imaginários e aventuras para descobrir.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "categories" ("id", "name", "code", "description", "created_at", "updated_at") VALUES ('22', 'Tecnologia', 'TEC', 'Conhecimento para aprender e criar com tecnologia.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "categories" ("id", "name", "code", "description", "created_at", "updated_at") VALUES ('23', 'História e sociedade', 'HIS', 'Leituras sobre o passado e a vida em sociedade.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
SELECT setval(pg_get_serial_sequence('categories', 'id'), (SELECT max(id) FROM "categories"), true);
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('36', '20', 'Entre Rios e Memórias', 'Ana Ribeiro', 'LIV-0001', '2010', 'Editora Exemplo', 'indisponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('37', '20', 'O Jardim das Palavras', 'Bruno Almeida', 'LIV-0002', '2011', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('38', '20', 'Cartas da Vila', 'Clara Santos', 'LIV-0003', '2012', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('39', '20', 'Retratos de um Verão', 'Daniel Costa', 'LIV-0004', '2013', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('40', '21', 'A Cidade das Estrelas', 'Elisa Rocha', 'LIV-0005', '2014', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('41', '21', 'O Mapa dos Ventos', 'Felipe Moura', 'LIV-0006', '2015', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('42', '21', 'Além da Última Ponte', 'Gabriela Lima', 'LIV-0007', '2016', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('43', '21', 'O Guardião da Lua', 'Henrique Dias', 'LIV-0008', '2017', 'Editora Exemplo', 'indisponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('44', '22', 'Primeiros Passos com PHP', 'Isabela Martins', 'LIV-0009', '2018', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('45', '22', 'Construindo Aplicações Web', 'João Ferreira', 'LIV-0010', '2019', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('46', '22', 'Banco de Dados na Prática', 'Larissa Gomes', 'LIV-0011', '2020', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('47', '22', 'Programação e Criatividade', 'Marcos Souza', 'LIV-0012', '2021', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('48', '23', 'Memórias da Nossa Cidade', 'Natália Barbosa', 'LIV-0013', '2022', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('49', '23', 'Caminhos da Sociedade', 'Otávio Pereira', 'LIV-0014', '2023', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('50', '23', 'Histórias do Cotidiano', 'Paula Nunes', 'LIV-0015', '2024', 'Editora Exemplo', 'indisponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
INSERT INTO "books" ("id", "category_id", "title", "author", "code", "publication_year", "publisher", "status", "synopsis", "created_at", "updated_at") VALUES ('51', '23', 'Cultura e Comunidade', 'Rafael Oliveira', 'LIV-0016', '2010', 'Editora Exemplo', 'disponivel', 'Exemplar fictício para demonstração do sistema de biblioteca.', '2026-11-04 20:24:07', '2026-11-04 20:24:07');
SELECT setval(pg_get_serial_sequence('books', 'id'), (SELECT max(id) FROM "books"), true);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('1', '0001_01_01_000000_create_users_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('2', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('3', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('4', '2026_09_12_000001_add_role_to_users', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('5', '2026_09_12_000002_create_courses_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('6', '2026_09_12_000003_create_students_table', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('7', '2026_09_13_000001_create_library_tables', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('8', '2026_09_13_000002_update_library_roles', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('9', '2026_11_02_000001_create_loans_and_api_tokens', '1');
INSERT INTO "migrations" ("id", "migration", "batch") VALUES ('10', '2026_11_02_000002_install_postgres_library_objects', '1');
SELECT setval(pg_get_serial_sequence('migrations', 'id'), (SELECT max(id) FROM "migrations"), true);

-- Histórico demonstrativo gerado pelas próprias procedures/triggers.
DO $$
DECLARE leitor bigint; exemplar bigint; emprestimo bigint;
BEGIN
    SELECT id INTO leitor FROM users WHERE email = 'consulta@biblioteca.test';
    SELECT id INTO exemplar FROM books WHERE code = 'LIV-0002';
    CALL sp_registrar_emprestimo(leitor, exemplar, CURRENT_DATE + 14);
    SELECT id INTO emprestimo FROM loans WHERE book_id = exemplar AND returned_at IS NULL;
    CALL sp_registrar_devolucao(emprestimo);
    CALL sp_registrar_emprestimo(leitor, exemplar, CURRENT_DATE + 14);
END;
$$;
