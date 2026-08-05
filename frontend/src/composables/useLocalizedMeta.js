import { onBeforeUnmount, watch } from 'vue'
import { useI18n } from 'vue-i18n'

export function useLocalizedMeta(titleKey, descriptionKey = null) {
  const { locale, t } = useI18n()
  const originalTitle = document.title
  const description = document.querySelector('meta[name="description"]')
  const originalDescription = description?.content || ''
  let appliedTitle = ''
  let appliedDescription = ''

  const stop = watch(locale, () => {
    appliedTitle = t(titleKey)
    document.title = appliedTitle
    if (descriptionKey && description) {
      appliedDescription = t(descriptionKey)
      description.content = appliedDescription
    }
  }, { immediate: true })

  onBeforeUnmount(() => {
    stop()
    if (document.title === appliedTitle) document.title = originalTitle
    if (description && description.content === appliedDescription) description.content = originalDescription
  })
}
