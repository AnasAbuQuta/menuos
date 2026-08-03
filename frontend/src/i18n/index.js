import { createI18n } from 'vue-i18n'
import ar from './locales/ar.json'
import en from './locales/en.json'

export const LOCALE_KEY = 'menuos_locale'
const savedLocale = localStorage.getItem(LOCALE_KEY)
const initialLocale = ['ar', 'en'].includes(savedLocale) ? savedLocale : 'ar'

export const i18n = createI18n({ legacy: false, locale: initialLocale, fallbackLocale: 'en', messages: { ar, en } })

export function applyDocumentLocale(locale) {
  document.documentElement.lang = locale
  document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr'
}

export function setLocale(locale) {
  if (!['ar', 'en'].includes(locale)) return
  i18n.global.locale.value = locale
  localStorage.setItem(LOCALE_KEY, locale)
  applyDocumentLocale(locale)
}

applyDocumentLocale(initialLocale)
