import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

const MAX_QUANTITY = 99

export function sanitizeStoredCart(value, availableItems) {
  const available = new Map(availableItems.map((item) => [Number(item.id), item]))
  const sanitized = { items: [], note: '' }
  if (!value || typeof value !== 'object') return sanitized
  if (typeof value.note === 'string') sanitized.note = value.note.slice(0, 500)
  if (!Array.isArray(value.items)) return sanitized

  const seen = new Set()
  for (const entry of value.items) {
    const id = Number(entry?.id)
    const quantity = Number(entry?.quantity)
    if (!Number.isInteger(id) || !Number.isInteger(quantity) || quantity < 1 || quantity > MAX_QUANTITY || seen.has(id) || !available.has(id)) continue
    seen.add(id)
    sanitized.items.push({ item: available.get(id), quantity })
  }
  return sanitized
}

export const useCartStore = defineStore('cart', () => {
  const restaurantSlug = ref('')
  const items = ref([])
  const note = ref('')
  const totalQuantity = computed(() => items.value.reduce((sum, line) => sum + line.quantity, 0))
  const totalPrice = computed(() => items.value.reduce((sum, line) => sum + Number(line.item.price) * line.quantity, 0))
  const storageKey = computed(() => `menuos_cart_${restaurantSlug.value}`)

  function persist() {
    if (!restaurantSlug.value) return
    localStorage.setItem(storageKey.value, JSON.stringify({
      items: items.value.map((line) => ({ id: line.item.id, quantity: line.quantity })),
      note: note.value,
    }))
  }

  function initialize(slug, availableItems) {
    restaurantSlug.value = slug
    let restored = null
    try { restored = JSON.parse(localStorage.getItem(storageKey.value)) } catch { localStorage.removeItem(storageKey.value) }
    const sanitized = sanitizeStoredCart(restored, availableItems)
    items.value = sanitized.items
    note.value = sanitized.note
    persist()
  }

  function add(item) {
    const line = items.value.find((entry) => entry.item.id === item.id)
    if (line) line.quantity = Math.min(MAX_QUANTITY, line.quantity + 1)
    else items.value.push({ item, quantity: 1 })
    persist()
  }

  function increase(id) {
    const line = items.value.find((entry) => entry.item.id === id)
    if (line) line.quantity = Math.min(MAX_QUANTITY, line.quantity + 1)
    persist()
  }

  function decrease(id) {
    const line = items.value.find((entry) => entry.item.id === id)
    if (!line) return
    if (line.quantity === 1) remove(id)
    else { line.quantity -= 1; persist() }
  }

  function remove(id) {
    items.value = items.value.filter((entry) => entry.item.id !== id)
    persist()
  }

  function updateNote(value) {
    note.value = String(value).slice(0, 500)
    persist()
  }

  function clear() {
    items.value = []
    note.value = ''
    persist()
  }

  return { items, note, totalQuantity, totalPrice, initialize, add, increase, decrease, remove, updateNote, clear }
})
