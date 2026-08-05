const managed = []

function meta(selector, attributes) {
  let element = document.head.querySelector(selector)
  if (!element) { element = document.createElement('meta'); document.head.appendChild(element); managed.push(element) }
  Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, value || ''))
  return element
}

export function applyRestaurantSeo(menu, canonicalUrl, locale = 'en') {
  const isArabic = locale === 'ar'
  const title = `${menu.name} · ${isArabic ? 'القائمة' : 'Menu'}`
  const description = (menu.description || (isArabic
    ? `اعرض قائمة ${menu.name} ومعلومات التواصل وساعات العمل.`
    : `View the menu, contact details, and opening hours for ${menu.name}.`)).slice(0, 160)
  const image = menu.cover_image_url || menu.logo_url || ''
  document.title = title
  meta('meta[name="description"]', { name: 'description', content: description })
  meta('meta[name="keywords"]', { name: 'keywords', content: isArabic ? `${menu.name}, مطعم, قائمة, طعام` : `${menu.name}, restaurant, menu, food` })
  meta('meta[property="og:title"]', { property: 'og:title', content: title })
  meta('meta[property="og:description"]', { property: 'og:description', content: description })
  meta('meta[property="og:type"]', { property: 'og:type', content: 'restaurant' })
  meta('meta[property="og:url"]', { property: 'og:url', content: canonicalUrl })
  if (image) meta('meta[property="og:image"]', { property: 'og:image', content: image })
  meta('meta[name="twitter:card"]', { name: 'twitter:card', content: image ? 'summary_large_image' : 'summary' })
  meta('meta[name="twitter:title"]', { name: 'twitter:title', content: title })
  meta('meta[name="twitter:description"]', { name: 'twitter:description', content: description })
  if (image) meta('meta[name="twitter:image"]', { name: 'twitter:image', content: image })
  let canonical = document.head.querySelector('link[rel="canonical"]')
  if (!canonical) { canonical = document.createElement('link'); canonical.rel = 'canonical'; document.head.appendChild(canonical); managed.push(canonical) }
  canonical.href = canonicalUrl
  const schema = document.createElement('script'); schema.type = 'application/ld+json'; schema.dataset.menuosSeo = 'true'; schema.textContent = JSON.stringify({ '@context': 'https://schema.org', '@type': 'Restaurant', name: menu.name, description, url: canonicalUrl, image: [menu.logo_url, menu.cover_image_url].filter(Boolean), telephone: menu.phone || undefined, address: menu.address || undefined, currenciesAccepted: menu.currency })
  document.head.appendChild(schema); managed.push(schema)
  return { title, description }
}

export function clearRestaurantSeo(originalTitle = 'MenuOS', expectedTitle = '') {
  if (!expectedTitle || document.title === expectedTitle) document.title = originalTitle
  managed.splice(0).forEach((element) => element.remove())
}
