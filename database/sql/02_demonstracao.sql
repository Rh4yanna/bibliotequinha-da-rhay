-- Consultas somente de leitura; substitua IDs conforme seus dados.
SELECT fn_quantidade_emprestimos_ativos(1), fn_livro_disponivel(1);
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM books ORDER BY title, id LIMIT 10;
EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM loans WHERE user_id = 1 AND returned_at IS NULL ORDER BY due_at;
SELECT loan_id, operation, created_at FROM loan_audits ORDER BY id DESC LIMIT 10;
-- Em banco descartável, com IDs válidos:
-- CALL sp_registrar_emprestimo(1, 1, CURRENT_DATE + 14);
-- SELECT id FROM loans WHERE user_id = 1 AND book_id = 1 AND returned_at IS NULL;
-- CALL sp_registrar_devolucao(<id retornado>);
