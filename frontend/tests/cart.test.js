import assert from 'node:assert/strict'
import test from 'node:test'
import { sanitizeStoredCart } from '../src/stores/cart.js'
import { buildWhatsAppOrder, normalizeWhatsAppNumber } from '../src/utils/whatsappOrder.js'

const available = [{ id: 1, name: 'Pizza', price: '10.00' }, { id: 2, name: 'Shawarma', price: '7.00' }]

test('restored cart keeps valid canonical items and rejects invalid or stale entries', () => {
  const result = sanitizeStoredCart({ items: [{ id: 1, quantity: 2 }, { id: 999, quantity: 1 }, { id: 2, quantity: 0 }], note: 'No onions' }, available)
  assert.deepEqual(result.items, [{ item: available[0], quantity: 2 }])
  assert.equal(result.note, 'No onions')
})

test('malformed cart is cleared safely', () => {
  assert.deepEqual(sanitizeStoredCart('invalid', available), { items: [], note: '' })
})

test('WhatsApp normalization accepts international digits without inferring a country', () => {
  assert.equal(normalizeWhatsAppNumber('+970 (59) 123-4567'), '970591234567')
  assert.equal(normalizeWhatsAppNumber('bad'), '')
})

test('Arabic message includes quantities, unit prices, totals, and note', () => {
  const message = buildWhatsAppOrder({ restaurantName: 'الريف', lines: [{ item: available[0], quantity: 2 }], total: 20, note: 'بدون بصل', formatMoney: (value) => `${Number(value)} ILS` })
  assert.match(message, /مطعم الريف/)
  assert.match(message, /Pizza × 2 — 20 ILS \(10 ILS للوحدة\)/)
  assert.match(message, /الإجمالي: 20 ILS/)
  assert.match(message, /بدون بصل/)
})
