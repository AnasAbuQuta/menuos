export const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']

export function defaultOpeningHours() {
  return Object.fromEntries(DAYS.map((day) => [day, { is_open: day !== 'friday', open: day === 'friday' ? null : '09:00', close: day === 'friday' ? null : '23:00' }]))
}

export function setTwentyFourHours(hours) {
  hours.is_open = true; hours.open = '00:00'; hours.close = '23:59'
}

export function copyHours(source, target) {
  Object.assign(target, {
    is_open: Boolean(source.is_open),
    open: source.open ?? null,
    close: source.close ?? null,
  })
}
