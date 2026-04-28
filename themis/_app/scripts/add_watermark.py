#!/usr/bin/env python3
"""
Adiciona marca d'água centralizada em PDF mantendo texto vetorial.
"""
import sys, os, io, tempfile
from PIL import Image
from reportlab.pdfgen import canvas
from pypdf import PdfReader, PdfWriter

def criar_wm_pdf(wm_path, page_width, page_height):
    img = Image.open(wm_path).convert('RGBA')
    arr = list(img.getdata())
    nova = []
    for r, g, b, a in arr:
        brilho = (r + g + b) / 3
        if brilho < 25:
            nova.append((255, 255, 255, 0))
        else:
            nova.append((r, g, b, int(brilho * 0.22)))
    img.putdata(nova)

    tmp_png = tempfile.NamedTemporaryFile(suffix='.png', delete=False)
    img.save(tmp_png.name, 'PNG')
    tmp_png.close()

    buf = io.BytesIO()
    c = canvas.Canvas(buf, pagesize=(page_width, page_height))

    # Centralizar: reportlab origem é no canto INFERIOR esquerdo
    # page_width=595, page_height=842 (A4 em pontos)
    sz = min(page_width, page_height) * 0.65  # 65% da página
    x  = (page_width  - sz) / 2               # centralizado horizontalmente
    y  = (page_height - sz) / 2               # centralizado verticalmente

    c.drawImage(tmp_png.name, x, y, width=sz, height=sz, mask='auto')
    c.save()
    os.unlink(tmp_png.name)
    buf.seek(0)
    return buf

def adicionar_watermark(input_pdf, wm_path, output_pdf):
    reader = PdfReader(input_pdf)
    writer = PdfWriter()
    for page in reader.pages:
        pw = float(page.mediabox.width)
        ph = float(page.mediabox.height)
        wm_buf  = criar_wm_pdf(wm_path, pw, ph)
        wm_page = PdfReader(wm_buf).pages[0]
        # Mesclar: watermark ATRÁS do conteúdo
        wm_page.merge_page(page)
        writer.add_page(wm_page)
    with open(output_pdf, 'wb') as f:
        writer.write(f)

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Uso: python3 add_watermark.py <input.pdf> <watermark.png> [output.pdf]")
        sys.exit(1)
    input_pdf  = sys.argv[1]
    wm_path    = sys.argv[2]
    output_pdf = sys.argv[3] if len(sys.argv) > 3 else input_pdf
    if not os.path.exists(input_pdf):
        print(f"ERRO: {input_pdf}", file=sys.stderr); sys.exit(1)
    if not os.path.exists(wm_path):
        print(f"ERRO: {wm_path}", file=sys.stderr); sys.exit(1)
    try:
        if input_pdf == output_pdf:
            tmp = input_pdf + '.tmp.pdf'
            adicionar_watermark(input_pdf, wm_path, tmp)
            os.replace(tmp, output_pdf)
        else:
            adicionar_watermark(input_pdf, wm_path, output_pdf)
        print(f"OK:{output_pdf}")
    except Exception as e:
        print(f"ERRO: {e}", file=sys.stderr); sys.exit(1)
