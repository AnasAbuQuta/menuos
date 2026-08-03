import assert from 'node:assert/strict'
import test from 'node:test'
import { currencyCodes } from '../src/constants/currencies.js'
import { calculateSetupCompletion } from '../src/utils/setupChecklist.js'
import { themeFor, themeKeys, themeVariables } from '../src/theme/restaurantThemes.js'

test('currency dropdown is restricted to supported stored codes', () => {
  assert.deepEqual(currencyCodes, ['ILS', 'USD', 'JOD'])
})

test('all safe themes resolve and invalid themes fall back to modern', () => {
  assert.deepEqual(themeKeys, ['modern', 'minimal', 'warm', 'dark', 'cafe', 'fast_food'])
  assert.equal(themeFor('invalid'), themeFor('modern'))
  assert.equal(themeVariables('warm')['--theme-primary'], themeFor('warm').primary)
})

test('custom primary color overrides and reset restores the theme default', () => {
  assert.equal(themeVariables('cafe', '#123456')['--theme-primary'], '#123456')
  assert.equal(themeVariables('cafe', null)['--theme-primary'], themeFor('cafe').primary)
})

test('setup checklist reports percentage and actionable incomplete links', () => {
  const restaurant = { name_en: 'MenuOS Cafe', description_en: 'Coffee', currency: 'ILS', theme_key: 'modern' }
  const result = calculateSetupCompletion({ restaurant, categories: [], items: [] })
  assert.equal(result.completed.length, 4)
  assert.equal(result.percentage, 36)
  assert.ok(result.incomplete.some(({ key, route }) => key === 'category' && route === '/categories'))
  assert.ok(result.incomplete.some(({ key, route }) => key === 'item' && route === '/menu-items'))
})

test('active category and available item complete their setup steps', () => {
  const result = calculateSetupCompletion({ restaurant: {}, categories: [{ is_active: true }], items: [{ is_available: true }] })
  assert.equal(result.steps.find(({ key }) => key === 'category').completed, true)
  assert.equal(result.steps.find(({ key }) => key === 'item').completed, true)
})
