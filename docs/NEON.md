# Neon — transferência validada em 02/11/2026

Projeto informado: bibliotequinha-rhay. Banco: neondb. Credenciais fornecidas privadamente em .env.neon, sem inclusão em logs, documentação ou commits.

## Resultado

Conexão PostgreSQL e Laravel confirmadas com sslmode=require. A string fornecida usa pooler; operações administrativas usaram o host direto equivalente. O PHP XAMPP usa libpq 11.4, sem SNI. App\Database\NeonPostgresConnector identifica o endpoint exclusivamente para hosts Neon e preserva conexões locais, conforme a [documentação oficial](https://neon.com/docs/connect/connection-errors#the-endpoint-id-is-not-specified).

Antes da transferência, backups completos da origem local e do Neon vazio foram salvos privadamente em storage/app/backups (ignorado pelo Git). pg_restore usou --single-transaction, --exit-on-error, --no-owner e --no-acl. Não houve --clean, migrate:fresh, db:wipe ou DROP no Neon.

Estrutura completa restaurada, incluindo migrations, duas functions de negócio, duas procedures, três triggers habilitadas e três índices adicionais. Dados comparados por contagem e assinatura do conteúdo:

| Tabela | Registros | Comparação origem/Neon |
|---|---:|---|
| users | 7 | Idêntica |
| categories | 4 | Idêntica |
| books | 16 | Idêntica |
| loans | 0 | Idêntica |
| loan_audits | 0 | Idêntica |
| migrations | 10 | Idêntica |

Não foram copiados dados de sessões, cache, jobs, tokens de API, recuperação de senha ou entidades acadêmicas antigas. Essas tabelas foram criadas vazias no destino; dados locais continuam preservados. As dez migrations constam como Ran; não foi preciso reaplicá-las.

LoanService executou retirada/devolução no Neon, confirmou situação do exemplar, contagem de ativos e duas auditorias. A transação externa foi revertida: nenhum usuário/empréstimo/auditoria de teste persistiu. Sequências podem avançar com ROLLBACK, comportamento normal do PostgreSQL. A suite completa rodou somente no PostgreSQL local isolado: **46 testes, 248 assertions, todos aprovados**. Não houve RefreshDatabase no Neon.

## Configuração ativa

O .env agora aponta para o Neon validado. A configuração local anterior foi preservada em **.env.local**; arquivos de ambiente continuam ignorados pelo Git. O PostgreSQL local da porta 55432 e seus dados permanecem intactos. Não há replicação automática entre local e Neon.

Para voltar ao local: preserve a configuração ativa, copie o arquivo privado .env.local para .env, execute `php artisan config:clear` e reinicie o servidor. Não execute seed de demonstração no Neon: usuários/acervo já foram transferidos. Tokens anteriores não foram copiados; emita novos pela CLI se necessário. Contas de demonstração herdadas destinam-se ao desenvolvimento, não à publicação pública.

## Evidências reais

- evidencias/neon-transferencia.txt: backups e comparação de dados.
- evidencias/neon-validacao.txt: catálogo SQL e operações Laravel revertidas.
- evidencias/neon-testes-isolados.txt: suite isolada e resultado do conector.

Ferramentas: scripts/check-neon.php faz diagnóstico de leitura; scripts/validate-neon.php faz validação transacional sem migrations; scripts/transfer-neon.php recusa destino preenchido e origem diferente do banco local esperado. Após ativação do Neon no .env, a transferência também recusa a origem incorreta. Não reexecute contra o destino preenchido. Ferramentas exigem pdo_pgsql, habilitado nesta máquina por PHP_INI_SCAN_DIR=scripts/php-conf. Saídas privadas de erros externos são omitidas. Nenhum arquivo de ambiente ou dump deve ser publicado. Não houve push/publicação do sistema.
