"""Organiza materiais exclusivamente locais por lista explícita, sem ambientes/segredos."""
from pathlib import Path
from urllib.parse import urlsplit, unquote
from hashlib import sha256
import json

root = Path(__file__).resolve().parent.parent
destination = root / "storage/app/entrega-local"
destination.mkdir(parents=True, exist_ok=True)
files = [
    "README.md",
    "docs/evidencias/finalizacao-local.txt", "docs/evidencias/postgres-validacao.txt",
    "docs/evidencias/neon-transferencia.txt", "docs/evidencias/neon-validacao.txt",
    "docs/evidencias/neon-testes-isolados.txt",
    "database/sql/00_estrutura.sql", "database/sql/01_library_objects.sql",
    "database/sql/02_demonstracao.sql", "database/sql/03_dados_ficticios.sql",
    "database/sql/04_validacao_local.sql",
]
# Somente para auditoria em memória: valores privados nunca são copiados ou exibidos.
secrets = set()
for filename in [".env", ".env.neon", ".env.local", ".env.testing", ".env.gravacao"]:
    path = root / filename
    if not path.exists():
        continue
    for line in path.read_text(encoding="utf-8-sig").splitlines():
        key, separator, value = line.partition("=")
        value = value.strip().strip('"').strip("'")
        if separator and (key == "APP_KEY" or "PASSWORD" in key or key == "DB_URL") and len(value) >= 8:
            secrets.add(value.encode())
            if key == "DB_URL":
                password = urlsplit(value).password
                if password and len(unquote(password)) >= 8:
                    secrets.add(unquote(password).encode())

entries = []
sources = [(root / name, name) for name in files]
sources += [(destination / name, (destination / name).relative_to(root).as_posix()) for name in ["backup-ficticio.dump", "backup-ficticio.sha256"]]
for source, relative in sources:
    if not source.is_file() or any(part.startswith(".env") for part in Path(relative).parts):
        raise SystemExit("Material ausente ou arquivo de ambiente recusado; entrega não preparada.")
    data = source.read_bytes()
    if any(secret in data for secret in secrets):
        raise SystemExit("Conteúdo privado detectado; entrega interrompida sem exibir o segredo.")
    entries.append({"arquivo": relative, "bytes": len(data), "sha256": sha256(data).hexdigest()})

manifest = {
    "projeto": "Bibliotequinha da Rhay 2.0", "revisao": "2026-11-04",
    "publicacao": "Entrega ao GitHub autorizada em 04/11/2026; este script não envia arquivos",
    "conteudo": "Materiais complementares. Código-fonte permanece no workspace local.",
    "backup": "Exclusivamente fictício; não contém credenciais utilizáveis ou dados do Neon.",
    "pendencias": ["Ensaiar arguição"],
    "arquivos": entries,
}
manifest_path = destination / "manifesto-entrega.json"
manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
print(f"Manifesto local atualizado: {len(sources)} arquivos; sem arquivos .env ou valores secretos detectados.")
print("Sem cópias duplicadas ou ZIP. Os materiais permanecem nos caminhos listados; nenhum envio executado.")
