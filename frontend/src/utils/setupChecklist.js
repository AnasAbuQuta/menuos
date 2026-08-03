export const setupDefinitions = [
  { key: 'name', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.name_ar || restaurant?.name_en || restaurant?.name) },
  { key: 'description', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.description_ar || restaurant?.description_en || restaurant?.description) },
  { key: 'logo', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.logo_url) },
  { key: 'cover', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.cover_image_url) },
  { key: 'contact', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.phone || restaurant?.whatsapp) },
  { key: 'address', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.address) },
  { key: 'hours', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.opening_hours && Object.values(restaurant.opening_hours).some((day) => day?.is_open)) },
  { key: 'currency', route: '/restaurant', complete: ({ restaurant }) => ['ILS', 'USD', 'JOD'].includes(restaurant?.currency) },
  { key: 'theme', route: '/restaurant', complete: ({ restaurant }) => Boolean(restaurant?.theme_key) },
  { key: 'category', route: '/categories', complete: ({ categories }) => categories.some((category) => category.is_active) },
  { key: 'item', route: '/menu-items', complete: ({ items }) => items.some((item) => item.is_available) },
]

export function calculateSetupCompletion(context) {
  const steps = setupDefinitions.map((definition) => ({ ...definition, completed: definition.complete(context) }))
  const completed = steps.filter((step) => step.completed)
  return { percentage: Math.round(completed.length / steps.length * 100), completed, incomplete: steps.filter((step) => !step.completed), steps }
}
