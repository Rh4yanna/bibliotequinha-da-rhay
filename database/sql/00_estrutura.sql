--
-- PostgreSQL database dump
--

\restrict k38YfmFfdj88xK6kiH9Wz9FqUW2shj9Oxa5yeylTC2s8XM8fygaQlTTuzwLnjiO

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: fn_livro_disponivel(bigint); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_livro_disponivel(p_livro bigint) RETURNS boolean
    LANGUAGE sql STABLE
    AS $$
    SELECT EXISTS (SELECT 1 FROM books b WHERE b.id = p_livro AND b.status = 'disponivel'
        AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.book_id = b.id AND l.returned_at IS NULL));
$$;


--
-- Name: fn_quantidade_emprestimos_ativos(bigint); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_quantidade_emprestimos_ativos(p_usuario bigint) RETURNS bigint
    LANGUAGE sql STABLE
    AS $$
    SELECT count(*) FROM loans WHERE user_id = p_usuario AND returned_at IS NULL;
$$;


--
-- Name: sp_registrar_devolucao(bigint); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.sp_registrar_devolucao(IN p_emprestimo bigint)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE loans SET returned_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
        WHERE id = p_emprestimo AND returned_at IS NULL;
    IF NOT FOUND THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Empréstimo inexistente ou já devolvido.';
    END IF;
END;
$$;


--
-- Name: sp_registrar_emprestimo(bigint, bigint, date); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.sp_registrar_emprestimo(IN p_usuario bigint, IN p_livro bigint, IN p_prazo date)
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF p_prazo IS NULL OR p_prazo < CURRENT_DATE THEN
        RAISE EXCEPTION USING ERRCODE = '23514', MESSAGE = 'Prazo deve ser hoje ou uma data futura.';
    END IF;
    -- A trigger concentra as regras e adquire locks antes de consultar disponibilidade/limite.
    INSERT INTO loans (user_id, book_id, borrowed_at, due_at, created_at, updated_at)
    VALUES (p_usuario, p_livro, CURRENT_TIMESTAMP, p_prazo, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
END;
$$;


--
-- Name: trg_auditar_emprestimo(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_auditar_emprestimo() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO loan_audits (loan_id, operation, old_data, new_data, created_at)
    VALUES (NEW.id, TG_OP, CASE WHEN TG_OP = 'UPDATE' THEN to_jsonb(OLD) ELSE NULL END, to_jsonb(NEW), CURRENT_TIMESTAMP);
    UPDATE books SET status = CASE WHEN NEW.returned_at IS NULL THEN 'emprestado' ELSE 'disponivel' END,
        updated_at = CURRENT_TIMESTAMP WHERE id = NEW.book_id;
    RETURN NEW;
END;
$$;


--
-- Name: trg_proteger_situacao_livro(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_proteger_situacao_livro() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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


--
-- Name: trg_validar_emprestimo(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_validar_emprestimo() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: api_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.api_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    token_hash character varying(64) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: api_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.api_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: api_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.api_tokens_id_seq OWNED BY public.api_tokens.id;


--
-- Name: books; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.books (
    id bigint NOT NULL,
    category_id bigint NOT NULL,
    title character varying(200) NOT NULL,
    author character varying(150) NOT NULL,
    code character varying(20) NOT NULL,
    publication_year smallint NOT NULL,
    publisher character varying(150),
    status character varying(255) DEFAULT 'disponivel'::character varying NOT NULL,
    synopsis text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT books_status_check CHECK (((status)::text = ANY ((ARRAY['disponivel'::character varying, 'emprestado'::character varying, 'indisponivel'::character varying])::text[])))
);


--
-- Name: books_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.books_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: books_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.books_id_seq OWNED BY public.books.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(20) NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: courses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.courses (
    id bigint NOT NULL,
    name character varying(150) NOT NULL,
    code character varying(20) NOT NULL,
    semesters smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: courses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.courses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: courses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.courses_id_seq OWNED BY public.courses.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: loan_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_audits (
    id bigint NOT NULL,
    loan_id bigint NOT NULL,
    operation character varying(10) NOT NULL,
    old_data json,
    new_data json NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: loan_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.loan_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: loan_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.loan_audits_id_seq OWNED BY public.loan_audits.id;


--
-- Name: loans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loans (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    book_id bigint NOT NULL,
    borrowed_at timestamp(6) without time zone NOT NULL,
    due_at date NOT NULL,
    returned_at timestamp(6) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.loans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: loans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.loans_id_seq OWNED BY public.loans.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: students; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.students (
    id bigint NOT NULL,
    course_id bigint NOT NULL,
    name character varying(150) NOT NULL,
    email character varying(255) NOT NULL,
    registration character varying(20) NOT NULL,
    birth_date date NOT NULL,
    phone character varying(25),
    status character varying(255) DEFAULT 'ativo'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT students_status_check CHECK (((status)::text = ANY ((ARRAY['ativo'::character varying, 'trancado'::character varying, 'concluido'::character varying])::text[])))
);


--
-- Name: students_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.students_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: students_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.students_id_seq OWNED BY public.students.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(255) DEFAULT 'consulta'::character varying NOT NULL,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'bibliotecario'::character varying, 'consulta'::character varying])::text[])))
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: api_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens ALTER COLUMN id SET DEFAULT nextval('public.api_tokens_id_seq'::regclass);


--
-- Name: books id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.books ALTER COLUMN id SET DEFAULT nextval('public.books_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: courses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.courses ALTER COLUMN id SET DEFAULT nextval('public.courses_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: loan_audits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_audits ALTER COLUMN id SET DEFAULT nextval('public.loan_audits_id_seq'::regclass);


--
-- Name: loans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans ALTER COLUMN id SET DEFAULT nextval('public.loans_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: students id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.students ALTER COLUMN id SET DEFAULT nextval('public.students_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: api_tokens api_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_pkey PRIMARY KEY (id);


--
-- Name: api_tokens api_tokens_token_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_token_hash_unique UNIQUE (token_hash);


--
-- Name: books books_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_code_unique UNIQUE (code);


--
-- Name: books books_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_code_unique UNIQUE (code);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: courses courses_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_code_unique UNIQUE (code);


--
-- Name: courses courses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: loan_audits loan_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_audits
    ADD CONSTRAINT loan_audits_pkey PRIMARY KEY (id);


--
-- Name: loans loans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: students students_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_email_unique UNIQUE (email);


--
-- Name: students students_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_pkey PRIMARY KEY (id);


--
-- Name: students students_registration_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_registration_unique UNIQUE (registration);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: books_status_title_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX books_status_title_index ON public.books USING btree (status, title);


--
-- Name: books_title_order_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX books_title_order_idx ON public.books USING btree (title, id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: loans_active_user_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loans_active_user_idx ON public.loans USING btree (user_id, due_at) WHERE (returned_at IS NULL);


--
-- Name: loans_one_active_copy_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX loans_one_active_copy_idx ON public.loans USING btree (book_id) WHERE (returned_at IS NULL);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: students_status_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX students_status_name_index ON public.students USING btree (status, name);


--
-- Name: books books_protect_status; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER books_protect_status BEFORE INSERT OR UPDATE ON public.books FOR EACH ROW EXECUTE FUNCTION public.trg_proteger_situacao_livro();


--
-- Name: loans loans_audit; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER loans_audit AFTER INSERT OR UPDATE ON public.loans FOR EACH ROW EXECUTE FUNCTION public.trg_auditar_emprestimo();


--
-- Name: loans loans_validate; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER loans_validate BEFORE INSERT OR DELETE OR UPDATE ON public.loans FOR EACH ROW EXECUTE FUNCTION public.trg_validar_emprestimo();


--
-- Name: api_tokens api_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: books books_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: loan_audits loan_audits_loan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_audits
    ADD CONSTRAINT loan_audits_loan_id_foreign FOREIGN KEY (loan_id) REFERENCES public.loans(id) ON DELETE RESTRICT;


--
-- Name: loans loans_book_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_book_id_foreign FOREIGN KEY (book_id) REFERENCES public.books(id) ON DELETE RESTRICT;


--
-- Name: loans loans_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: students students_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict k38YfmFfdj88xK6kiH9Wz9FqUW2shj9Oxa5yeylTC2s8XM8fygaQlTTuzwLnjiO
