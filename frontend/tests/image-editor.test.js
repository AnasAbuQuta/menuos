import test from 'node:test'
import assert from 'node:assert/strict'
import { reactive } from 'vue'
import { IMAGE_PROFILES, fitDimensions, formatFileSize, validateImageFile } from '../src/utils/imageEditor.js'
import { copyHours, setTwentyFourHours } from '../src/utils/openingHours.js'
import { clonePlainData } from '../src/utils/plainData.js'

test('image profiles enforce requested crop ratios and maximum dimensions', () => {
  assert.equal(IMAGE_PROFILES.logo.aspectRatio, 1)
  assert.equal(IMAGE_PROFILES.cover.maxWidth, 1920)
  assert.deepEqual(fitDimensions(4000, 3000, 1200, 1200), { width: 1200, height: 900 })
  assert.deepEqual(fitDimensions(640, 480, 1200, 1200), { width: 640, height: 480 })
})

test('image validation and readable sizes use the upload contract', () => {
  assert.equal(validateImageFile({ type: 'image/webp', size: 1000 }), '')
  assert.equal(validateImageFile({ type: 'image/gif', size: 1000 }), 'imageType')
  assert.equal(validateImageFile({ type: 'image/webp', size: 2 * 1024 * 1024 + 1 }), 'imageSize')
  assert.equal(formatFileSize(1024), '1 KB')
})

test('opening hour shortcuts create independent valid schedules', () => {
  const monday = reactive({ is_open: false, open: null, close: null })
  setTwentyFourHours(monday)
  assert.deepEqual(monday, { is_open: true, open: '00:00', close: '23:59' })
  const tuesday = reactive({})
  copyHours(monday, tuesday)
  tuesday.open = '08:00'
  assert.equal(monday.open, '00:00')
})

test('reactive setup data is converted into a cloneable API payload', () => {
  const form = reactive({
    name_ar: 'بيلا باستا',
    opening_hours: { monday: { is_open: true, open: '09:00', close: '23:00' } },
  })

  const payload = clonePlainData(form)
  payload.opening_hours.monday.open = '10:00'

  assert.equal(form.opening_hours.monday.open, '09:00')
  assert.deepEqual(payload, {
    name_ar: 'بيلا باستا',
    opening_hours: { monday: { is_open: true, open: '10:00', close: '23:00' } },
  })
})
