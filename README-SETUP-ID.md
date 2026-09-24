# Setup DOKU + watermark/password PDF delivery

This version uses Vercel for DOKU Checkout, DOKU notification signature checks, and PDF watermark/encryption. Google Apps Script stores orders in Google Sheets, creates the personalized PDF from a Google Docs template, and emails the attachment and its password separately.

## Important credential cleanup

The previous repository had DOKU credentials encoded in Base64 in source code. Base64 is reversible. Revoke/rotate every DOKU credential that was previously committed or pasted into chat, including the DOKU key and secret. Also review the old GitHub commit history; removing a value from the latest file does not remove it from earlier commits. Do not paste replacement credentials into source files or chat.

## 1. Prepare Google Drive and Sheets

1. Create a Google Sheet for orders and copy its ID from the URL.
2. Keep the Google Docs ebook template in Drive. Add optional placeholders `{{NAMA}}`, `{{EMAIL}}`, `{{ORGANISASI}}`, and `{{LISENSI}}` where appropriate.
3. Create a private Drive folder for temporary document copies and copy its folder ID.
4. Open the Apps Script editor and add the contents of `google-apps-script/Code.gs`.
5. In Apps Script **Project Settings → Script Properties**, set:
   - `GAS_SHARED_SECRET`: a long random secret shared only with Vercel.
   - `PDF_PROTECT_SECRET`: a different long random secret shared only with Vercel.
   - `SPREADSHEET_ID`: the Google Sheet ID.
   - `TEMPLATE_DOC_ID`: the Google Docs template ID.
   - `OUTPUT_FOLDER_ID`: temporary folder ID.
   - `VERCEL_APP_URL`: the deployed Vercel origin, e.g. `https://your-site.vercel.app`.
6. Deploy as **Web app**, execute as your Google account, and allow access from **Anyone** so Vercel can call it. The code checks the shared secret in the POST body for every action. Copy the `/exec` URL.
7. Complete the first-time Google authorization so Drive, Docs, Sheets, and Gmail scopes are granted.

The `Penerima` sheet and its columns are created automatically on the first request.

## 2. Configure Vercel

Import this repository into Vercel. Add the following Environment Variables for Preview/Production as appropriate:

| Variable | Value |
| --- | --- |
| `DOKU_CLIENT_ID` | Current DOKU Client ID |
| `DOKU_SECRET_KEY` | Newly rotated DOKU Secret Key |
| `DOKU_BASE_URL` | Sandbox: `https://api-sandbox.doku.com`; production: `https://api.doku.com` |
| `APP_BASE_URL` | Public Vercel origin, without a trailing slash |
| `GOOGLE_SCRIPT_URL` | Apps Script deployment `/exec` URL |
| `GAS_SHARED_SECRET` | Same value as Apps Script property |
| `PDF_PROTECT_SECRET` | Same value as Apps Script property |

Generate the two shared secrets independently, for example using a password manager. Do not reuse the DOKU secret for either one. `.env.example` contains names and placeholders only.

## 3. Configure DOKU

1. Test in the DOKU sandbox first.
2. Use `https://your-site.vercel.app/api/webhook` as the notification URL, or let the request override it through `additional_info.override_notification_url`.
3. Keep the checkout redirect/callback separate from payment confirmation. The application delivers only after a valid DOKU-signed notification with `transaction.status = SUCCESS`.
4. Once sandbox verification is complete, add production DOKU credentials in Vercel and change `DOKU_BASE_URL` to `https://api.doku.com`.

## 4. Payment and delivery behavior

- The checkout API selects product title and price from server-side `lib/products.js`; values posted by the browser cannot set the price.
- Vercel verifies DOKU's `Client-Id`, `Request-Id`, `Request-Timestamp`, `Request-Target`, body digest, and HMAC signature before forwarding a success event.
- Google Apps Script matches the invoice and amount to a saved order, prevents duplicate processing, creates a per-customer copy, adds a footer watermark, and exports the PDF.
- Vercel adds an additional per-page footer watermark and AES-256 PDF open password. Apps Script emails the PDF and sends the password in a second email.
- The Vercel PDF endpoint currently limits the source PDF to about **2.4 MB** to stay within serverless request-size limits. Larger ebooks need a Drive-based transfer flow instead of Base64 in a request.
- Paid non-ebook services are recorded as `PAID_MANUAL_FULFILLMENT`; they do not receive a random ebook. Add a service-specific fulfillment workflow if needed.
- Google Apps Script and Gmail have execution and sending quotas. Monitor Apps Script Executions and the `Penerima` sheet for failed delivery rows (`PAID`).

## 5. Before production

Use a sandbox purchase and confirm: the order row is created; an invalid signature is rejected; a valid success notification matches invoice and amount; one personalized PDF is delivered; the PDF asks for its password; the password email arrives; and repeating the same notification does not send a second ebook. Check the deployed Vercel logs and Apps Script Executions without logging secrets or passwords.

PDF passwords deter casual access but are not DRM: recipients can share the password or capture the content after opening the PDF.
