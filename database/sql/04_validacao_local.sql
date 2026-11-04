-- Somente no banco LOCAL descartável reconstruído com 00 + 03. Não aplicar no Neon.
BEGIN;
DO $$
DECLARE leitor bigint; exemplar bigint; emprestimo bigint; anteriores bigint;
BEGIN
    IF (SELECT count(*) FROM users) <> 3 OR (SELECT count(*) FROM categories) <> 4
        OR (SELECT count(*) FROM books) <> 16 OR (SELECT count(*) FROM loans) <> 2
        OR (SELECT count(*) FROM loan_audits) <> 3 THEN
        RAISE EXCEPTION 'Contagens fictícias inesperadas';
    END IF;
    SELECT id INTO leitor FROM users WHERE email = 'consulta@biblioteca.test';
    SELECT id INTO exemplar FROM books WHERE status = 'disponivel' ORDER BY id LIMIT 1;
    anteriores := fn_quantidade_emprestimos_ativos(leitor);
    IF NOT fn_livro_disponivel(exemplar) THEN RAISE EXCEPTION 'Disponibilidade incorreta'; END IF;
    CALL sp_registrar_emprestimo(leitor, exemplar, CURRENT_DATE + 7);
    SELECT id INTO emprestimo FROM loans WHERE book_id = exemplar AND returned_at IS NULL;
    IF fn_quantidade_emprestimos_ativos(leitor) <> anteriores + 1 OR fn_livro_disponivel(exemplar) THEN
        RAISE EXCEPTION 'Retirada inconsistente';
    END IF;
    BEGIN
        CALL sp_registrar_emprestimo(leitor, exemplar, CURRENT_DATE + 7);
        RAISE EXCEPTION 'Duplicidade indevidamente aceita';
    EXCEPTION WHEN check_violation THEN NULL;
    END;
    CALL sp_registrar_devolucao(emprestimo);
    IF NOT fn_livro_disponivel(exemplar) OR fn_quantidade_emprestimos_ativos(leitor) <> anteriores
        OR (SELECT count(*) FROM loan_audits WHERE loan_id = emprestimo) <> 2 THEN
        RAISE EXCEPTION 'Devolução/auditoria inconsistente';
    END IF;
    RAISE NOTICE 'SQL validado: dados, functions, procedures, triggers e rejeição de duplicidade.';
END;
$$;
ROLLBACK;
SELECT (SELECT count(*) FROM users) AS usuarios, (SELECT count(*) FROM categories) AS categorias,
    (SELECT count(*) FROM books) AS livros, (SELECT count(*) FROM loans) AS emprestimos,
    (SELECT count(*) FROM loan_audits) AS auditorias;
