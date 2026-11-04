"""Gera o PDF obrigatório a partir do README único, sem criar outra documentação."""
from pathlib import Path
import re
import textwrap

root = Path(__file__).resolve().parent.parent
source = root / "README.md"
target = root / "docs/RELATORIO_ENTREGA.pdf"
lines = [("Relatório de entrega — Bibliotequinha da Rhay 2.0", True), ("Autora: Rhayanna | Revisão para entrega em 04/11/2026", False), ("", False)]
for paragraph in source.read_text(encoding="utf-8").splitlines():
    if paragraph.startswith("```"):
        continue
    paragraph = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", r"\1 (\2)", paragraph)
    paragraph = paragraph.replace("`", "")
    heading = paragraph.startswith("#")
    text = re.sub(r"^#+\s*", "", paragraph).replace("**", "")
    for line in textwrap.wrap(text, width=94) or [""]:
        lines.append((line, heading))

pages = [lines[i:i + 49] for i in range(0, len(lines), 49)]
objects = [b"<< /Type /Catalog /Pages 2 0 R >>", b"", b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>", b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>"]
page_ids = []
for number, page in enumerate(pages, 1):
    page_id = len(objects) + 1
    stream_id = page_id + 1
    page_ids.append(page_id)
    commands = ["BT", "50 790 Td", "14 TL"]
    for line, heading in page:
        escaped = line.replace("\\", "\\\\").replace("(", "\\(").replace(")", "\\)")
        commands.extend([f"/{'F2' if heading else 'F1'} 10 Tf", f"({escaped}) Tj", "T*"])
    commands.extend(["ET", "BT /F1 9 Tf 50 30 Td", f"(Bibliotequinha da Rhay | {number}/{len(pages)}) Tj ET"])
    stream = "\n".join(commands).encode("cp1252", errors="replace")
    objects.append(f"<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {stream_id} 0 R >>".encode())
    objects.append(f"<< /Length {len(stream)} >>\nstream\n".encode() + stream + b"\nendstream")
objects[1] = f"<< /Type /Pages /Count {len(pages)} /Kids [{' '.join(f'{pid} 0 R' for pid in page_ids)}] >>".encode()
pdf = bytearray(b"%PDF-1.4\n%\xe2\xe3\xcf\xd3\n")
offsets = [0]
for index, obj in enumerate(objects, 1):
    offsets.append(len(pdf))
    pdf.extend(f"{index} 0 obj\n".encode() + obj + b"\nendobj\n")
xref = len(pdf)
pdf.extend(f"xref\n0 {len(objects) + 1}\n0000000000 65535 f \n".encode())
for offset in offsets[1:]:
    pdf.extend(f"{offset:010d} 00000 n \n".encode())
pdf.extend(f"trailer\n<< /Size {len(objects) + 1} /Root 1 0 R >>\nstartxref\n{xref}\n%%EOF\n".encode())
target.write_bytes(pdf)
print(f"PDF gerado: {target.name}, {len(pages)} páginas, {len(pdf)} bytes")
