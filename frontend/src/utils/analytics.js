export function trafficSource(routeSource = '', referrer = document.referrer) {
  if (routeSource === 'qr') return 'qr'
  if (!referrer) return 'direct'
  try {
    const host = new URL(referrer).hostname
    if (/google|bing|yahoo|duckduckgo/.test(host)) return 'search'
    if (/facebook|instagram|twitter|x\.com|linkedin|t\.me|telegram|whatsapp/.test(host)) return 'social'
    return host === globalThis.location?.hostname ? 'direct' : 'referral'
  } catch { return 'direct' }
}
