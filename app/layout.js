export const metadata = {
  title: 'Restia Moegiono (@restiapriw) - Official Store',
  description: 'Toko Resmi E-Book RoPA, DPIA, dan ISO 27001 oleh Restia Moegiono',
};

export default function RootLayout({ children }) {
  return (
    <html lang="id">
      <body style={{ margin: 0, padding: 0, backgroundColor: '#060b13' }}>
        {children}
      </body>
    </html>
  );
}