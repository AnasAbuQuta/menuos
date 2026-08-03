import api from './api.js'
export { trafficSource } from '../utils/analytics.js'

const VISITOR_KEY = 'menuos_visitor_id'
const queues = new Map()
const timers = new Map()

export function anonymousVisitorId() {
  let id = localStorage.getItem(VISITOR_KEY)
  if (!id) {
    id = globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}-${Math.random().toString(36).slice(2)}`
    localStorage.setItem(VISITOR_KEY, id)
  }
  return id
}

export function trackPublicEvent(slug, type, details = {}) {
  if (!slug) return
  const queue = queues.get(slug) || []
  queue.push({ type, ...details, occurred_at: new Date().toISOString() })
  queues.set(slug, queue)
  if (queue.length >= 5) void flushPublicEvents(slug)
  else if (!timers.has(slug)) timers.set(slug, setTimeout(() => void flushPublicEvents(slug), 1800))
}

export async function flushPublicEvents(slug) {
  const events = queues.get(slug)?.splice(0, 20) || []
  clearTimeout(timers.get(slug)); timers.delete(slug)
  if (!events.length) return
  try { await api.post(`/public/menu/${encodeURIComponent(slug)}/analytics`, { visitor_id: anonymousVisitorId(), events }) }
  catch { queues.set(slug, [...events, ...(queues.get(slug) || [])].slice(0, 20)) }
}

export async function getDashboardAnalytics() {
  const { data } = await api.get('/restaurant/analytics')
  return data.data.analytics
}
