import fs from 'node:fs';
import path from 'node:path';
import { randomBytes } from 'node:crypto';
import nodemailer from 'nodemailer';
import { PDFDocument, StandardFonts, rgb } from '@cantoo/pdf-lib';
import { getOrder, updateOrderStatus } from './db.js';

function getMailer() {
  const host = process.env.SMTP_HOST || 'localhost';
  const port = parseInt(process.env.SMTP_PORT, 10) || (host === 'localhost' ? 25 : 465);
  const secure = port === 465;
  const user = process.env.SMTP_USER;
  const pass = process.env.SMTP_PASS;

  const config = {
    host,
    port,
    secure,
    tls: { rejectUnauthorized: false }
  };

  if (user && pass) {
    config.auth = { user, pass };
  }

  return nodemailer.createTransport(config);
}

export async function testSmtp(recipientEmail) {
  const mailer = getMailer();
  const from = process.env.SMTP_FROM || `Toko Restia Moegiono <order@restiamoegiono.com>`;
  const info = await mailer.sendMail({
    from,
    to: recipientEmail,
    subject: 'Tes Pengiriman Email Toko E-Book',
    text: 'Halo! Ini adalah email tes dari sistem toko e-book Restia Moegiono. Konfigurasi SMTP Anda berfungsi dengan baik.',
    html: `
      <div style="font-family: sans-serif; padding: 20px; color: #0f172a;">
        <h2 style="color: #0284c7;">Konfigurasi Email Berhasil! ✅</h2>
        <p>Email ini dikirim otomatis untuk menguji koneksi SMTP server Anda.</p>
        <p style="color: #64748b; font-size: 13px;">Waktu tes: ${new Date().toLocaleString('id-ID')}</p>
      </div>
    `
  });
  return info;
}

function generate6CharPassword() {
  const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
  let pass = '';
  const bytes = randomBytes(6);
  for (let i = 0; i < 6; i++) {
    pass += chars[bytes[i] % chars.length];
  }
  return pass;
}

export async function fulfillOrder(invoiceNumber) {
  const order = getOrder(invoiceNumber);
  if (!order) throw new Error(`Pesanan dengan invoice ${invoiceNumber} tidak ditemukan.`);

  const name = String(order.customer_name || 'Pembeli');
  const email = String(order.customer_email || '');
  const organization = String(order.organization || '-');
  const title = String(order.product_title || 'E-Book');
  const productId = String(order.product_id || '');

  if (!email) throw new Error('Email pembeli tidak valid.');

  const licenseId = order.license_id || `LIC-${Date.now().toString(36).toUpperCase()}-${randomBytes(3).toString('hex').toUpperCase()}`;
  const pdfPassword = order.pdf_password || generate6CharPassword();

  // Update status to PROCESSING
  updateOrderStatus(invoiceNumber, {
    status: 'PROCESSING',
    license_id: licenseId,
    pdf_password: pdfPassword
  });

  const ebooksDir = path.join(process.cwd(), 'storage', 'ebooks');
  const masterFile = path.join(ebooksDir, `${productId}.pdf`);

  let finalPdfBytes;

  if (fs.existsSync(masterFile)) {
    // Process master PDF
    const sourceBytes = fs.readFileSync(masterFile);
    const pdf = await PDFDocument.load(sourceBytes);
    const font = await pdf.embedFont(StandardFonts.Helvetica);
    const watermarkText = `Lisensi Resmi: ${name} (${email}) | Organisasi: ${organization} | ID: ${licenseId} | Hak Cipta Dilindungi UU No. 28/2014`;

    for (const page of pdf.getPages()) {
      const { width } = page.getSize();
      page.drawText(watermarkText, {
        x: 24,
        y: 18,
        size: 7,
        font,
        color: rgb(0.35, 0.4, 0.48),
        opacity: 0.85,
        maxWidth: Math.max(100, width - 48)
      });
    }

    pdf.encrypt({
      userPassword: pdfPassword,
      ownerPassword: randomBytes(32).toString('base64url'),
      permissions: {
        printing: 'lowResolution',
        copying: false,
        modifying: false,
        annotating: false,
        fillingForms: false,
        contentAccessibility: true,
        documentAssembly: false
      }
    });

    finalPdfBytes = Buffer.from(await pdf.save());
  } else {
    // Generate certified license document if master PDF not uploaded yet
    const pdf = await PDFDocument.create();
    const page = pdf.addPage([595, 842]); // A4
    const fontBold = await pdf.embedFont(StandardFonts.HelveticaBold);
    const fontRegular = await pdf.embedFont(StandardFonts.Helvetica);

    page.drawText('SERTIFIKAT LISENSI RESMI E-BOOK', {
      x: 50,
      y: 760,
      size: 18,
      font: fontBold,
      color: rgb(0.01, 0.52, 0.78)
    });

    page.drawText(`Judul: ${title}`, { x: 50, y: 710, size: 14, font: fontBold, color: rgb(0.06, 0.09, 0.16) });
    page.drawText(`Nomor Invoice: ${invoiceNumber}`, { x: 50, y: 670, size: 11, font: fontRegular });
    page.drawText(`ID Lisensi: ${licenseId}`, { x: 50, y: 645, size: 11, font: fontRegular });
    page.drawText(`Nama Penerima: ${name}`, { x: 50, y: 620, size: 11, font: fontRegular });
    page.drawText(`Email: ${email}`, { x: 50, y: 595, size: 11, font: fontRegular });
    page.drawText(`Organisasi: ${organization}`, { x: 50, y: 570, size: 11, font: fontRegular });
    page.drawText(`Tanggal Pembelian: ${new Date().toLocaleDateString('id-ID')}`, { x: 50, y: 545, size: 11, font: fontRegular });

    page.drawText('Dokumen ini merupakan bukti kepemilikan lisensi resmi yang sah diterbitkan oleh', { x: 50, y: 480, size: 10, font: fontRegular, color: rgb(0.3, 0.35, 0.45) });
    page.drawText('Restia Moegiono Official Store (store.restiamoegiono.com).', { x: 50, y: 462, size: 10, font: fontRegular, color: rgb(0.3, 0.35, 0.45) });

    page.drawText(`Password pembuka PDF: ${pdfPassword}`, { x: 50, y: 400, size: 11, font: fontBold, color: rgb(0.01, 0.52, 0.78) });

    pdf.encrypt({
      userPassword: pdfPassword,
      ownerPassword: randomBytes(32).toString('base64url'),
      permissions: { printing: 'highResolution', copying: true }
    });

    finalPdfBytes = Buffer.from(await pdf.save());
  }

  const safeFileName = `${title.replace(/[\/\\:*?"<>|]/g, '-').slice(0, 80)} - ${name}.pdf`;
  const mailer = getMailer();
  const from = process.env.SMTP_FROM || `Restia Moegiono Official <order@restiamoegiono.com>`;

  // 1. Send Email with Attachment
  await mailer.sendMail({
    from,
    to: email,
    subject: `E-Book Anda: ${title}`,
    text: `Halo ${name},\n\nTerima kasih atas pesanan Anda (${invoiceNumber}). Terlampir file PDF e-book Anda.\n\nPassword untuk membuka PDF akan dikirimkan dalam email terpisah beberapa saat lagi.\n\nSalam,\nRestia Moegiono Official`,
    html: `
      <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #0f172a; line-height: 1.6;">
        <div style="background: #0284c7; padding: 20px; border-radius: 12px; text-align: center; color: #ffffff; margin-bottom: 24px;">
          <h2 style="margin: 0; font-size: 20px;">E-Book Resmi Anda Siap! 📚</h2>
        </div>
        <p>Halo <b>${name}</b>,</p>
        <p>Terima kasih atas pembelian Anda di <b>Restia Moegiono Official Store</b>. Pembayaran Anda telah terkonfirmasi.</p>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 20px 0;">
          <div style="font-size: 13px; color: #64748b;">Detail Pesanan:</div>
          <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;">${title}</div>
          <div style="font-size: 13px; color: #475569; margin-top: 6px;">Invoice: <b>${invoiceNumber}</b> | Lisensi: <b>${licenseId}</b></div>
        </div>
        <p>Berkas PDF e-book telah dipersonalisasi dengan watermarking lisensi resmi atas nama dan identitas Anda di setiap footer halaman.</p>
        <p style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 4px; font-size: 13px; color: #92400e;">
          ⚠️ <b>Penting:</b> Berkas PDF dilindungi kata sandi 6 karakter (kombinasi angka &amp; huruf kecil). <b>Password pembuka telah dikirimkan secara otomatis dalam email terpisah</b> demi keamanan lisensi Anda.
        </p>
        <p style="font-size: 13px; color: #64748b; margin-top: 32px;">Salam hangat,<br><b>Restia Moegiono Official</b><br><a href="https://store.restiamoegiono.com" style="color: #0284c7; text-decoration: none;">store.restiamoegiono.com</a></p>
      </div>
    `,
    attachments: [
      {
        filename: safeFileName,
        content: finalPdfBytes,
        contentType: 'application/pdf'
      }
    ]
  });

  // 2. Send Separate Email with Password
  await mailer.sendMail({
    from,
    to: email,
    subject: `Password Pembuka PDF: ${title}`,
    text: `Halo ${name},\n\nBerikut password (6 karakter) untuk membuka file PDF e-book ${title}:\n\nPassword: ${pdfPassword}\n\nFormat password terdiri dari 6 karakter kombinasi angka dan huruf kecil.\n\nPERINGATAN HAK CIPTA & LISENSI:\nBerkas PDF ini memuat watermark lisensi personal Anda di setiap halaman. Dilarang menggandakan, mendistribusikan, atau menyebarluaskan dokumen ini kepada pihak lain.\n\nSalam,\nRestia Moegiono Official`,
    html: `
      <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #0f172a; line-height: 1.6;">
        <div style="background: #0f172a; padding: 20px; border-radius: 12px; text-align: center; color: #ffffff; margin-bottom: 24px;">
          <h2 style="margin: 0; font-size: 20px;">Kunci Sandi Pembuka PDF 🔐</h2>
        </div>
        <p>Halo <b>${name}</b>,</p>
        <p>Berikut adalah kode sandi (password) resmi untuk membuka file PDF e-book <b>${title}</b>:</p>
        <div style="background: #f1f5f9; border: 2px dashed #0284c7; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
          <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700;">Password Pembuka (6 Karakter):</div>
          <div style="font-size: 28px; font-weight: 900; letter-spacing: 4px; color: #0284c7; margin-top: 8px; user-select: all; font-family: monospace;">${pdfPassword}</div>
          <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Format: 6 digit angka dan huruf kecil</div>
        </div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; font-size: 13px; color: #334155; margin-bottom: 20px;">
          <b>Petunjuk Membuka:</b>
          <ol style="margin-left: 20px; margin-top: 6px; padding: 0;">
            <li>Buka file lampiran PDF dari email sebelumnya menggunakan Adobe Acrobat Reader, Google Chrome, atau pembaca PDF standar.</li>
            <li>Salin atau ketik kode 6 karakter di atas dengan huruf kecil sesuai format.</li>
            <li>Tekan tombol Buka / Enter untuk mengakses isi dokumen lengkap.</li>
          </ol>
        </div>
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px; font-size: 12px; color: #991b1b;">
          🛡️ <b>Pemberitahuan Lisensi &amp; Keamanan Konten:</b><br>
          Dokumen PDF Anda telah disematkan identitas lisensi resmi di setiap footer halaman. Dilarang mendistribusikan, membagikan, atau mengunggah dokumen ini ke domain publik tanpa izin tertulis dari Penulis sesuai UU Hak Cipta No. 28 Tahun 2014.
        </div>
        <p style="font-size: 13px; color: #64748b; margin-top: 28px;">Salam hangat,<br><b>Restia Moegiono Official</b><br><a href="https://store.restiamoegiono.com" style="color: #0284c7; text-decoration: none;">store.restiamoegiono.com</a></p>
      </div>
    `
  });

  // Update order status to SENT
  const updatedOrder = updateOrderStatus(invoiceNumber, {
    status: 'SENT',
    sent_at: new Date().toISOString()
  });

  return { success: true, licenseId, order: updatedOrder };
}
