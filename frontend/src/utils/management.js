export function localizedValue(record, field, locale) {
  return record?.[`${field}_${locale}`] || record?.[`${field}_${locale === 'ar' ? 'en' : 'ar'}`] || record?.[field] || ''
}

export function secondaryLocalizedValue(record, field, locale) {
  const value = record?.[`${field}_${locale === 'ar' ? 'en' : 'ar'}`] || ''
  return value && value !== localizedValue(record, field, locale) ? value : ''
}

export function filterCategories(categories, search) {
  const term = search.trim().toLocaleLowerCase()
  if (!term) return categories
  return categories.filter((category) => [category.name_ar, category.name_en, category.name]
    .filter(Boolean).some((name) => name.toLocaleLowerCase().includes(term)))
}

export function reorderByIds(records, orderedIds) {
  const byId = new Map(records.map((record) => [Number(record.id), record]))
  return orderedIds.map(Number).map((id) => byId.get(id)).filter(Boolean)
}

export async function optimisticFieldUpdate(record, field, value, save) {
  const previous = record[field]
  record[field] = value
  try { return await save() }
  catch (error) { record[field] = previous; throw error }
}

export function publicMenuUrl(slug, origin = window.location.origin) {
  return slug ? `${origin.replace(/\/$/, '')}/menu/${encodeURIComponent(slug)}` : ''
}
