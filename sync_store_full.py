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
    (DOCROOT, base_local / "index.php", "index.php"),
    (DOCROOT, base_local / "product.php", "product.php"),
    (DOCROOT, base_local / "track.php", "track.php"),
    (DOCROOT, base_local / "guide.php", "guide.php"),
    (DOCROOT, base_local / "license.php", "license.php"),
    (DOCROOT, base_local / "terms.php", "terms.php"),
    (DOCROOT + "/services", base_local / "services/DokuService.php", "DokuService.php"),
    (DOCROOT + "/services", base_local / "services/WatermarkService.php", "WatermarkService.php"),
    (DOCROOT + "/services", base_local / "services/MailService.php", "MailService.php"),
    (DOCROOT + "/services", base_local / "services/FulfillmentService.php", "FulfillmentService.php"),
    (DOCROOT + "/api", base_local / "api/checkout.php", "checkout.php"),
    (DOCROOT + "/api", base_local / "api/doku-notify.php", "doku-notify.php"),
    (DOCROOT + "/api", base_local / "api/order-status.php", "order-status.php"),
    (DOCROOT + "/api", base_local / "api/admin-action.php", "admin-action.php"),
    (DOCROOT + "/payment/result", base_local / "payment/result/index.php", "index.php"),
    (DOCROOT + "/admin", base_local / "admin/index.php", "index.php"),
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

# Update .htaccess with rewrite rules for individual product pages & friendly URLs
new_ht = """RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

DirectoryIndex index.php index.html

# Friendly URLs for Standalone Product Pages & Policies
RewriteRule ^produk/([^/]+)/?$ product.php?id=$1 [L,QSA]
RewriteRule ^product/([^/]+)/?$ product.php?id=$1 [L,QSA]
RewriteRule ^cek-pesanan/?$ track.php [L,QSA]
RewriteRule ^panduan/?$ guide.php [L,QSA]
RewriteRule ^lisensi/?$ license.php [L,QSA]
RewriteRule ^syarat-ketentuan/?$ terms.php [L,QSA]

# Security: Prevent search engines from indexing admin or raw internal directories
<If "%{REQUEST_URI} =~ m#^/(admin|api|payment|storage|bin)#">
    Header set X-Robots-Tag "noindex, nofollow, noarchive, nosnippet"
</If>

# php -- BEGIN cPanel-generated handler, do not edit
# Set the “ea-php81” package as the default “PHP” programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php81 .php .php8 .phtml
</IfModule>
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
