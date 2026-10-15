#!/usr/bin/env python3
import sys
import os
import argparse
import io
from pypdf import PdfReader, PdfWriter
from reportlab.pdfgen import canvas
from reportlab.lib.colors import HexColor

def apply_watermark(input_pdf, output_pdf, title, name, email, org, invoice, password=""):
    if not os.path.exists(input_pdf):
        raise FileNotFoundError(f"Input file not found: {input_pdf}")

    reader = PdfReader(input_pdf)
    writer = PdfWriter()

    org_str = org if org and org.strip() else "-"
    watermark_text = f'"{title}" ini karya Restia Moegiono, salinan ini dibuat untuk "{name}" | "{email}" | "{org_str}"'

    for idx, page in enumerate(reader.pages):
        w = float(page.mediabox.width)
        h = float(page.mediabox.height)

        packet = io.BytesIO()
        can = canvas.Canvas(packet, pagesize=(w, h))
        
        # Subtle separator line at footer
        can.setStrokeColor(HexColor('#cbd5e1'))
        can.setLineWidth(0.5)
        can.line(36, 30, w - 36, 30)

        # Footer Watermark Text
        can.setFillColor(HexColor('#475569'))
        can.setFont("Helvetica-Bold", 7.5)
        can.drawCentredString(w / 2.0, 18, watermark_text)

        # Subtle top-right verification badge
        can.setFillColor(HexColor('#94a3b8'))
        can.setFont("Helvetica", 6.5)
        can.drawRightString(w - 36, h - 22, f"Lisensi Resmi: {invoice} | Halaman {idx + 1}")

        can.save()
        packet.seek(0)

        overlay_reader = PdfReader(packet)
        overlay_page = overlay_reader.pages[0]
        page.merge_page(overlay_page)
        writer.add_page(page)

    # Proteksi Password PDF Kriptografis AES-256 jika diberikan
    if password and password.strip():
        writer.encrypt(
            user_password=password.strip(),
            owner_password=os.environ.get("PDF_OWNER_KEY", "RestiaMasterAudit2026!"),
            algorithm="AES-256"
        )

    os.makedirs(os.path.dirname(os.path.abspath(output_pdf)), exist_ok=True)
    with open(output_pdf, "wb") as f_out:
        writer.write(f_out)

    return True

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--title", required=True)
    parser.add_argument("--name", required=True)
    parser.add_argument("--email", required=True)
    parser.add_argument("--org", default="")
    parser.add_argument("--invoice", required=True)
    parser.add_argument("--password", default="")
    args = parser.parse_args()

    try:
        apply_watermark(args.input, args.output, args.title, args.name, args.email, args.org, args.invoice, args.password)
        print("OK")
    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        sys.exit(1)
