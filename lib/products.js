// Prices and titles are authoritative on the server. Never accept price from browser.
export const PRODUCTS = {
  'iso-27001-main': { title: 'Buku Pegangan Implementasi ISO 27001:2022', amount: 175000 },
  'kepatuhan-pdp': { title: 'Panduan Praktis Kepatuhan UU PDP', amount: 150000 },
  'ropa-dpia': { title: 'RoPA dan DPIA Dalam Implementasi UU PDP', amount: 50000 },
  'lia-tia': { title: 'LIA dan TIA Dalam Implementasi UU PDP', amount: 50000 },
  '11-kontrol-v2': { title: '[Versi 2.0] Buku Pegangan Implementasi 11 Kontrol Baru Annex A ISO 27001 2022', amount: 150000 },
  'chk-web-word': { title: 'Checklist Keamanan Web (versi Word)', amount: 100000 },
  'jasa-konsultasi': { title: 'Jasa Konsultasi Keamanan Siber', amount: 500000 },
  'traktiran-minres': { title: 'Traktiran untuk Minres', amount: 25000 },
  'workshop-11-kontrol': { title: 'Workshop Implementasi 11 Kontrol Baru Annex A ISO 27001:2022 (SekolahSiber)', amount: 250000 },
  'kursus-smartphone': { title: 'Kursus Proteksi Smartphone di sekolahsiber.com', amount: 99000 },
};

export function validateCustomer(value) {
  const name = String(value?.nama || '').trim();
  const email = String(value?.email || '').trim().toLowerCase();
  const organization = String(value?.organisasi || '-').trim();
  if (name.length < 2 || name.length > 100) throw new Error('Nama harus berisi 2–100 karakter.');
  if (email.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) throw new Error('Alamat email tidak valid.');
  if (organization.length > 150) throw new Error('Nama organisasi terlalu panjang.');
  return { name, email, organization: organization || '-' };
}
