export const dynamic = 'force-dynamic';

export default async function PaymentResultPage({ searchParams }) {
  const params = await searchParams;
  const invoice = String(params?.invoice || '');

  return (
    <main style={{ minHeight: '100vh', display: 'grid', placeItems: 'center', padding: 24, background: '#060b13', color: '#fff', fontFamily: 'system-ui, sans-serif' }}>
      <section style={{ maxWidth: 520, width: '100%', padding: 28, borderRadius: 18, background: '#fff', color: '#0f172a', textAlign: 'center' }}>
        <div style={{ fontSize: 36, marginBottom: 12 }}>📩</div>
        <h1 style={{ fontSize: 22, margin: '0 0 12px' }}>Terima kasih atas pesanan Anda</h1>
        <p style={{ color: '#475569', lineHeight: 1.6, margin: '0 0 12px' }}>
          Status pembayaran sedang dikonfirmasi. Kami akan mengirim ebook dan password PDF ke email Anda setelah DOKU mengonfirmasi pembayaran.
        </p>
        {invoice && <p style={{ color: '#64748b', fontSize: 13, margin: '0 0 18px' }}>Nomor pesanan: <strong>{invoice}</strong></p>}
        <a href="/" style={{ display: 'inline-block', padding: '11px 18px', borderRadius: 10, background: '#0284c7', color: '#fff', textDecoration: 'none', fontWeight: 700 }}>Kembali ke toko</a>
      </section>
    </main>
  );
}
