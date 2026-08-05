export function formatRelativeTime(value, locale, now = Date.now()) {
  const elapsedSeconds = Math.max(0, Math.round((now - new Date(value).getTime()) / 1000))
  const formatter = new Intl.RelativeTimeFormat(locale === 'ar' ? 'ar' : 'en', { numeric: 'auto' })
  if (elapsedSeconds < 60) return formatter.format(-elapsedSeconds, 'second')
  const minutes = Math.round(elapsedSeconds / 60)
  if (minutes < 60) return formatter.format(-minutes, 'minute')
  const hours = Math.round(minutes / 60)
  if (hours < 24) return formatter.format(-hours, 'hour')
  return formatter.format(-Math.round(hours / 24), 'day')
}

export function analyticsSourceKey(source) {
  const normalized = String(source || 'unknown').toLowerCase()
  return ['direct', 'qr', 'social', 'search', 'referral'].includes(normalized) ? normalized : 'unknown'
}
