import fs from 'node:fs';
import path from 'node:path';

const STORAGE_DIR = path.join(process.cwd(), 'storage');
const DB_FILE = path.join(STORAGE_DIR, 'orders.json');

function ensureDb() {
  if (!fs.existsSync(STORAGE_DIR)) {
    fs.mkdirSync(STORAGE_DIR, { recursive: true });
  }
  const ebooksDir = path.join(STORAGE_DIR, 'ebooks');
  if (!fs.existsSync(ebooksDir)) {
    fs.mkdirSync(ebooksDir, { recursive: true });
  }
  if (!fs.existsSync(DB_FILE)) {
    fs.writeFileSync(DB_FILE, JSON.stringify([], null, 2), 'utf8');
  }
}

export function getOrders() {
  ensureDb();
  try {
    const raw = fs.readFileSync(DB_FILE, 'utf8');
    const orders = JSON.parse(raw || '[]');
    return Array.isArray(orders) ? orders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at)) : [];
  } catch (err) {
    console.error('Error reading orders database:', err);
    return [];
  }
}

export function getOrder(invoiceNumber) {
  const orders = getOrders();
  return orders.find(o => String(o.invoice_number) === String(invoiceNumber)) || null;
}

export function saveOrder(orderData) {
  ensureDb();
  const orders = getOrders();
  const existingIdx = orders.findIndex(o => String(o.invoice_number) === String(orderData.invoice_number));

  if (existingIdx >= 0) {
    orders[existingIdx] = {
      ...orders[existingIdx],
      ...orderData,
      updated_at: new Date().toISOString()
    };
  } else {
    orders.unshift({
      ...orderData,
      created_at: orderData.created_at || new Date().toISOString(),
      status: orderData.status || 'PENDING'
    });
  }

  // Atomic write
  const tempFile = `${DB_FILE}.tmp.${Date.now()}`;
  fs.writeFileSync(tempFile, JSON.stringify(orders, null, 2), 'utf8');
  fs.renameSync(tempFile, DB_FILE);
  return orderData;
}

export function updateOrderStatus(invoiceNumber, updates) {
  const order = getOrder(invoiceNumber);
  if (!order) return null;
  const updated = {
    ...order,
    ...updates,
    updated_at: new Date().toISOString()
  };
  saveOrder(updated);
  return updated;
}

export function getStats() {
  const orders = getOrders();
  let totalRevenue = 0;
  let countSent = 0;
  let countPaid = 0;
  let countPending = 0;

  for (const o of orders) {
    if (o.status === 'SENT' || o.status === 'PAID') {
      totalRevenue += Number(o.amount || 0);
    }
    if (o.status === 'SENT') countSent++;
    else if (o.status === 'PAID') countPaid++;
    else if (o.status === 'PENDING') countPending++;
  }

  return {
    totalRevenue,
    totalOrders: orders.length,
    countSent,
    countPaid,
    countPending
  };
}
