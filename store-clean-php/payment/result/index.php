<?php
$invoice = htmlspecialchars($_GET['invoice'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran &amp; Pengiriman — Restia Moegiono Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --font-title: clamp(1.35rem, 2.5vw + 0.3rem, 1.8rem);
      --font-body: clamp(0.875rem, 0.3vw + 0.8rem, 1rem);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { text-size-adjust: 100%; -webkit-text-size-adjust: 100%; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: #F8FAFC; color: #0F224A; line-height: 1.6;
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: clamp(14px, 3.5vw, 28px);
      -webkit-font-smoothing: antialiased; touch-action: manipulation; -webkit-tap-highlight-color: transparent;
    }
    .card {
      background: #ffffff; border: 1px solid #D6EAFF; border-radius: 28px;
      padding: clamp(28px, 4vw, 44px) clamp(20px, 3.5vw, 36px); max-width: 520px; width: 100%; text-align: center;
      box-shadow: 0 20px 50px rgba(15, 34, 74, 0.08);
    }
    .icon-box {
      width: 76px; height: 76px; border-radius: 50%; margin: 0 auto 20px;
      display: flex; align-items: center; justify-content: center; font-size: 36px;
    }
    .icon-success { background: #E8F3FF; color: #00AEFF; border: 2px solid #00AEFF; box-shadow: 0 0 16px rgba(0, 174, 255, 0.35); }
    .icon-pending { background: #FFF9E6; color: #F2B200; border: 2px solid #FDE68A; }
    .title { font-size: var(--font-title); font-weight: 900; margin-bottom: 8px; color: #0F224A; text-wrap: balance; }
    .sub { font-size: var(--font-body); color: #64748b; margin-bottom: 24px; }
    
    .detail-box {
      background: #E8F3FF; border: 1px solid #D6EAFF; border-radius: 16px;
      padding: 18px 20px; margin-bottom: 24px; text-align: left; font-size: 13px;
    }
    .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
    .detail-row:last-child { margin-bottom: 0; }
    .lbl { color: #64748b; font-weight: 600; }
    .val { color: #0F224A; font-weight: 800; text-align: right; }

    .status-badge {
      display: inline-block; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 12px;
    }
    .status-badge-paid { background: #E8F3FF; color: #0F224A; border: 1px solid #00AEFF; }
    .status-badge-pending { background: #FFF9E6; color: #92400E; border: 1px solid #FDE68A; }

    .btn-home {
      display: inline-block; width: 100%; padding: 14px; border-radius: 12px;
      background: linear-gradient(135deg, #00AEFF, #0F224A); color: #ffffff; font-size: 14px; font-weight: 800;
      text-decoration: none; transition: transform 0.15s; margin-top: 10px;
      box-shadow: 0 6px 18px rgba(0, 174, 255, 0.35); touch-action: manipulation; min-height: 46px;
    }
    .btn-home:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(0, 174, 255, 0.45); }
  </style>
</head>
<body>
  <div class="card">
    <div id="statusIcon" class="icon-box icon-pending">⏳</div>
    <h1 id="statusTitle" class="title">Memeriksa Status Pembayaran...</h1>
    <p id="statusSub" class="sub">Menghubungkan ke sistem verifikasi DOKU Payment Gateway...</p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="lbl">No. Invoice:</span>
        <span class="val" id="dispInvoice"><?= $invoice ?: '-' ?></span>
      </div>
      <div class="detail-row">
        <span class="lbl">Produk:</span>
        <span class="val" id="dispProduct">-</span>
      </div>
      <div class="detail-row">
        <span class="lbl">Alamat Email:</span>
        <span class="val" id="dispEmail">-</span>
      </div>
      <div class="detail-row">
        <span class="lbl">Status Bayar:</span>
        <span class="val" id="dispStatus"><span class="status-badge status-badge-pending">MEMERIKSA...</span></span>
      </div>
      <div class="detail-row" id="rowEmailSent" style="display:none;">
        <span class="lbl">Pengiriman E-Book:</span>
        <span class="val" style="color:#059669; font-weight:800;">✓ TERKIRIM KE EMAIL</span>
      </div>
    </div>

    <div id="noticePaid" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:14px; font-size:13px; color:#065f46; margin-bottom:16px;">
      ✓ Berkas PDF e-book dengan stempel watermark lisensi resmi Anda telah siap diunduh di bawah ini. Tautan cadangan juga telah dikirimkan ke kotak masuk email Anda.
    </div>

    <!-- KARTU PASSWORD PDF -->
    <div id="passwordCard" style="display:none; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 2px dashed #059669; border-radius: 16px; padding: 20px; margin-bottom: 20px; text-align: center;">
      <div style="font-size: 11px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
        🔐 Password Buka Berkas PDF Anda
      </div>
      <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 8px 0;">
        <span id="dispPdfPassword" style="font-size: 32px; font-weight: 900; color: #065f46; letter-spacing: 6px; font-family: 'SF Mono', Consolas, Monaco, monospace;">------</span>
        <button id="btnCopyPwd" onclick="copyPassword()" type="button" style="background: #059669; color: #ffffff; border: none; border-radius: 8px; padding: 8px 14px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: background 0.15s;">Salin</button>
      </div>
      <div style="font-size: 12px; color: #047857;">
        Gunakan 6 karakter acak di atas saat membuka berkas PDF hasil unduhan Anda.
      </div>
    </div>

    <!-- KARTU DAFTAR UNDUHAN E-BOOK LANGSUNG -->
    <div id="downloadButtonsCard" style="display:none; background:#ffffff; border:1px solid #D6EAFF; border-radius:18px; padding:20px; margin-bottom:20px; text-align:left;">
      <div style="font-size:13px; font-weight:800; color:#0F224A; margin-bottom:12px; display:flex; align-items:center; gap:6px;">
        <span>📥</span> Unduh Berkas E-Book Anda Sekarang:
      </div>
      <div id="downloadListContainer" style="display:flex; flex-direction:column; gap:10px;">
        <!-- Rendered by JS -->
      </div>
      <div style="font-size:11.5px; color:#64748b; margin-top:12px; line-height:1.4;">
        💡 <b>Tips:</b> Berkas PDF telah dilindungi stempel watermark nama dan lisensi Anda. Gunakan aplikasi Adobe Acrobat Reader / Google Chrome untuk pengalaman membaca terbaik.
      </div>
    </div>

    <a href="/" class="btn-home">Kembali ke Toko</a>
  </div>

  <script>
    const invoice = '<?= $invoice ?>' || localStorage.getItem('rm_last_invoice') || '';
    let currentPdfPassword = '';

    if (invoice) {
      document.getElementById('dispInvoice').innerText = invoice;
      checkStatus();
      const interval = setInterval(checkStatus, 4000);
    } else {
      document.getElementById('statusTitle').innerText = 'Invoice Tidak Ditemukan';
      document.getElementById('statusSub').innerText = 'Silakan pilih produk dan lakukan checkout dari katalog.';
    }

    async function checkStatus() {
      try {
        const res = await fetch('/api/order-status.php?invoice=' + encodeURIComponent(invoice));
        const data = await res.json();
        const o = data.order || (data.orders && data.orders[0]) || null;
        if (o) {
          if (o.items && o.items.length > 1) {
            document.getElementById('dispProduct').innerHTML = o.items.map(it => `<div style="font-size:12px; font-weight:800; margin-bottom:3px; text-align:right;">📘 ${it.title}</div>`).join('');
          } else {
            document.getElementById('dispProduct').innerText = o.product_title;
          }
          document.getElementById('dispEmail').innerText = o.customer_email;

          if (o.is_paid) {
            document.getElementById('statusIcon').className = 'icon-box icon-success';
            document.getElementById('statusIcon').innerText = '✓';
            document.getElementById('statusTitle').innerText = 'Pembayaran Berhasil!';
            document.getElementById('statusSub').innerText = 'Terima kasih! Pesanan Anda telah terverifikasi resmi oleh DOKU.';
            document.getElementById('dispStatus').innerHTML = '<span class="status-badge status-badge-paid">✓ LUNAS</span>';
            document.getElementById('noticePaid').style.display = 'block';

            if (o.pdf_password) {
              currentPdfPassword = o.pdf_password;
              document.getElementById('passwordCard').style.display = 'block';
              document.getElementById('dispPdfPassword').innerText = o.pdf_password;
            }

            // Render tombol unduh untuk setiap e-book
            if (o.items && o.items.length > 0) {
              const dlContainer = document.getElementById('downloadListContainer');
              dlContainer.innerHTML = o.items.map(it => {
                const url = it.download_url || (`/download.php?inv=${encodeURIComponent(o.invoice)}&prod=${encodeURIComponent(it.id)}`);
                return `
                  <div style="background:#F8FAFC; border:1px solid #D6EAFF; border-radius:12px; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                    <div style="font-size:12.5px; font-weight:800; color:#0F224A; flex:1; min-width:180px;">📘 ${it.title}</div>
                    <a href="${url}" target="_blank" style="background:linear-gradient(135deg, #00AEFF, #0F224A); color:#ffffff; font-size:12px; font-weight:800; padding:8px 16px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:4px; box-shadow:0 3px 10px rgba(0,174,255,0.25);">
                      📥 Unduh (PDF)
                    </a>
                  </div>
                `;
              }).join('');
              document.getElementById('downloadButtonsCard').style.display = 'block';
            }

            if (o.email_sent) {
              document.getElementById('rowEmailSent').style.display = 'flex';
            }
          } else {
            document.getElementById('dispStatus').innerHTML = '<span class="status-badge status-badge-pending">' + o.status + '</span>';
          }
        }
      } catch (e) {
        console.error('Check status error:', e);
      }
    }

    function copyPassword() {
      if (!currentPdfPassword) return;
      navigator.clipboard.writeText(currentPdfPassword).then(() => {
        const btn = document.getElementById('btnCopyPwd');
        const orig = btn.innerText;
        btn.innerText = '✓ Tersalin!';
        btn.style.background = '#047857';
        setTimeout(() => {
          btn.innerText = orig;
          btn.style.background = '#059669';
        }, 2000);
      });
    }
  </script>
</body>
</html>
