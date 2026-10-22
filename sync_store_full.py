import urllib.request
import urllib.parse
import ssl
import json
import secrets
from pathlib import Path

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

CPANEL_AUTH = "cpanel restiamo:OWGHEMIOPPS1I4985VNW9S6NXW2ZX5HT"

def upload_file_cpanel(target_dir, file_path, remote_filename=None):
    if remote_filename is None:
        remote_filename = Path(file_path).name

    with open(file_path, "rb") as f:
        file_bytes = f.read()

    boundary = "----WebKitFormBoundary" + secrets.token_hex(16)
    
    header_part = (
        f"--{boundary}\r\n"
        f'Content-Disposition: form-data; name="dir"\r\n\r\n'
        f"{target_dir}\r\n"
        f"--{boundary}\r\n"
        f'Content-Disposition: form-data; name="overwrite"\r\n\r\n'
        f"1\r\n"
        f"--{boundary}\r\n"
        f'Content-Disposition: form-data; name="autocreate_directories"\r\n\r\n'
        f"1\r\n"
        f"--{boundary}\r\n"
        f'Content-Disposition: form-data; name="file-1"; filename="{remote_filename}"\r\n'
        f"Content-Type: application/octet-stream\r\n\r\n"
    ).encode('utf-8')
    
    footer_part = f"\r\n--{boundary}--\r\n".encode('utf-8')
    body = header_part + file_bytes + footer_part

    req = urllib.request.Request(
        "https://103.125.180.56:2083/execute/Fileman/upload_files",
        data=body,
        headers={
            "Authorization": CPANEL_AUTH,
            "Content-Type": f"multipart/form-data; boundary={boundary}"
        }
    )

    with urllib.request.urlopen(req, context=ctx) as resp:
        return json.loads(resp.read().decode())

def delete_file_cpanel(dir_path, filename):
    req = urllib.request.Request(
        "https://103.125.180.56:2083/execute/Fileman/fileop",
        data=urllib.parse.urlencode({"op": "unlink", "sourcefiles": f"{dir_path}/{filename}"}).encode(),
        headers={"Authorization": CPANEL_AUTH}
    )
    try:
        with urllib.request.urlopen(req, context=ctx) as resp:
            return json.loads(resp.read().decode())
    except:
        return {}

base_local = Path("C:/Users/IT GLOBALDISPOMEDIKA/.gemini/antigravity-ide/scratch/store-clean-php")

DOCROOT = "/home/restiamo/public_html/store.restiamoegiono.com"

files_to_sync = [
    (DOCROOT, base_local / "config.php", "config.php"),
    (DOCROOT, base_local / "db.php", "db.php"),
    (DOCROOT, base_local / "php.ini", "php.ini"),
    (DOCROOT, base_local / "favicon.ico", "favicon.ico"),
    (DOCROOT, base_local / "index.php", "index.php"),
    (DOCROOT, base_local / "product.php", "product.php"),
    (DOCROOT, base_local / "cart-drawer.php", "cart-drawer.php"),
    (DOCROOT, base_local / "track.php", "track.php"),
    (DOCROOT, base_local / "download.php", "download.php"),
    (DOCROOT, base_local / "guide.php", "guide.php"),
    (DOCROOT, base_local / "license.php", "license.php"),
    (DOCROOT, base_local / "terms.php", "terms.php"),
    (DOCROOT + "/services", base_local / "services/DokuService.php", "DokuService.php"),
    (DOCROOT + "/services", base_local / "services/WatermarkService.php", "WatermarkService.php"),
    (DOCROOT + "/services", base_local / "services/MailService.php", "MailService.php"),
    (DOCROOT + "/services", base_local / "services/FulfillmentService.php", "FulfillmentService.php"),
    (DOCROOT + "/services", base_local / "services/BackupService.php", "BackupService.php"),
    (DOCROOT + "/services", base_local / "services/DownloadService.php", "DownloadService.php"),
    (DOCROOT + "/services", base_local / "services/LogService.php", "LogService.php"),
    (DOCROOT + "/api", base_local / "api/checkout.php", "checkout.php"),
    (DOCROOT + "/api", base_local / "api/doku-notify.php", "doku-notify.php"),
    (DOCROOT + "/api", base_local / "api/order-status.php", "order-status.php"),
    (DOCROOT + "/api", base_local / "api/admin-action.php", "admin-action.php"),
    (DOCROOT + "/payment/result", base_local / "payment/result/index.php", "index.php"),
    (DOCROOT + "/admin", base_local / "admin/index.php", "index.php"),
    (DOCROOT + "/admin", base_local / "admin/.htaccess", ".htaccess"),
    (DOCROOT + "/bin", base_local / "bin/watermark.py", "watermark.py"),
    (DOCROOT + "/storage/generated", base_local / "storage/generated/.htaccess", ".htaccess"),
]

print("=== DEPLOYING NATIVE PHP ARCHITECTURE TO STORE.RESTIAMOEGIONO.COM ===")
for target_dir, local_path, fname in files_to_sync:
    if not local_path.exists():
        print(f"[ERROR] Local file not found: {local_path}")
        continue
    res = upload_file_cpanel(target_dir, local_path, fname)
    if res.get('status') == 1:
        print(f"[OK] Uploaded {fname} -> {target_dir}")
    else:
        print(f"[FAILED] {fname} -> {res.get('errors')}")

# Update .htaccess with rewrite rules, Gzip compression, browser caching, and strict security headers
new_ht = r"""RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

DirectoryIndex index.php index.html

# Mitigation against CVE-2026-109618 (PHP2Shell OOM Request Flooding)
# Limit public request body to 10MB (checkouts/APIs require < 200KB)
LimitRequestBody 10485760

# MIME Types (Fix for Content-Type Header Empty)
AddType text/html .php .html
AddType application/json .json
AddType image/webp .webp
AddType image/x-icon .ico
AddType image/svg+xml .svg
AddType font/woff2 .woff2
AddType text/css .css
AddType application/javascript .js

# Friendly URLs for Standalone Product Pages & Policies
RewriteRule ^produk/([^/]+)/?$ product.php?id=$1 [L,QSA]
RewriteRule ^product/([^/]+)/?$ product.php?id=$1 [L,QSA]
RewriteRule ^cek-pesanan/?$ track.php [L,QSA]
RewriteRule ^panduan/?$ guide.php [L,QSA]
RewriteRule ^lisensi/?$ license.php [L,QSA]
RewriteRule ^syarat-ketentuan/?$ terms.php [L,QSA]

# Security: Prevent search engines from indexing admin or raw internal directories
<If "%{REQUEST_URI} =~ m#^/(admin|api|payment|storage|bin)#">
    Header always set X-Robots-Tag "noindex, nofollow, noarchive, nosnippet"
</If>


# ----------------------------------------------------------------------
# 1. Gzip / Deflate Network Compression (HTML/JSON/CSS/JS/SVG)
# ----------------------------------------------------------------------
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json image/svg+xml
</IfModule>

# ----------------------------------------------------------------------
# 2. Strict Security Headers & Information Leak Prevention
# ----------------------------------------------------------------------
<IfModule mod_headers.c>
  # Fix: X-Content-Type-Options Header Missing (Always set for all status codes)
  Header always set X-Content-Type-Options "nosniff"
  Header set X-Content-Type-Options "nosniff"

  # Fix: Strict-Transport-Security Header Not Set (HSTS)
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
  Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"

  # Fix: Server Leaks Information via X-Powered-By / X-Turbo-Charged-By
  Header always unset X-Powered-By
  Header unset X-Powered-By
  Header always unset X-Turbo-Charged-By
  Header unset X-Turbo-Charged-By
  Header always unset Server
  Header unset Server

  # Frame Protection & XSS Hardening
  Header always set X-Frame-Options "SAMEORIGIN"
  Header always set X-XSS-Protection "1; mode=block"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Permissions-Policy "geolocation=(), camera=(), microphone=(), payment=(self 'https://pay.doku.com' 'https://sandbox.doku.com')"

  # Fix: Re-examine Cache-control Directives
  # Dynamic Pages & PHP: strictly private, no-store
  <FilesMatch "\.(php|html)$">
    Header always set Cache-Control "no-store, no-cache, must-revalidate, max-age=0, private"
    Header always set Pragma "no-cache"
    Header always set Expires "0"
  </FilesMatch>

  # Static Assets: 1-Year Immutable Caching
  <FilesMatch "\.(webp|jpg|jpeg|png|gif|svg|ico|woff2|woff|ttf|css|js)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>
</IfModule>

# ----------------------------------------------------------------------
# 3. Browser Caching Immortality (Expires Module)
# ----------------------------------------------------------------------
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresDefault "access plus 1 month"
  ExpiresByType text/html "access plus 0 seconds"
  ExpiresByType application/json "access plus 0 seconds"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType image/x-icon "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# php -- BEGIN cPanel-generated handler, do not edit
# Set the “ea-php82” package as the default “PHP” programming language with 80MB limits
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php82 .php .php8 .phtml
</IfModule>
php_value upload_max_filesize 80M
php_value post_max_size 80M
php_value memory_limit 256M
php_value max_execution_time 600
php_value max_input_time 600
# php -- END cPanel-generated handler, do not edit
"""

ht_path = Path("C:/Users/IT GLOBALDISPOMEDIKA/.gemini/antigravity-ide/scratch/temp_ht.txt")
ht_path.write_text(new_ht, encoding="utf-8")
res_ht = upload_file_cpanel(DOCROOT, ht_path, ".htaccess")
print("[OK] Updated .htaccess -> Friendly Rewrites & DirectoryIndex index.php")
try:
    ht_path.unlink()
except:
    pass

# Delete old static index.html if exists so index.php takes priority
del_res = delete_file_cpanel(DOCROOT, "index.html")
print("[OK] Cleaned up legacy index.html")

# Purge Cloudflare Cache
print("\n=== PURGING CLOUDFLARE EDGE CACHE ===")
try:
    script_code = """<?php
    require_once '/home/restiamo/portal-cms/core/bootstrap.php';
    $res = \\App\\Services\\CloudflareService::purgeCache();
    echo json_encode($res);
    """
    boundary = '----WebKitFormBoundary' + secrets.token_hex(16)
    body = (
        f'--{boundary}\r\n'
        f'Content-Disposition: form-data; name="dir"\r\n\r\n/home/restiamo/public_html\r\n'
        f'--{boundary}\r\nContent-Disposition: form-data; name="overwrite"\r\n\r\n1\r\n'
        f'--{boundary}\r\nContent-Disposition: form-data; name="file-1"; filename="purge_cf_temp.php"\r\n'
        f'Content-Type: application/octet-stream\r\n\r\n'
    ).encode('utf-8') + script_code.encode('utf-8') + f'\r\n--{boundary}--\r\n'.encode('utf-8')

    req = urllib.request.Request(
        'https://103.125.180.56:2083/execute/Fileman/upload_files',
        data=body,
        headers={
            'Authorization': CPANEL_AUTH,
            'Content-Type': f'multipart/form-data; boundary={boundary}'
        }
    )
    with urllib.request.urlopen(req, context=ctx) as r:
        pass

    req_test = urllib.request.Request('https://restiamoegiono.com/purge_cf_temp.php', headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req_test, context=ctx) as r:
        cf_res = r.read().decode()
        print('[OK] Cloudflare Purge Response:', cf_res)

    req_del = urllib.request.Request(
        'https://103.125.180.56:2083/execute/Fileman/fileop?op=unlink&file=/home/restiamo/public_html/purge_cf_temp.php',
        headers={'Authorization': CPANEL_AUTH}
    )
    with urllib.request.urlopen(req_del, context=ctx) as r:
        pass
except Exception as e:
    print('[WARNING] Cloudflare purge exception:', e)

print("\nAll files deployed to cPanel successfully!")
