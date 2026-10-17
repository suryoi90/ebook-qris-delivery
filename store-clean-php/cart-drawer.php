<!-- ==========================================
     KOMPONEN KERANJANG BELANJA (CART DRAWER)
     Restia Moegiono Official Store
     ========================================== -->
<div id="cartOverlay" class="cart-overlay" onclick="CartEngine.close()"></div>

<aside id="cartDrawer" class="cart-drawer" aria-label="Keranjang Belanja">
  <!-- HEADER DRAWER -->
  <div class="cart-header">
    <div class="cart-header-title-group">
      <div class="cart-header-icon">🛒</div>
      <div>
        <h2 class="cart-header-title">Keranjang Belanja</h2>
        <div class="cart-header-sub">
          <span id="cartCountText">0</span> e-book dipilih
        </div>
      </div>
    </div>
    <button type="button" class="btn-close-cart" onclick="CartEngine.close()" title="Tutup Keranjang" aria-label="Tutup">&times;</button>
  </div>

  <!-- ISI KERANJANG (BODY) -->
  <div class="cart-body" id="cartBody">
    <!-- EMPTY STATE -->
    <div id="cartEmptyState" class="cart-empty-state" style="display:none;">
      <div class="empty-icon-wrap">
        <div class="empty-icon-circle">🛒</div>
      </div>
      <h3 class="empty-title">Keranjang Anda Masih Kosong</h3>
      <p class="empty-desc">
        Pilih e-book panduan kepatuhan regulasi &amp; literasi keamanan siber resmi karya Restia Moegiono untuk ditambahkan ke keranjang belanja Anda.
      </p>
      <button type="button" class="btn-cart-browse" onclick="CartEngine.browseCatalog()">
        Jelajahi Katalog E-Book ➔
      </button>
    </div>

    <!-- CART ITEMS CONTAINER -->
    <div id="cartActiveState">
      <!-- DAFTAR ITEM PRODUK -->
      <div class="cart-section-label">Daftar E-Book Berlisensi</div>
      <div id="cartItemsList" class="cart-items-list">
        <!-- Rendered by JS -->
      </div>

      <!-- KUPON PROMO -->
      <div class="cart-coupon-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <span style="font-size:12px; font-weight:800; color:var(--color-navy);">🏷️ Kode Voucher Promo</span>
          <span id="cartCouponStatus" style="font-size:11px; font-weight:700; color:#059669; display:none;">✓ Terpasang</span>
        </div>
        <div class="cart-coupon-input-group" id="cartCouponInputWrap">
          <input type="text" id="inputCartCoupon" placeholder="Ketik kode kupon..." style="text-transform:uppercase;" class="cart-input">
          <button type="button" id="btnApplyCartCoupon" onclick="CartEngine.applyCoupon()" class="btn-coupon-apply">
            Terapkan
          </button>
        </div>
        <div id="cartCouponAppliedRow" class="cart-coupon-applied-pill" style="display:none;">
          <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-weight:800;" id="dispCartCouponCode">PROMO</span>
            <span style="font-size:11px;" id="dispCartCouponDiscount">(-Rp 0)</span>
          </div>
          <button type="button" onclick="CartEngine.removeCoupon()" class="btn-cancel-coupon" title="Hapus Kupon">Batal</button>
        </div>
      </div>

      <!-- FORM IDENTITAS LISENSI PEMBELI -->
      <div class="cart-buyer-card">
        <div class="cart-section-label" style="margin-bottom:10px;">📋 Identitas Pembeli (Untuk Stempel Lisensi DRM)</div>
        
        <div class="cart-field-group">
          <label class="cart-label">Nama Lengkap Penerima <span class="req-star">*</span></label>
          <input type="text" id="cartBuyerName" placeholder="Contoh: Budi Santoso, S.Kom., CISA" required class="cart-input">
          <span class="cart-input-hint">Dicantumkan pada watermark permanen di setiap halaman berkas PDF.</span>
        </div>

        <div class="cart-field-group">
          <label class="cart-label">Alamat Email Pembeli <span class="req-star">*</span></label>
          <input type="email" id="cartBuyerEmail" placeholder="nama@perusahaan.co.id" required class="cart-input">
          <span class="cart-input-hint">Seluruh berkas PDF &amp; password 6 karakter akan dikirimkan otomatis ke email ini.</span>
        </div>

        <div class="cart-field-row">
          <div class="cart-field-group" style="flex:1;">
            <label class="cart-label">Nomor WhatsApp</label>
            <input type="tel" id="cartBuyerWa" placeholder="081234567890" class="cart-input">
          </div>
          <div class="cart-field-group" style="flex:1;">
            <label class="cart-label">Instansi / Perusahaan</label>
            <input type="text" id="cartBuyerOrg" placeholder="PT Bank / BUMN / Pribadi" class="cart-input">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER DRAWER: RINGKASAN & CHECKOUT BUTTON -->
  <div class="cart-footer" id="cartFooter">
    <div class="cart-summary-table">
      <div class="summary-line">
        <span class="summary-lbl">Subtotal Produk:</span>
        <span class="summary-val" id="cartSubtotalText">Rp 0</span>
      </div>
      <div class="summary-line" id="rowCartDiscount" style="display:none; color:#059669;">
        <span class="summary-lbl">Potongan Kupon:</span>
        <span class="summary-val" id="cartDiscountText">- Rp 0</span>
      </div>
      <div class="summary-line summary-total">
        <span class="summary-total-lbl">Total Pembayaran:</span>
        <span class="summary-total-val" id="cartGrandTotalText">Rp 0</span>
      </div>
    </div>

    <button type="button" id="btnCartSubmitCheckout" class="btn-cart-checkout" onclick="CartEngine.processCheckout()">
      <span class="btn-checkout-lock">🔒</span>
      <span id="btnCartCheckoutText">Bayar Sekarang via DOKU</span>
      <span class="btn-checkout-arrow">➔</span>
    </button>

    <div class="cart-trust-badges">
      <span>🛡️ Lisensi Resmi</span> &bull; 
      <span>🔐 Password PDF 6-Karakter</span> &bull; 
      <span>⚡ DOKU QRIS &amp; Virtual Account</span>
    </div>
  </div>
</aside>

<!-- FLOATING CART BUTTON (BOTTOM RIGHT) -->
<button type="button" id="floatingCartBtn" class="floating-cart-pill" onclick="CartEngine.toggle()" aria-label="Buka Keranjang Belanja">
  <div class="floating-cart-icon-wrap">
    <span class="floating-cart-icon">🛒</span>
    <span id="floatingCartBadge" class="floating-cart-badge">0</span>
  </div>
  <div class="floating-cart-meta">
    <span class="floating-cart-title">Keranjang</span>
    <span id="floatingCartTotal" class="floating-cart-amount">Rp 0</span>
  </div>
</button>

<!-- NOTIFIKASI TOAST -->
<div id="cartToast" class="cart-toast"></div>

<!-- STYLES CART DRAWER -->
<style>
  /* Overlay */
  .cart-overlay {
    position: fixed; inset: 0;
    background: rgba(11, 27, 61, 0.65);
    backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
    z-index: 9998;
    opacity: 0; pointer-events: none;
    transition: opacity 0.3s ease;
  }
  .cart-overlay.open {
    opacity: 1; pointer-events: auto;
  }

  /* Drawer Container */
  .cart-drawer {
    position: fixed; top: 0; right: 0; bottom: 0;
    width: 100%; max-width: 480px;
    background: #ffffff;
    z-index: 9999;
    box-shadow: -12px 0 40px rgba(11, 27, 61, 0.25);
    display: flex; flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  }
  .cart-drawer.open {
    transform: translateX(0);
  }

  /* Header */
  .cart-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, #0F224A 0%, #0B1B3D 100%);
    color: #ffffff;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 2px solid #00AEFF;
    flex-shrink: 0;
  }
  .cart-header-title-group {
    display: flex; align-items: center; gap: 12px;
  }
  .cart-header-icon {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(0, 174, 255, 0.2);
    border: 1px solid rgba(0, 174, 255, 0.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
  }
  .cart-header-title {
    font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2;
    color: #ffffff;
  }
  .cart-header-sub {
    font-size: 12px; color: #94a3b8; font-weight: 600; margin-top: 2px;
  }
  .btn-close-cart {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff; width: 36px; height: 36px; border-radius: 50%;
    font-size: 22px; line-height: 1; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
  }
  .btn-close-cart:hover {
    background: #ef4444; border-color: #ef4444;
    transform: rotate(90deg);
  }

  /* Body */
  .cart-body {
    flex: 1; overflow-y: auto; padding: 20px;
    background: #f8fafc;
  }
  .cart-body::-webkit-scrollbar { width: 6px; }
  .cart-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

  .cart-section-label {
    font-size: 11px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.06em; color: #64748b; margin-bottom: 8px;
  }

  /* Items List */
  .cart-items-list {
    display: flex; flex-direction: column; gap: 10px;
    margin-bottom: 16px;
  }
  .cart-item-card {
    background: #ffffff; border: 1px solid #D6EAFF; border-radius: 14px;
    padding: 12px 14px; display: flex; gap: 12px; align-items: center;
    box-shadow: 0 4px 12px rgba(15, 34, 74, 0.03);
    transition: transform 0.15s, border-color 0.15s;
    animation: fadeInCartItem 0.25s ease;
  }
  @keyframes fadeInCartItem {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .cart-item-thumb {
    width: 48px; height: 64px; border-radius: 8px; object-fit: cover;
    background: #0F224A; flex-shrink: 0; border: 1px solid #e2e8f0;
  }
  .cart-item-info {
    flex: 1; min-width: 0;
  }
  .cart-item-badge {
    font-size: 9px; font-weight: 800; background: #E8F3FF; color: #0284c7;
    padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 2px;
  }
  .cart-item-title {
    font-size: 13px; font-weight: 800; color: #0F224A;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    line-height: 1.3; margin-bottom: 2px;
  }
  .cart-item-price {
    font-size: 13px; font-weight: 800; color: #0284c7;
  }
  .btn-remove-item {
    background: #fee2e2; border: 1px solid #fecaca; color: #dc2626;
    width: 28px; height: 28px; border-radius: 8px; cursor: pointer;
    font-size: 13px; display: flex; align-items: center; justify-content: center;
    transition: all 0.15s; flex-shrink: 0;
  }
  .btn-remove-item:hover {
    background: #ef4444; color: #ffffff;
  }

  /* Coupon Card */
  .cart-coupon-card {
    background: #ffffff; border: 1px solid #D6EAFF; border-radius: 14px;
    padding: 14px; margin-bottom: 16px;
  }
  .cart-coupon-input-group {
    display: flex; gap: 8px;
  }
  .btn-coupon-apply {
    background: #0F224A; color: #ffffff; border: none; border-radius: 10px;
    padding: 8px 16px; font-size: 12px; font-weight: 800; cursor: pointer;
    transition: background 0.15s;
  }
  .btn-coupon-apply:hover { background: #00AEFF; }

  .cart-coupon-applied-pill {
    background: #f0fdf4; border: 1px dashed #059669; border-radius: 10px;
    padding: 8px 12px; display: flex; justify-content: space-between; align-items: center;
    color: #065f46; font-size: 12px; margin-top: 8px;
  }
  .btn-cancel-coupon {
    background: none; border: none; color: #dc2626; font-size: 11px;
    font-weight: 800; cursor: pointer; text-decoration: underline;
  }

  /* Buyer Card */
  .cart-buyer-card {
    background: #ffffff; border: 1px solid #D6EAFF; border-radius: 14px;
    padding: 16px; margin-bottom: 16px;
  }
  .cart-field-group {
    margin-bottom: 12px;
  }
  .cart-field-group:last-child { margin-bottom: 0; }
  .cart-field-row { display: flex; gap: 10px; }
  .cart-label {
    display: block; font-size: 12px; font-weight: 700; color: #0F224A;
    margin-bottom: 4px;
  }
  .req-star { color: #dc2626; }
  .cart-input {
    width: 100%; padding: 10px 12px; border-radius: 10px;
    border: 1px solid #cbd5e1; font-size: 13px; font-family: inherit;
    outline: none; transition: border-color 0.15s, box-shadow 0.15s;
    background: #ffffff; box-sizing: border-box;
  }
  .cart-input:focus {
    border-color: #00AEFF;
    box-shadow: 0 0 0 3px rgba(0, 174, 255, 0.18);
  }
  .cart-input-hint {
    font-size: 10.5px; color: #64748b; margin-top: 3px; display: block;
  }

  /* Empty State */
  .cart-empty-state {
    text-align: center; padding: 48px 16px;
  }
  .empty-icon-wrap {
    margin-bottom: 16px;
  }
  .empty-icon-circle {
    width: 80px; height: 80px; border-radius: 50%;
    background: #E8F3FF; border: 2px dashed #00AEFF;
    margin: 0 auto; display: flex; align-items: center; justify-content: center;
    font-size: 36px; color: #00AEFF;
  }
  .empty-title {
    font-size: 18px; font-weight: 800; color: #0F224A; margin-bottom: 8px;
  }
  .empty-desc {
    font-size: 13px; color: #64748b; line-height: 1.6; max-width: 320px;
    margin: 0 auto 20px;
  }
  .btn-cart-browse {
    background: linear-gradient(135deg, #00AEFF, #0F224A);
    color: #ffffff; border: none; border-radius: 12px; padding: 12px 24px;
    font-size: 13px; font-weight: 800; cursor: pointer;
    box-shadow: 0 6px 18px rgba(0, 174, 255, 0.35); transition: transform 0.15s;
  }
  .btn-cart-browse:hover { transform: translateY(-1px); }

  /* Footer */
  .cart-footer {
    padding: 18px 24px max(22px, env(safe-area-inset-bottom));
    background: #ffffff;
    border-top: 1px solid #D6EAFF;
    box-shadow: 0 -10px 25px rgba(15, 34, 74, 0.05);
    flex-shrink: 0;
  }
  .cart-summary-table {
    margin-bottom: 14px;
  }
  .summary-line {
    display: flex; justify-content: space-between; font-size: 13px;
    margin-bottom: 4px;
  }
  .summary-lbl { color: #64748b; font-weight: 600; }
  .summary-val { color: #0F224A; font-weight: 800; }
  .summary-total {
    margin-top: 8px; padding-top: 8px; border-top: 1px dashed #cbd5e1;
    font-size: 16px;
  }
  .summary-total-lbl { color: #0F224A; font-weight: 900; }
  .summary-total-val {
    color: #0284c7; font-weight: 900; font-size: 19px;
  }

  .btn-cart-checkout {
    width: 100%; padding: 15px; border-radius: 14px;
    background: linear-gradient(135deg, #00AEFF 0%, #0F224A 100%);
    color: #ffffff; border: none; font-size: 14px; font-weight: 900;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    gap: 8px; box-shadow: 0 8px 24px rgba(0, 174, 255, 0.4);
    transition: all 0.2s; touch-action: manipulation;
  }
  .btn-cart-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0, 174, 255, 0.55);
  }
  .btn-cart-checkout:disabled {
    opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none;
  }

  @media (max-width: 768px) {
    .cart-input {
      font-size: 16px !important;
    }
  }

  .cart-trust-badges {
    text-align: center; margin-top: 10px; font-size: 10.5px;
    color: #64748b; font-weight: 600;
  }

  /* Floating Cart Button */
  .floating-cart-pill {
    position: fixed; bottom: 24px; right: 24px; z-index: 9990;
    background: linear-gradient(135deg, #0F224A 0%, #00AEFF 100%);
    color: #ffffff; border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 40px; padding: 10px 18px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 10px 30px rgba(0, 174, 255, 0.45);
    cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
  }
  .floating-cart-pill:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 14px 36px rgba(0, 174, 255, 0.6);
  }
  .floating-cart-pill.bounce {
    animation: cartPillBounce 0.5s ease;
  }
  @keyframes cartPillBounce {
    0%, 100% { transform: scale(1); }
    30% { transform: scale(1.15) rotate(-3deg); }
    60% { transform: scale(0.95) rotate(3deg); }
  }
  .floating-cart-icon-wrap {
    position: relative; font-size: 22px; line-height: 1;
  }
  .floating-cart-badge {
    position: absolute; top: -6px; right: -8px;
    background: #ef4444; color: #ffffff; font-size: 10px; font-weight: 900;
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #0F224A;
  }
  .floating-cart-meta {
    text-align: left;
  }
  .floating-cart-title {
    display: block; font-size: 10px; text-transform: uppercase;
    font-weight: 800; letter-spacing: 0.05em; opacity: 0.9;
  }
  .floating-cart-amount {
    display: block; font-size: 13px; font-weight: 900;
  }

  /* Toast Notification */
  .cart-toast {
    position: fixed; bottom: 84px; right: 24px; z-index: 10002;
    background: #0F224A; color: #ffffff; border: 1px solid #00AEFF;
    border-radius: 12px; padding: 12px 18px; font-size: 13px; font-weight: 700;
    box-shadow: 0 10px 30px rgba(15, 34, 74, 0.3);
    opacity: 0; transform: translateY(12px); pointer-events: none;
    transition: all 0.25s ease;
  }
  .cart-toast.show {
    opacity: 1; transform: translateY(0); pointer-events: auto;
  }

  /* Responsive Mobile */
  @media (max-width: 600px) {
    .cart-drawer { max-width: 100%; }
    .floating-cart-pill { bottom: 16px; right: 16px; padding: 8px 14px; }
    .cart-field-row { flex-direction: column; gap: 0; }
  }
</style>

<!-- JAVASCRIPT CART ENGINE -->
<script>
window.CartEngine = (function() {
  const STORAGE_KEY = 'rm_cart_items_v2';
  const BUYER_KEY = 'rm_cart_buyer_v2';
  const COUPON_KEY = 'rm_cart_coupon_v2';

  let items = [];
  let appliedCoupon = null;

  function init() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      items = stored ? JSON.parse(stored) : [];
      const storedCoupon = localStorage.getItem(COUPON_KEY);
      appliedCoupon = storedCoupon ? JSON.parse(storedCoupon) : null;
      loadSavedBuyerInfo();
    } catch (e) {
      items = [];
    }
    render();
  }

  function save() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
      if (appliedCoupon) {
        localStorage.setItem(COUPON_KEY, JSON.stringify(appliedCoupon));
      } else {
        localStorage.removeItem(COUPON_KEY);
      }
    } catch (e) {}
  }

  function loadSavedBuyerInfo() {
    try {
      const b = JSON.parse(localStorage.getItem(BUYER_KEY) || '{}');
      if (b.name) document.getElementById('cartBuyerName').value = b.name;
      if (b.email) document.getElementById('cartBuyerEmail').value = b.email;
      if (b.wa) document.getElementById('cartBuyerWa').value = b.wa;
      if (b.org) document.getElementById('cartBuyerOrg').value = b.org;
    } catch (e) {}
  }

  function saveBuyerInfo() {
    try {
      const b = {
        name: document.getElementById('cartBuyerName').value.trim(),
        email: document.getElementById('cartBuyerEmail').value.trim(),
        wa: document.getElementById('cartBuyerWa').value.trim(),
        org: document.getElementById('cartBuyerOrg').value.trim()
      };
      localStorage.setItem(BUYER_KEY, JSON.stringify(b));
    } catch (e) {}
  }

  function add(product, openDrawerAfter = true) {
    // Cek apakah produk sudah ada di keranjang
    const existing = items.find(x => x.id === product.id);
    if (existing) {
      showToast(`ℹ️ "${product.title}" sudah ada di keranjang Anda.`);
      if (openDrawerAfter) open();
      return;
    }

    items.push({
      id: product.id,
      title: product.title,
      price: Number(product.price),
      cover: product.cover || '/images/book-cover-450.webp',
      badge: product.badge || 'E-Book'
    });

    save();
    render();
    bounceFloatingButton();
    showToast(`✓ "${product.title}" ditambahkan ke keranjang!`);
    
    if (openDrawerAfter) {
      setTimeout(() => open(), 150);
    }
  }

  function remove(productId) {
    const idx = items.findIndex(x => x.id === productId);
    if (idx !== -1) {
      const removed = items.splice(idx, 1)[0];
      save();
      render();
      showToast(`Item "${removed.title}" dihapus.`);
    }
  }

  function clear() {
    items = [];
    appliedCoupon = null;
    save();
    render();
  }

  function getSubtotal() {
    return items.reduce((acc, it) => acc + (Number(it.price) || 0), 0);
  }

  function getDiscount() {
    if (!appliedCoupon) return 0;
    const subtotal = getSubtotal();
    let disc = 0;
    if (appliedCoupon.discount_type === 'PERCENT') {
      disc = subtotal * (Number(appliedCoupon.discount_value) / 100);
      if (appliedCoupon.max_discount && disc > Number(appliedCoupon.max_discount)) {
        disc = Number(appliedCoupon.max_discount);
      }
    } else {
      disc = Number(appliedCoupon.discount_value);
    }
    return Math.min(disc, subtotal);
  }

  function getGrandTotal() {
    return Math.max(0, getSubtotal() - getDiscount());
  }

  function render() {
    const count = items.length;
    const subtotal = getSubtotal();
    const discount = getDiscount();
    const grandTotal = getGrandTotal();

    // Update Header
    document.getElementById('cartCountText').innerText = count;

    // Update Floating Button
    const floatBtn = document.getElementById('floatingCartBtn');
    const floatBadge = document.getElementById('floatingCartBadge');
    const floatTotal = document.getElementById('floatingCartTotal');
    if (floatBtn && floatBadge && floatTotal) {
      floatBadge.innerText = count;
      floatTotal.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
      if (count === 0) {
        floatBtn.style.display = 'none';
      } else {
        floatBtn.style.display = 'flex';
      }
    }

    // Update Navbar Badge if exists
    const navBadges = document.querySelectorAll('.nav-cart-counter');
    navBadges.forEach(el => {
      el.innerText = count;
      el.style.display = count > 0 ? 'inline-flex' : 'none';
    });

    const emptyState = document.getElementById('cartEmptyState');
    const activeState = document.getElementById('cartActiveState');
    const footer = document.getElementById('cartFooter');

    if (count === 0) {
      emptyState.style.display = 'block';
      activeState.style.display = 'none';
      footer.style.display = 'none';
      return;
    }

    emptyState.style.display = 'none';
    activeState.style.display = 'block';
    footer.style.display = 'block';

    // Render Items
    const container = document.getElementById('cartItemsList');
    container.innerHTML = items.map(it => `
      <div class="cart-item-card">
        <img src="${it.cover}" alt="${it.title}" class="cart-item-thumb">
        <div class="cart-item-info">
          <span class="cart-item-badge">${it.badge}</span>
          <div class="cart-item-title" title="${it.title}">${it.title}</div>
          <div class="cart-item-price">Rp ${Number(it.price).toLocaleString('id-ID')}</div>
        </div>
        <button type="button" class="btn-remove-item" onclick="CartEngine.remove('${it.id}')" title="Hapus dari keranjang">&times;</button>
      </div>
    `).join('');

    // Render Coupon Row
    const couponRow = document.getElementById('cartCouponAppliedRow');
    const couponInputWrap = document.getElementById('cartCouponInputWrap');
    const couponStatus = document.getElementById('cartCouponStatus');
    const rowDisc = document.getElementById('rowCartDiscount');
    const discText = document.getElementById('cartDiscountText');

    if (appliedCoupon && discount > 0) {
      couponRow.style.display = 'flex';
      couponInputWrap.style.display = 'none';
      couponStatus.style.display = 'inline';
      document.getElementById('dispCartCouponCode').innerText = appliedCoupon.code;
      document.getElementById('dispCartCouponDiscount').innerText = `(-Rp ${discount.toLocaleString('id-ID')})`;
      rowDisc.style.display = 'flex';
      discText.innerText = `- Rp ${discount.toLocaleString('id-ID')}`;
    } else {
      couponRow.style.display = 'none';
      couponInputWrap.style.display = 'flex';
      couponStatus.style.display = 'none';
      rowDisc.style.display = 'none';
    }

    // Render Totals
    document.getElementById('cartSubtotalText').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('cartGrandTotalText').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
    document.getElementById('btnCartCheckoutText').innerText = `Bayar Sekarang — Rp ${grandTotal.toLocaleString('id-ID')}`;
  }

  async function applyCoupon() {
    const input = document.getElementById('inputCartCoupon');
    const code = input.value.trim().toUpperCase();
    if (!code) {
      alert('Silakan masukkan kode voucher.');
      return;
    }
    const subtotal = getSubtotal();
    if (subtotal <= 0) return;

    const btn = document.getElementById('btnApplyCartCoupon');
    btn.disabled = true;
    btn.innerText = '...';

    try {
      const res = await fetch(`/api/checkout.php?action=check_coupon&code=${encodeURIComponent(code)}&amount=${subtotal}&product_id=${items[0]?.id || ''}`);
      const data = await res.json();
      btn.disabled = false;
      btn.innerText = 'Terapkan';

      if (data.success) {
        appliedCoupon = {
          code: data.code,
          discount_type: data.discount_type,
          discount_value: data.discount_value,
          max_discount: data.max_discount || 0
        };
        save();
        render();
        showToast(`✓ Kupon ${data.code} berhasil diterapkan!`);
      } else {
        alert(data.error || data.message || 'Kode promo tidak valid');
      }
    } catch (e) {
      btn.disabled = false;
      btn.innerText = 'Terapkan';
      alert('Gagal memverifikasi kupon promo.');
    }
  }

  function removeCoupon() {
    appliedCoupon = null;
    save();
    render();
    showToast('Kupon dibatalkan.');
  }

  async function processCheckout() {
    if (items.length === 0) {
      alert('Keranjang belanja Anda masih kosong.');
      return;
    }

    const name = document.getElementById('cartBuyerName').value.trim();
    const email = document.getElementById('cartBuyerEmail').value.trim();
    const wa = document.getElementById('cartBuyerWa').value.trim();
    const org = document.getElementById('cartBuyerOrg').value.trim();

    if (!name) {
      alert('Harap isi Nama Lengkap untuk keperluan stempel lisensi DRM.');
      document.getElementById('cartBuyerName').focus();
      return;
    }
    if (!email || !email.includes('@')) {
      alert('Harap isi Alamat Email yang valid untuk pengiriman berkas & password.');
      document.getElementById('cartBuyerEmail').focus();
      return;
    }

    saveBuyerInfo();

    const btn = document.getElementById('btnCartSubmitCheckout');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ Memproses Tagihan DOKU...</span>';

    try {
      const payload = {
        items: items.map(x => x.id),
        name: name,
        email: email,
        whatsapp: wa,
        org: org,
        coupon_code: appliedCoupon ? appliedCoupon.code : ''
      };

      const res = await fetch('/api/checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success && data.payment_url) {
        // Simpan invoice terbaru ke localStorage
        localStorage.setItem('rm_last_invoice', data.invoice);
        // Kosongkan keranjang belanja
        clear();
        // Alihkan pembeli ke halaman pembayaran DOKU Checkout resmi
        window.location.href = data.payment_url;
      } else {
        btn.disabled = false;
        render();
        alert('Gagal membuat pesanan: ' + (data.error || 'Silakan coba beberapa saat lagi.'));
      }
    } catch (e) {
      btn.disabled = false;
      render();
      alert('Terjadi kesalahan jaringan saat menghubungkan ke payment gateway.');
    }
  }

  function open() {
    document.getElementById('cartDrawer').classList.add('open');
    document.getElementById('cartOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    document.getElementById('cartDrawer').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }

  function toggle() {
    if (document.getElementById('cartDrawer').classList.contains('open')) {
      close();
    } else {
      open();
    }
  }

  function browseCatalog() {
    close();
    const el = document.getElementById('katalog');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    } else {
      window.location.href = '/#katalog';
    }
  }

  function bounceFloatingButton() {
    const btn = document.getElementById('floatingCartBtn');
    if (btn) {
      btn.classList.remove('bounce');
      void btn.offsetWidth; // trigger reflow
      btn.classList.add('bounce');
    }
  }

  function showToast(msg) {
    const t = document.getElementById('cartToast');
    if (t) {
      t.innerText = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 3000);
    }
  }

  // Self-init on DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  return {
    add,
    remove,
    clear,
    open,
    close,
    toggle,
    applyCoupon,
    removeCoupon,
    processCheckout,
    browseCatalog,
    getItems: () => items
  };
})();
</script>
