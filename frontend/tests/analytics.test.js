import test from 'node:test'
import assert from 'node:assert/strict'

globalThis.document = { referrer: '' }
const { trafficSource } = await import('../src/utils/analytics.js')

test('traffic attribution recognizes explicit QR and privacy-safe source groups', () => {
  assert.equal(trafficSource('qr', ''), 'qr')
  assert.equal(trafficSource('', ''), 'direct')
  assert.equal(trafficSource('', 'https://www.google.com/search?q=menu'), 'search')
  assert.equal(trafficSource('', 'https://www.instagram.com/post'), 'social')
})
