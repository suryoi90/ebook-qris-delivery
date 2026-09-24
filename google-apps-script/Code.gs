/**
 * Private order recorder and ebook delivery endpoint.
 * Set Script Properties: GAS_SHARED_SECRET, SPREADSHEET_ID, TEMPLATE_DOC_ID,
 * OUTPUT_FOLDER_ID, VERCEL_APP_URL, PDF_PROTECT_SECRET.
 */
const ORDER_SHEET = 'Orders';
const HEADERS = ['invoice_number', 'created_at', 'customer_name', 'customer_email', 'organization', 'product_id', 'product_title', 'amount', 'status', 'transaction_id', 'paid_at', 'license_id'];

function doPost(e) {
  try {
    const body = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    const props = PropertiesService.getScriptProperties();
    if (!body.shared_secret || !constantTimeEquals_(body.shared_secret, props.getProperty('GAS_SHARED_SECRET') || '')) {
      return json_({ status: 'error', message: 'unauthorized' });
    }
    if (body.action === 'register_order') return registerOrder_(body);
    if (body.action === 'deliver_paid_order') return deliverPaidOrder_(body);
    return json_({ status: 'error', message: 'unknown action' });
  } catch (err) {
    console.error('doPost failed: ' + err.message);
    return json_({ status: 'error', message: 'request failed' });
  }
}

function registerOrder_(body) {
  const invoice = String(body.invoice_number || '');
  const amount = Number(body.amount);
  const email = String(body.customer_email || '').trim().toLowerCase();
  if (!/^EB-[A-Za-z0-9-]{8,60}$/.test(invoice) || !Number.isSafeInteger(amount) || amount < 1 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return json_({ status: 'error', message: 'invalid order' });
  }
  const sheet = getSheet_();
  const lock = LockService.getScriptLock();
  lock.waitLock(20000);
  try {
    const rows = sheet.getDataRange().getValues();
    const idx = rows[0].indexOf('invoice_number');
    if (rows.slice(1).some(r => String(r[idx]) === invoice)) return json_({ status: 'success', duplicate: true });
    sheet.appendRow([invoice, new Date(), String(body.customer_name || '').slice(0, 100), email,
      String(body.organization || '-').slice(0, 150), String(body.product_id || ''),
      String(body.product_title || '').slice(0, 200), amount, 'PENDING', '', '', '']);
  } finally { lock.releaseLock(); }
  return json_({ status: 'success' });
}

function deliverPaidOrder_(body) {
  const invoice = String(body.invoice_number || '');
  const amount = Number(body.amount);
  const transactionId = String(body.transaction_id || '').slice(0, 128);
  if (!invoice || !Number.isSafeInteger(amount) || body.transaction_status !== 'SUCCESS') {
    return json_({ status: 'error', message: 'invalid payment event' });
  }
  const sheet = getSheet_();
  const lock = LockService.getScriptLock();
  lock.waitLock(20000);
  let record = null;
  try {
    const values = sheet.getDataRange().getValues();
    const columns = values[0];
    const rowIndex = values.findIndex((row, i) => i > 0 && String(row[columns.indexOf('invoice_number')]) === invoice);
    if (rowIndex < 1) return json_({ status: 'error', message: 'order not found' });
    const row = values[rowIndex];
    const col = key => columns.indexOf(key);
    if (Number(row[col('amount')]) !== amount) return json_({ status: 'error', message: 'amount mismatch' });
    if (row[col('status')] === 'SENT') return json_({ status: 'success', duplicate: true });
    if (row[col('status')] === 'PROCESSING') return json_({ status: 'processing' });
    const ebookIds = ['iso-27001-main', 'kepatuhan-pdp', 'ropa-dpia', 'lia-tia', '11-kontrol-v2'];
    if (ebookIds.indexOf(String(row[col('product_id')])) < 0) {
      sheet.getRange(rowIndex + 1, col('status') + 1).setValue('PAID_MANUAL_FULFILLMENT');
      sheet.getRange(rowIndex + 1, col('transaction_id') + 1).setValue(transactionId);
      sheet.getRange(rowIndex + 1, col('paid_at') + 1).setValue(new Date());
      return json_({ status: 'success', fulfillment: 'manual' });
    }
    sheet.getRange(rowIndex + 1, col('status') + 1).setValue('PROCESSING');
    if (transactionId) sheet.getRange(rowIndex + 1, col('transaction_id') + 1).setValue(transactionId);
    record = { rowIndex: rowIndex + 1, columns, row };
  } finally { lock.releaseLock(); }

  const sheetAgain = sheet;
  let tempDoc;
  try {
    const c = key => record.row[record.columns.indexOf(key)];
    const name = String(c('customer_name') || 'Pembeli');
    const email = String(c('customer_email') || '');
    const organization = String(c('organization') || '-');
    const title = String(c('product_title') || 'E-Book');
    const license = Utilities.getUuid().replace(/-/g, '').slice(0, 12).toUpperCase();
    const pdfPassword = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '').slice(0, 8);
    const watermark = 'Lisensi resmi untuk ' + name + ' (' + email + ') | ' + organization + ' | ID: ' + license;
    const template = DriveApp.getFileById(requiredProperty_('TEMPLATE_DOC_ID'));
    const folder = DriveApp.getFolderById(requiredProperty_('OUTPUT_FOLDER_ID'));
    tempDoc = template.makeCopy((title + ' - ' + license).slice(0, 180), folder);
    const doc = DocumentApp.openById(tempDoc.getId());
    const footer = doc.getFooter() || doc.addFooter();
    footer.setText(watermark);
    const footerParagraph = footer.getParagraphs()[0];
    footerParagraph.setAlignment(DocumentApp.HorizontalAlignment.CENTER);
    footerParagraph.editAsText().setFontSize(8).setForegroundColor('#64748b');
    const bodyText = doc.getBody();
    bodyText.replaceText('\\{\\{NAMA\\}\\}', escapeReplacement_(name));
    bodyText.replaceText('\\{\\{EMAIL\\}\\}', escapeReplacement_(email));
    bodyText.replaceText('\\{\\{ORGANISASI\\}\\}', escapeReplacement_(organization));
    bodyText.replaceText('\\{\\{LISENSI\\}\\}', escapeReplacement_(license));
    doc.saveAndClose();

    const exportResponse = UrlFetchApp.fetch('https://docs.google.com/document/d/' + tempDoc.getId() + '/export?format=pdf', {
      headers: { Authorization: 'Bearer ' + ScriptApp.getOAuthToken() }, muteHttpExceptions: true,
    });
    const rawPdf = exportResponse.getBlob().getBytes();
    if (exportResponse.getResponseCode() !== 200 || rawPdf.length < 8 || rawPdf.length > 2400000 || bytesToText_(rawPdf.slice(0, 5)) !== '%PDF-') {
      throw new Error('PDF export failed or exceeds the supported 2.4 MB size.');
    }

    const vercelUrl = requiredProperty_('VERCEL_APP_URL').replace(/\/$/, '') + '/api/protect-pdf';
    const protectResponse = UrlFetchApp.fetch(vercelUrl, {
      method: 'post', contentType: 'application/json',
      headers: { 'x-pdf-protect-secret': requiredProperty_('PDF_PROTECT_SECRET') },
      payload: JSON.stringify({ pdf_base64: Utilities.base64Encode(rawPdf), password: pdfPassword,
        name, email, license_id: license }), muteHttpExceptions: true,
    });
    if (protectResponse.getResponseCode() !== 200) throw new Error('Vercel PDF protection failed.');
    const protectedData = JSON.parse(protectResponse.getContentText());
    const finalBytes = Utilities.base64Decode(protectedData.pdf_base64 || '');
    if (finalBytes.length < 8 || bytesToText_(finalBytes.slice(0, 5)) !== '%PDF-') throw new Error('Vercel returned an invalid PDF.');
    const attachment = Utilities.newBlob(finalBytes, 'application/pdf', safeFileName_(title + ' - ' + name) + '.pdf');

    GmailApp.sendEmail(email, 'E-Book Anda: ' + title, 'Terlampir PDF ebook ber-watermark. Password pembuka PDF akan dikirim dalam email terpisah.', {
      attachments: [attachment], name: 'Restia Moegiono Official',
    });
    GmailApp.sendEmail(email, 'Password PDF E-Book: ' + title, 'Password untuk membuka PDF Anda:\n\n' + pdfPassword + '\n\nSimpan password ini secara aman.', {
      name: 'Restia Moegiono Official',
    });

    const columns = record.columns;
    const rowNo = record.rowIndex;
    sheetAgain.getRange(rowNo, columns.indexOf('status') + 1).setValue('SENT');
    sheetAgain.getRange(rowNo, columns.indexOf('paid_at') + 1).setValue(new Date());
    sheetAgain.getRange(rowNo, columns.indexOf('license_id') + 1).setValue(license);
    return json_({ status: 'success' });
  } catch (err) {
    if (record) sheetAgain.getRange(record.rowIndex, record.columns.indexOf('status') + 1).setValue('PAID');
    console.error('Delivery failed: ' + err.message);
    return json_({ status: 'error', message: 'delivery failed' });
  } finally {
    if (tempDoc) { try { tempDoc.setTrashed(true); } catch (ignore) {} }
  }
}

function getSheet_() {
  const ss = SpreadsheetApp.openById(requiredProperty_('SPREADSHEET_ID'));
  let sheet = ss.getSheetByName(ORDER_SHEET);
  if (!sheet) sheet = ss.insertSheet(ORDER_SHEET);
  if (sheet.getLastRow() === 0) sheet.appendRow(HEADERS);
  return sheet;
}
function requiredProperty_(key) {
  const value = PropertiesService.getScriptProperties().getProperty(key);
  if (!value) throw new Error('Missing Script Property: ' + key);
  return value;
}
function json_(value) { return ContentService.createTextOutput(JSON.stringify(value)).setMimeType(ContentService.MimeType.JSON); }
function constantTimeEquals_(a, b) {
  const x = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, String(a));
  const y = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, String(b));
  let diff = x.length ^ y.length;
  for (let i = 0; i < Math.min(x.length, y.length); i++) diff |= x[i] ^ y[i];
  return diff === 0;
}
function escapeReplacement_(value) { return String(value).replace(/\\/g, '\\\\').replace(/\$/g, '\\$'); }
function bytesToText_(bytes) { return bytes.map(b => String.fromCharCode((b + 256) % 256)).join(''); }
function safeFileName_(value) { return String(value).replace(/[\\/:*?"<>|]/g, '-').slice(0, 120); }
