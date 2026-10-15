<?php
$invoice = htmlspecialchars($_GET['invoice'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran &amp; Pengiriman — Restia Moegiono Store</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      background-color: #F8FAFC; color: #0F224A; line-height: 1.6;
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .card {
      background: #ffffff; border: 1px solid #D6EAFF; border-radius: 28px;
      padding: 44px 36px; max-width: 520px; width: 100%; text-align: center;
      box-shadow: 0 20px 50px rgba(15, 34, 74, 0.08);
    }
    .icon-box {
      width: 76px; height: 76px; border-radius: 50%; margin: 0 auto 20px;
      display: flex; align-items: center; justify-content: center; font-size: 36px;
    }
    .icon-success { background: #E8F3FF; color: #00AEFF; border: 2px solid #00AEFF; box-shadow: 0 0 16px rgba(0, 174, 255, 0.35); }
    .icon-pending { background: #FFF9E6; color: #F2B200; border: 2px solid #FDE68A; }
    .title { font-size: 24px; font-weight: 900; margin-bottom: 8px; color: #0F224A; }
    .sub { font-size: 14px; color: #64748b; margin-bottom: 24px; }
    
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
      box-shadow: 0 6px 18px rgba(0, 174, 255, 0.35);
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

    <div id="noticePaid" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:14px; font-size:13px; color:#065f46; margin-bottom:20px;">
      ✓ Berkas PDF e-book dengan stempel watermark lisensi resmi Anda telah berhasil dikirimkan ke email. Silakan periksa kotak masuk (inbox) atau folder spam.
    </div>

    <a href="/" class="btn-home">Kembali ke Toko</a>
  </div>

  <script>
    const invoice = '<?= $invoice ?>' || localStorage.getItem('rm_last_invoice') || '';
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
        if (data.success && data.order) {
          const o = data.order;
          document.getElementById('dispProduct').innerText = o.product_title;
          document.getElementById('dispEmail').innerText = o.customer_email;

          if (o.is_paid) {
            document.getElementById('statusIcon').className = 'icon-box icon-success';
            document.getElementById('statusIcon').innerText = '✓';
            document.getElementById('statusTitle').innerText = 'Pembayaran Berhasil!';
            document.getElementById('statusSub').innerText = 'Terima kasih! Pesanan Anda telah terverifikasi resmi oleh DOKU.';
            document.getElementById('dispStatus').innerHTML = '<span class="status-badge status-badge-paid">✓ LUNAS</span>';
            document.getElementById('noticePaid').style.display = 'block';

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
  </script>
</body>
</html>
