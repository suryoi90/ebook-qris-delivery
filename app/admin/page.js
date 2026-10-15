'use client';
import { useState, useEffect } from 'react';

export default function AdminPage() {
  const [authChecked, setAuthChecked] = useState(false);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [password, setPassword] = useState('');
  const [loginError, setLoginError] = useState('');
  const [loginLoading, setLoginLoading] = useState(false);

  // Dashboard Data
  const [activeTab, setActiveTab] = useState('orders'); // 'orders' | 'ebooks' | 'email'
  const [orders, setOrders] = useState([]);
  const [stats, setStats] = useState({ totalRevenue: 0, totalOrders: 0, countSent: 0, countPaid: 0, countPending: 0 });
  const [ebooks, setEbooks] = useState([]);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('ALL');

  // Modals & Actions
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [actionMessage, setActionMessage] = useState(null);
  const [uploadLoading, setUploadLoading] = useState({});
  const [testEmail, setTestEmail] = useState('');
  const [testEmailLoading, setTestEmailLoading] = useState(false);
  const [testEmailResult, setTestEmailResult] = useState(null);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const res = await fetch('/api/admin/auth');
      const data = await res.json();
      if (data.authenticated) {
        setIsAuthenticated(true);
        loadDashboardData();
      }
    } catch (e) {
      console.error(e);
    } finally {
      setAuthChecked(true);
    }
  };

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoginError('');
    setLoginLoading(true);
    try {
      const res = await fetch('/api/admin/auth', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password })
      });
      const data = await res.json();
      if (data.success) {
        setIsAuthenticated(true);
        loadDashboardData();
      } else {
        setLoginError(data.message || 'Password salah.');
      }
    } catch (err) {
      setLoginError('Gagal menghubungi server.');
    } finally {
      setLoginLoading(false);
    }
  };

  const handleLogout = async () => {
    await fetch('/api/admin/auth', { method: 'DELETE' });
    setIsAuthenticated(false);
    setPassword('');
  };

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      const [resOrders, resEbooks] = await Promise.all([
        fetch('/api/admin/orders').then(r => r.json()),
        fetch('/api/admin/ebooks').then(r => r.json())
      ]);

      if (resOrders.success) {
        setOrders(resOrders.orders || []);
        setStats(resOrders.stats || {});
      }
      if (resEbooks.success) {
        setEbooks(resEbooks.ebooks || []);
      }
    } catch (err) {
      console.error('Failed to load dashboard data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleOrderAction = async (action, invoiceNumber) => {
    setActionLoading(true);
    setActionMessage(null);
    try {
      const res = await fetch('/api/admin/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, invoice_number: invoiceNumber })
      });
      const data = await res.json();
      setActionMessage({ type: data.success ? 'success' : 'error', text: data.message });
      if (data.success) {
        await loadDashboardData();
        if (selectedOrder && selectedOrder.invoice_number === invoiceNumber) {
          const updated = orders.find(o => o.invoice_number === invoiceNumber);
          if (updated) setSelectedOrder(updated);
        }
      }
    } catch (err) {
      setActionMessage({ type: 'error', text: err.message || 'Gagal memproses aksi.' });
    } finally {
      setActionLoading(false);
    }
  };

  const handleUploadEbook = async (productId, file) => {
    if (!file) return;
    setUploadLoading(prev => ({ ...prev, [productId]: true }));
    const formData = new FormData();
    formData.append('productId', productId);
    formData.append('file', file);

    try {
      const res = await fetch('/api/admin/ebooks', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      alert(data.message || (data.success ? 'Upload berhasil!' : 'Upload gagal.'));
      if (data.success) {
        loadDashboardData();
      }
    } catch (err) {
      alert('Gagal mengupload file: ' + err.message);
    } finally {
      setUploadLoading(prev => ({ ...prev, [productId]: false }));
    }
  };

  const handleTestEmail = async (e) => {
    e.preventDefault();
    if (!testEmail) return;
    setTestEmailLoading(true);
    setTestEmailResult(null);
    try {
      const res = await fetch('/api/admin/test-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: testEmail })
      });
      const data = await res.json();
      setTestEmailResult({ type: data.success ? 'success' : 'error', text: data.message });
    } catch (err) {
      setTestEmailResult({ type: 'error', text: err.message || 'Gagal tes kirim email.' });
    } finally {
      setTestEmailLoading(false);
    }
  };

  const formatRupiah = (num) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
  };

  const filteredOrders = orders.filter(o => {
    const matchSearch =
      (o.invoice_number || '').toLowerCase().includes(search.toLowerCase()) ||
      (o.customer_name || '').toLowerCase().includes(search.toLowerCase()) ||
      (o.customer_email || '').toLowerCase().includes(search.toLowerCase()) ||
      (o.product_title || '').toLowerCase().includes(search.toLowerCase());

    const matchStatus = statusFilter === 'ALL' || o.status === statusFilter;
    return matchSearch && matchStatus;
  });

  if (!authChecked) {
    return (
      <div style={{ minHeight: '100vh', display: 'grid', placeItems: 'center', backgroundColor: '#f8fafc', color: '#0284c7', fontFamily: 'system-ui, sans-serif' }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{ width: '48px', height: '48px', border: '3px solid #e2e8f0', borderTopColor: '#0284c7', borderRadius: '50%', animation: 'spin 1s linear infinite', margin: '0 auto 16px' }} />
          <div style={{ fontSize: '14px', fontWeight: '700', color: '#0f172a' }}>Memuat Command Vault...</div>
          <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
        </div>
      </div>
    );
  }

  // LOGIN SCREEN IN BRIGHT LIGHT THEME
  if (!isAuthenticated) {
    return (
      <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', backgroundColor: '#f8fafc', padding: '20px', fontFamily: 'system-ui, -apple-system, sans-serif', color: '#0f172a', position: 'relative' }}>
        
        <div style={{ position: 'absolute', width: '500px', height: '500px', background: 'radial-gradient(circle, rgba(2, 132, 199, 0.08) 0%, transparent 70%)', pointerEvents: 'none' }} />

        <div style={{ maxWidth: '440px', width: '100%', background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '28px', padding: '36px 32px', boxShadow: '0 20px 50px -10px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.03)', position: 'relative', zIndex: 1 }}>
          
          {/* 3D VAULT AVATAR */}
          <div style={{ textAlign: 'center', marginBottom: '28px' }}>
            <div style={{ width: '96px', height: '96px', borderRadius: '24px', overflow: 'hidden', margin: '0 auto 16px', border: '2px solid #cbd5e1', boxShadow: '0 8px 20px rgba(0,0,0,0.1)' }}>
              <img 
                src="/images/admin-vault-3d.jpg" 
                alt="Cyber Vault 3D" 
                style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }}
              />
            </div>
            <div style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '4px 12px', borderRadius: '20px', background: '#e0f2fe', border: '1px solid #bae6fd', color: '#0369a1', fontSize: '11px', fontWeight: '800', marginBottom: '8px' }}>
              🔒 AREA PENGELOLA TERPROTEKSI
            </div>
            <h1 style={{ fontSize: '22px', fontWeight: '900', margin: '0 0 6px', color: '#0f172a' }}>Restia Moegiono Command Vault</h1>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>Autentikasi Sesi Terenkripsi HMAC-SHA256</p>
          </div>

          <form onSubmit={handleLogin}>
            <div style={{ marginBottom: '18px' }}>
              <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '8px' }}>Kata Sandi Administrator:</label>
              <input
                id="admin-password-input"
                type="password"
                required
                placeholder="Masukkan kata sandi..."
                value={password}
                onChange={e => setPassword(e.target.value)}
                style={{ width: '100%', padding: '14px 16px', borderRadius: '12px', background: '#f8fafc', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '14px', boxSizing: 'border-box', outline: 'none' }}
              />
            </div>

            {loginError && (
              <div style={{ padding: '12px 14px', borderRadius: '10px', background: '#fef2f2', border: '1px solid #fecaca', color: '#b91c1c', fontSize: '12px', marginBottom: '16px', fontWeight: '600' }}>
                ⚠️ {loginError}
              </div>
            )}

            <button
              id="admin-login-button"
              type="submit"
              disabled={loginLoading}
              style={{ width: '100%', padding: '14px', borderRadius: '12px', background: 'linear-gradient(135deg, #0284c7, #2563eb)', border: 'none', color: '#fff', fontSize: '14px', fontWeight: '800', cursor: 'pointer', boxShadow: '0 4px 16px rgba(2,132,199,0.3)', opacity: loginLoading ? 0.7 : 1, transition: 'all 0.2s' }}
            >
              {loginLoading ? 'Memverifikasi Kredensial...' : 'Buka Dashboard Pengelola ➔'}
            </button>
          </form>

          <div style={{ textAlign: 'center', marginTop: '24px' }}>
            <a href="/" style={{ color: '#64748b', fontSize: '12px', textDecoration: 'none', fontWeight: '600' }}>← Kembali ke Halaman Toko</a>
          </div>
        </div>
      </div>
    );
  }

  // MAIN ADMIN DASHBOARD IN BRIGHT LIGHT THEME
  return (
    <div style={{ minHeight: '100vh', backgroundColor: '#f8fafc', color: '#0f172a', fontFamily: 'system-ui, -apple-system, sans-serif' }}>
      
      {/* TOPBAR */}
      <header style={{ borderBottom: '1px solid #e2e8f0', background: 'rgba(255, 255, 255, 0.92)', backdropFilter: 'blur(16px)', padding: '14px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', position: 'sticky', top: 0, zIndex: 50, boxShadow: '0 2px 8px rgba(0,0,0,0.03)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
          <div style={{ width: '42px', height: '42px', borderRadius: '12px', overflow: 'hidden', border: '1px solid #cbd5e1', boxShadow: '0 2px 8px rgba(0,0,0,0.08)' }}>
            <img src="/images/admin-vault-3d.jpg" alt="Vault Icon" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
          </div>
          <div>
            <div style={{ fontSize: '16px', fontWeight: '900', color: '#0f172a', letterSpacing: '-0.3px' }}>Restia Moegiono Command Vault</div>
            <div style={{ fontSize: '11px', color: '#0284c7', fontWeight: '700' }}>Self-Hosted E-Book Engine & Fulfillment Center</div>
          </div>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <button
            onClick={loadDashboardData}
            style={{ padding: '8px 14px', borderRadius: '8px', background: '#f1f5f9', border: '1px solid #cbd5e1', color: '#334155', fontSize: '12px', fontWeight: '700', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px' }}
          >
            <span>🔄</span> <span>{loading ? 'Menyinkronkan...' : 'Segarkan Data'}</span>
          </button>

          <a
            href="/"
            target="_blank"
            style={{ padding: '8px 14px', borderRadius: '8px', background: '#e0f2fe', border: '1px solid #bae6fd', color: '#0369a1', fontSize: '12px', fontWeight: '700', textDecoration: 'none' }}
          >
            Buka Toko ↗
          </a>

          <button
            onClick={handleLogout}
            style={{ padding: '8px 14px', borderRadius: '8px', background: '#fee2e2', border: '1px solid #fecaca', color: '#dc2626', fontSize: '12px', fontWeight: '700', cursor: 'pointer' }}
          >
            Keluar
          </button>
        </div>
      </header>

      {/* BODY CONTENT */}
      <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '32px 24px 80px' }}>
        
        {/* KPI METRIC CARDS IN LIGHT THEME */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '18px', marginBottom: '32px' }}>
          
          <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '4px solid #0284c7', borderRadius: '18px', padding: '22px', boxShadow: '0 4px 14px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
              <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '800' }}>TOTAL OMSET PENJUALAN</div>
              <span style={{ fontSize: '20px' }}>💰</span>
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#0284c7', letterSpacing: '-0.5px' }}>{formatRupiah(stats.totalRevenue)}</div>
            <div style={{ fontSize: '11px', color: '#94a3b8', marginTop: '6px' }}>Akumulasi dari transaksi sukses (Lunas)</div>
          </div>

          <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '4px solid #10b981', borderRadius: '18px', padding: '22px', boxShadow: '0 4px 14px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
              <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '800' }}>E-BOOK TERKIRIM OTOMATIS</div>
              <span style={{ fontSize: '20px' }}>📬</span>
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#059669', letterSpacing: '-0.5px' }}>{stats.countSent || 0}</div>
            <div style={{ fontSize: '11px', color: '#94a3b8', marginTop: '6px' }}>PDF lisensi & password berhasil dikirim</div>
          </div>

          <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '4px solid #f59e0b', borderRadius: '18px', padding: '22px', boxShadow: '0 4px 14px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
              <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '800' }}>MENUNGGU PEMBAYARAN</div>
              <span style={{ fontSize: '20px' }}>⏳</span>
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#d97706', letterSpacing: '-0.5px' }}>{stats.countPending || 0}</div>
            <div style={{ fontSize: '11px', color: '#94a3b8', marginTop: '6px' }}>Checkout DOKU dibuat (belum bayar)</div>
          </div>

          <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '4px solid #64748b', borderRadius: '18px', padding: '22px', boxShadow: '0 4px 14px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
              <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '800' }}>TOTAL TRANSAKSI</div>
              <span style={{ fontSize: '20px' }}>📋</span>
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#0f172a', letterSpacing: '-0.5px' }}>{stats.totalOrders || 0}</div>
            <div style={{ fontSize: '11px', color: '#94a3b8', marginTop: '6px' }}>Keseluruhan pesanan tercatat di database</div>
          </div>

        </div>

        {/* TABS NAVIGATION IN LIGHT MODE */}
        <div style={{ display: 'flex', gap: '8px', borderBottom: '1px solid #e2e8f0', marginBottom: '28px', paddingBottom: '4px' }}>
          {[
            { id: 'orders', label: '📋 Data & Antrean Pesanan', count: orders.length },
            { id: 'ebooks', label: '📚 Kelola Master PDF E-Book', count: ebooks.length },
            { id: 'email', label: '⚙️ Konfigurasi & Tes Email' },
          ].map(tab => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              style={{
                padding: '12px 20px',
                borderRadius: '12px 12px 0 0',
                fontSize: '13px',
                fontWeight: '700',
                background: activeTab === tab.id ? '#ffffff' : 'transparent',
                color: activeTab === tab.id ? '#0284c7' : '#64748b',
                border: activeTab === tab.id ? '1px solid #e2e8f0' : 'none',
                borderBottom: activeTab === tab.id ? '2px solid #0284c7' : '2px solid transparent',
                boxShadow: activeTab === tab.id ? '0 -2px 6px rgba(0,0,0,0.02)' : 'none',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                transition: 'all 0.2s'
              }}
            >
              <span>{tab.label}</span>
              {typeof tab.count === 'number' && (
                <span style={{ background: activeTab === tab.id ? '#0284c7' : '#e2e8f0', color: activeTab === tab.id ? '#fff' : '#64748b', fontSize: '11px', padding: '2px 8px', borderRadius: '10px' }}>
                  {tab.count}
                </span>
              )}
            </button>
          ))}
        </div>

        {/* TAB 1: ORDERS */}
        {activeTab === 'orders' && (
          <div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '12px', alignItems: 'center', justifyContent: 'space-between', marginBottom: '20px' }}>
              <input
                type="text"
                placeholder="Cari Invoice, Nama, Email, atau Judul E-Book..."
                value={search}
                onChange={e => setSearch(e.target.value)}
                style={{ width: '360px', maxWidth: '100%', padding: '12px 16px', borderRadius: '10px', background: '#ffffff', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '13px', outline: 'none' }}
              />

              <div style={{ display: 'flex', gap: '6px' }}>
                {[
                  { id: 'ALL', label: 'Semua Status' },
                  { id: 'SENT', label: 'Terkirim (SENT)' },
                  { id: 'PAID', label: 'Lunas (PAID)' },
                  { id: 'PENDING', label: 'Menunggu (PENDING)' },
                ].map(s => (
                  <button
                    key={s.id}
                    onClick={() => setStatusFilter(s.id)}
                    style={{
                      padding: '8px 14px',
                      borderRadius: '8px',
                      fontSize: '11px',
                      fontWeight: '700',
                      background: statusFilter === s.id ? '#0284c7' : '#ffffff',
                      color: statusFilter === s.id ? '#fff' : '#64748b',
                      border: statusFilter === s.id ? '1px solid #0284c7' : '1px solid #cbd5e1',
                      cursor: 'pointer'
                    }}
                  >
                    {s.label}
                  </button>
                ))}
              </div>
            </div>

            {/* ORDERS TABLE */}
            <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '18px', overflow: 'hidden', boxShadow: '0 4px 14px rgba(0,0,0,0.03)' }}>
              {filteredOrders.length === 0 ? (
                <div style={{ padding: '60px 20px', textAlign: 'center', color: '#64748b' }}>
                  <div style={{ fontSize: '32px', marginBottom: '10px' }}>📭</div>
                  <div style={{ fontSize: '14px', fontWeight: '700', color: '#0f172a' }}>Belum ada data pesanan yang cocok.</div>
                  <div style={{ fontSize: '12px', marginTop: '4px' }}>Data pesanan baru dari checkout DOKU akan otomatis tampil di sini.</div>
                </div>
              ) : (
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '13px', textAlign: 'left' }}>
                    <thead>
                      <tr style={{ borderBottom: '1px solid #e2e8f0', background: '#f8fafc', color: '#64748b', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                        <th style={{ padding: '14px 18px' }}>Invoice</th>
                        <th style={{ padding: '14px 18px' }}>Pembeli & Organisasi</th>
                        <th style={{ padding: '14px 18px' }}>Produk</th>
                        <th style={{ padding: '14px 18px' }}>Total</th>
                        <th style={{ padding: '14px 18px' }}>Status</th>
                        <th style={{ padding: '14px 18px', textAlign: 'right' }}>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredOrders.map(order => (
                        <tr key={order.invoice_number} style={{ borderBottom: '1px solid #f1f5f9' }}>
                          <td style={{ padding: '14px 18px', fontFamily: 'monospace', fontSize: '12px', color: '#0284c7', fontWeight: '700' }}>
                            {order.invoice_number}
                          </td>
                          <td style={{ padding: '14px 18px' }}>
                            <div style={{ fontWeight: '700', color: '#0f172a' }}>{order.customer_name}</div>
                            <div style={{ fontSize: '11px', color: '#64748b' }}>{order.customer_email}</div>
                            <div style={{ fontSize: '11px', color: '#94a3b8' }}>{order.organization || '-'}</div>
                          </td>
                          <td style={{ padding: '14px 18px', color: '#334155' }}>
                            {order.product_title}
                          </td>
                          <td style={{ padding: '14px 18px', fontWeight: '800', color: '#0f172a' }}>
                            {formatRupiah(order.amount)}
                          </td>
                          <td style={{ padding: '14px 18px' }}>
                            <span style={{
                              display: 'inline-block',
                              padding: '4px 10px',
                              borderRadius: '20px',
                              fontSize: '11px',
                              fontWeight: '800',
                              background: order.status === 'SENT' ? '#ecfdf5' : order.status === 'PAID' ? '#e0f2fe' : '#fef3c7',
                              color: order.status === 'SENT' ? '#047857' : order.status === 'PAID' ? '#0369a1' : '#b45309',
                              border: order.status === 'SENT' ? '1px solid #a7f3d0' : order.status === 'PAID' ? '1px solid #bae6fd' : '1px solid #fde68a'
                            }}>
                              {order.status}
                            </span>
                          </td>
                          <td style={{ padding: '14px 18px', textAlign: 'right' }}>
                            <button
                              onClick={() => setSelectedOrder(order)}
                              style={{ padding: '6px 12px', borderRadius: '6px', background: '#f1f5f9', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '11px', fontWeight: '700', cursor: 'pointer' }}
                            >
                              Detail & Kontrol
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        )}

        {/* TAB 2: MASTER EBOOKS */}
        {activeTab === 'ebooks' && (
          <div>
            <div style={{ background: '#f0f9ff', border: '1px solid #bae6fd', borderRadius: '16px', padding: '20px', marginBottom: '24px', display: 'flex', alignItems: 'center', gap: '16px' }}>
              <span style={{ fontSize: '32px' }}>📖</span>
              <div>
                <div style={{ fontSize: '14px', fontWeight: '800', color: '#0369a1' }}>Pusat File Master E-Book Asli</div>
                <div style={{ fontSize: '12px', color: '#475569', marginTop: '2px', lineHeight: '1.5' }}>
                  Upload file PDF master buku Anda di sini. Ketika ada transaksi sukses, sistem akan mengambil file master ini, membubuhkan watermark lisensi nama & email pembeli di tiap halaman, mengenkripsi dengan password unik, lalu otomatis mengirimkannya via email.
                </div>
              </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '20px' }}>
              {ebooks.map(eb => (
                <div key={eb.id} style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '22px', display: 'flex', flexDirection: 'column', justifyContent: 'space-between', boxShadow: '0 4px 14px rgba(0,0,0,0.03)' }}>
                  <div>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '12px' }}>
                      <span style={{ fontSize: '11px', color: '#0369a1', fontWeight: '700', background: '#e0f2fe', padding: '3px 8px', borderRadius: '4px' }}>
                        ID: {eb.id}
                      </span>
                      <span style={{
                        fontSize: '11px',
                        fontWeight: '800',
                        color: eb.hasFile ? '#047857' : '#b45309',
                        background: eb.hasFile ? '#ecfdf5' : '#fef3c7',
                        border: eb.hasFile ? '1px solid #a7f3d0' : '1px solid #fde68a',
                        padding: '3px 8px',
                        borderRadius: '6px'
                      }}>
                        {eb.hasFile ? '✅ Master Terpasang' : '⚠️ Belum Upload'}
                      </span>
                    </div>

                    <h4 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', margin: '0 0 6px', lineHeight: '1.35' }}>{eb.title}</h4>
                    <div style={{ fontSize: '14px', fontWeight: '800', color: '#0284c7', marginBottom: '12px' }}>{formatRupiah(eb.amount)}</div>

                    {eb.hasFile && (
                      <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '8px 12px', fontSize: '11px', color: '#475569', marginBottom: '16px' }}>
                        <div>Ukuran Berkas: <b>{eb.sizeMb} MB</b></div>
                        <div>Terakhir Diperbarui: <b>{new Date(eb.modifiedAt).toLocaleDateString('id-ID')}</b></div>
                      </div>
                    )}
                  </div>

                  <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: '16px' }}>
                    <label style={{ display: 'block', width: '100%' }}>
                      <input
                        type="file"
                        accept="application/pdf"
                        style={{ display: 'none' }}
                        onChange={e => handleUploadEbook(eb.id, e.target.files[0])}
                      />
                      <div style={{
                        width: '100%',
                        padding: '10px',
                        borderRadius: '10px',
                        background: eb.hasFile ? '#f1f5f9' : '#0284c7',
                        border: eb.hasFile ? '1px solid #cbd5e1' : 'none',
                        color: eb.hasFile ? '#334155' : '#ffffff',
                        fontSize: '12px',
                        fontWeight: '700',
                        textAlign: 'center',
                        cursor: 'pointer',
                        boxSizing: 'border-box'
                      }}>
                        {uploadLoading[eb.id] ? 'Mengunggah PDF...' : eb.hasFile ? '🔄 Ganti File PDF Master' : '📤 Upload File PDF Master'}
                      </div>
                    </label>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* TAB 3: EMAIL TESTER */}
        {activeTab === 'email' && (
          <div style={{ maxWidth: '600px', margin: '0 auto' }}>
            <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '20px', padding: '32px', boxShadow: '0 12px 30px rgba(0,0,0,0.05)' }}>
              
              <div style={{ textAlign: 'center', marginBottom: '24px' }}>
                <div style={{ fontSize: '36px', marginBottom: '8px' }}>✉️</div>
                <h3 style={{ fontSize: '18px', fontWeight: '800', margin: '0 0 6px', color: '#0f172a' }}>Uji Pengiriman Email (SMTP)</h3>
                <p style={{ fontSize: '12px', color: '#64748b', margin: 0 }}>Pastikan email notifikasi dan berkas e-book dapat terkirim lancar ke inbox pembeli</p>
              </div>

              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '16px', marginBottom: '24px', fontSize: '12px', color: '#334155' }}>
                <div style={{ fontWeight: '700', color: '#0284c7', marginBottom: '6px' }}>Status Konfigurasi Mail Server:</div>
                <div>• Host SMTP: <b>localhost (cPanel Exim)</b></div>
                <div>• Port SMTP: <b>25 / 465</b></div>
                <div>• Pengirim Resmi: <b>order@restiamoegiono.com</b></div>
              </div>

              <form onSubmit={handleTestEmail}>
                <div style={{ marginBottom: '18px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>Kirim Email Percobaan Ke Alamat:</label>
                  <input
                    type="email"
                    required
                    placeholder="email-anda@gmail.com"
                    value={testEmail}
                    onChange={e => setTestEmail(e.target.value)}
                    style={{ width: '100%', padding: '12px 14px', borderRadius: '10px', background: '#f8fafc', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '13px', boxSizing: 'border-box', outline: 'none' }}
                  />
                </div>

                {testEmailResult && (
                  <div style={{
                    padding: '12px 14px',
                    borderRadius: '10px',
                    fontSize: '12px',
                    marginBottom: '18px',
                    background: testEmailResult.type === 'success' ? '#ecfdf5' : '#fef2f2',
                    border: testEmailResult.type === 'success' ? '1px solid #a7f3d0' : '1px solid #fecaca',
                    color: testEmailResult.type === 'success' ? '#047857' : '#b91c1c',
                    fontWeight: '600'
                  }}>
                    {testEmailResult.type === 'success' ? '✅' : '⚠️'} {testEmailResult.text}
                  </div>
                )}

                <button
                  type="submit"
                  disabled={testEmailLoading}
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', background: '#0284c7', border: 'none', color: '#fff', fontSize: '13px', fontWeight: '800', cursor: 'pointer', opacity: testEmailLoading ? 0.7 : 1 }}
                >
                  {testEmailLoading ? 'Mengirimkan Email Tes...' : 'Kirim Email Percobaan Sekarang ➔'}
                </button>
              </form>

            </div>
          </div>
        )}

        {/* ORDER DETAIL & ACTION MODAL */}
        {selectedOrder && (
          <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(15, 23, 42, 0.65)', backdropFilter: 'blur(8px)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '16px', zIndex: 9999 }}>
            <div style={{ background: '#ffffff', border: '1px solid #cbd5e1', borderRadius: '24px', maxWidth: '520px', width: '100%', padding: '28px', color: '#0f172a', position: 'relative', boxShadow: '0 24px 60px rgba(0,0,0,0.2)' }}>
              
              <button 
                onClick={() => setSelectedOrder(null)} 
                style={{ position: 'absolute', top: '16px', right: '16px', width: '32px', height: '32px', borderRadius: '50%', background: '#f1f5f9', border: '1px solid #e2e8f0', color: '#64748b', fontSize: '14px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
              >
                ✕
              </button>

              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '18px' }}>
                <span style={{ fontSize: '24px' }}>📄</span>
                <div>
                  <h3 style={{ fontSize: '17px', fontWeight: '800', margin: 0, color: '#0f172a' }}>Rincian Pesanan & Lisensi</h3>
                  <div style={{ fontSize: '11px', color: '#0284c7', fontFamily: 'monospace', fontWeight: '700' }}>{selectedOrder.invoice_number}</div>
                </div>
              </div>

              {actionMessage && (
                <div style={{
                  padding: '10px 14px',
                  borderRadius: '8px',
                  fontSize: '12px',
                  marginBottom: '16px',
                  background: actionMessage.type === 'success' ? '#ecfdf5' : '#fef2f2',
                  border: actionMessage.type === 'success' ? '1px solid #a7f3d0' : '1px solid #fecaca',
                  color: actionMessage.type === 'success' ? '#047857' : '#b91c1c',
                  fontWeight: '600'
                }}>
                  {actionMessage.type === 'success' ? '✅' : '⚠️'} {actionMessage.text}
                </div>
              )}

              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '16px', marginBottom: '20px', fontSize: '12px', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                <div>Nama Pembeli: <b>{selectedOrder.customer_name}</b></div>
                <div>Email: <b>{selectedOrder.customer_email}</b></div>
                <div>Organisasi: <b>{selectedOrder.organization || '-'}</b></div>
                <div>Produk: <b>{selectedOrder.product_title}</b></div>
                <div>Nominal: <b>{formatRupiah(selectedOrder.amount)}</b></div>
                <div>Status Pesanan: <b style={{ color: selectedOrder.status === 'SENT' ? '#059669' : selectedOrder.status === 'PAID' ? '#0284c7' : '#d97706' }}>{selectedOrder.status}</b></div>
                {selectedOrder.license_id && <div>ID Lisensi: <b style={{ fontFamily: 'monospace', color: '#0284c7' }}>{selectedOrder.license_id}</b></div>}
                {selectedOrder.pdf_password && <div>Password Pembuka PDF: <b style={{ fontFamily: 'monospace', color: '#b45309', fontSize: '14px' }}>{selectedOrder.pdf_password}</b></div>}
                <div>Waktu Pemesanan: <b>{new Date(selectedOrder.created_at).toLocaleString('id-ID')}</b></div>
              </div>

              <div style={{ display: 'flex', gap: '10px' }}>
                <button
                  disabled={actionLoading}
                  onClick={() => handleOrderAction('resend', selectedOrder.invoice_number)}
                  style={{ flex: 1, padding: '12px', borderRadius: '10px', background: '#0284c7', border: 'none', color: '#fff', fontSize: '12px', fontWeight: '800', cursor: 'pointer', opacity: actionLoading ? 0.7 : 1 }}
                >
                  {actionLoading ? 'Memproses...' : '📨 Kirim Ulang E-Book'}
                </button>

                {selectedOrder.status === 'PENDING' && (
                  <button
                    disabled={actionLoading}
                    onClick={() => handleOrderAction('mark_paid', selectedOrder.invoice_number)}
                    style={{ flex: 1, padding: '12px', borderRadius: '10px', background: '#10b981', border: 'none', color: '#fff', fontSize: '12px', fontWeight: '800', cursor: 'pointer', opacity: actionLoading ? 0.7 : 1 }}
                  >
                    {actionLoading ? 'Memproses...' : '✅ Tandai Lunas & Kirim'}
                  </button>
                )}
              </div>

            </div>
          </div>
        )}

      </div>

    </div>
  );
}
