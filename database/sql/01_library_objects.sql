-- Instalado pela migration; executar manualmente somente em banco já migrado.
CREATE INDEX IF NOT EXISTS books_title_order_idx ON books (title, id);
CREATE INDEX IF NOT EXISTS loans_active_user_idx ON loans (user_id, due_at) WHERE returned_at IS NULL;
CREATE UNIQUE INDEX IF NOT EXISTS loans_one_active_copy_idx ON loans (book_id) WHERE returned_at IS NULL;

CREATE OR REPLACE FUNCTION fn_quantidade_emprestimos_ativos(p_usuario bigint)
RETURNS bigint LANGUAGE sql STABLE AS $$
    SELECT count(*) FROM loans WHERE user_id = p_usuario AND returned_at IS NULL;
$$;

CREATE OR REPLACE FUNCTION fn_livro_disponivel(p_livro bigint)
RETURNS boolean LANGUAGE sql STABLE AS $$
    SELECT EXISTS (SELECT 1 FROM books b WHERE b.id = p_livro AND b.status = 'disponivel'
        AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.book_id = b.id AND l.returned_at IS NULL));
$$;

-- A validação também protege INSERT/UPDATE/DELETE executados fora do Laravel.
CREATE OR REPLACE FUNCTION trg_validar_emprestimo()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Histórico de empréstimos não pode ser excluído.';
    END IF;
    PERFORM 1 FROM users WHERE id = NEW.user_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Usuário inexistente.';
    END IF;
    PERFORM 1 FROM books WHERE id = NEW.book_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Exemplar inexistente.';
    END IF;
    IF NEW.borrowed_at > clock_timestamp() OR NEW.due_at < NEW.borrowed_at::date
       OR (NEW.returned_at IS NOT NULL AND (NEW.returned_at < NEW.borrowed_at OR NEW.returned_at > clock_timestamp())) THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Datas de empréstimo inválidas.';
    END IF;
    IF TG_OP = 'INSERT' THEN
        IF NEW.returned_at IS NOT NULL OR NOT fn_livro_disponivel(NEW.book_id) THEN
            RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Exemplar indisponível.';
        END IF;
        IF fn_quantidade_emprestimos_ativos(NEW.user_id) >= 3 THEN
            RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Limite de três empréstimos ativos atingido.';
        END IF;
    ELSE
        IF NEW.user_id <> OLD.user_id OR NEW.book_id <> OLD.book_id OR NEW.borrowed_at <> OLD.borrowed_at
            OR OLD.returned_at IS NOT NULL OR NEW.returned_at IS NULL THEN
            RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Somente a devolução de empréstimo ativo é permitida.';
        END IF;
    END IF;
    RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION trg_auditar_emprestimo()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO loan_audits (loan_id, operation, old_data, new_data, created_at)
    VALUES (NEW.id, TG_OP, CASE WHEN TG_OP = 'UPDATE' THEN to_jsonb(OLD) ELSE NULL END, to_jsonb(NEW), CURRENT_TIMESTAMP);
    UPDATE books SET status = CASE WHEN NEW.returned_at IS NULL THEN 'emprestado' ELSE 'disponivel' END,
        updated_at = CURRENT_TIMESTAMP WHERE id = NEW.book_id;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS loans_validate ON loans;
CREATE TRIGGER loans_validate BEFORE INSERT OR UPDATE OR DELETE ON loans
FOR EACH ROW EXECUTE FUNCTION trg_validar_emprestimo();
DROP TRIGGER IF EXISTS loans_audit ON loans;
CREATE TRIGGER loans_audit AFTER INSERT OR UPDATE ON loans
FOR EACH ROW EXECUTE FUNCTION trg_auditar_emprestimo();

CREATE OR REPLACE FUNCTION trg_proteger_situacao_livro()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM loans WHERE book_id = NEW.id AND returned_at IS NULL) THEN
        IF NEW.status <> 'emprestado' THEN
            RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Registre a devolução antes de alterar a situação.';
        END IF;
    ELSIF NEW.status = 'emprestado' THEN
        -- Registros legados permanecem íntegros; novos empréstimos exigem histórico.
        IF TG_OP = 'INSERT' THEN
            RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Registre um empréstimo para emprestar o exemplar.';
        ELSIF OLD.status <> 'emprestado' THEN
            RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Registre um empréstimo para emprestar o exemplar.';
        END IF;
    END IF;
    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS books_protect_status ON books;
CREATE TRIGGER books_protect_status BEFORE INSERT OR UPDATE ON books
FOR EACH ROW EXECUTE FUNCTION trg_proteger_situacao_livro();

CREATE OR REPLACE PROCEDURE sp_registrar_emprestimo(IN p_usuario bigint, IN p_livro bigint, IN p_prazo date)
LANGUAGE plpgsql AS $$
BEGIN
    IF p_prazo IS NULL OR p_prazo < CURRENT_DATE THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Prazo deve ser hoje ou uma data futura.';
    END IF;
    -- A trigger concentra as regras e adquire locks antes de consultar disponibilidade/limite.
    INSERT INTO loans (user_id, book_id, borrowed_at, due_at, created_at, updated_at)
    VALUES (p_usuario, p_livro, CURRENT_TIMESTAMP, p_prazo, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
END;
$$;

CREATE OR REPLACE PROCEDURE sp_registrar_devolucao(IN p_emprestimo bigint)
LANGUAGE plpgsql AS $$
BEGIN
    UPDATE loans SET returned_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
        WHERE id = p_emprestimo AND returned_at IS NULL;
    IF NOT FOUND THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Empréstimo inexistente ou já devolvido.';
    END IF;
END;
$$;
