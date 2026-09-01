export const metadata = {
  title: 'E-Book RoPA dan DPIA - UU PDP',
  description: 'Beli E-Book RoPA dan DPIA UU PDP dengan QRIS Otomatis',
};

export default function RootLayout({ children }) {
  return (
    <html lang="id">
      <body style={{ margin: 0, padding: 0, backgroundColor: '#f8fafc', color: '#1e293b' }}>
        {children}
      </body>
    </html>
  );
}
