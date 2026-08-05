import assert from 'node:assert/strict'
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs'
import { join, relative } from 'node:path'
import test from 'node:test'
import { fileURLToPath } from 'node:url'

const root = fileURLToPath(new URL('../', import.meta.url))
const read = (path) => readFileSync(join(root, path), 'utf8')
const catalogs = { ar: JSON.parse(read('src/i18n/locales/ar.json')), en: JSON.parse(read('src/i18n/locales/en.json')) }
const leafKeys = (value, prefix = '') => Object.entries(value).flatMap(([key, child]) => child && typeof child === 'object' && !Array.isArray(child) ? leafKeys(child, `${prefix}${key}.`) : [`${prefix}${key}`]).sort()

test('Arabic and English translation catalogs have identical nested keys', () => {
  assert.deepEqual(leafKeys(catalogs.ar), leafKeys(catalogs.en))
})

test('release-blocking surfaces provide distinct Arabic and English interface copy', () => {
  const paths = [
    'dashboard.performance', 'setup.createTitle', 'restaurant.saveSettings', 'qr.generating', 'errors.notFound',
    'imageEditor.rotateLeft', 'themes.previewSize', 'analytics.empty', 'landing.scanMenu', 'pwa.installTitle', 'offline.title',
  ]
  const get = (catalog, path) => path.split('.').reduce((value, key) => value[key], catalog)
  for (const path of paths) {
    assert.equal(typeof get(catalogs.ar, path), 'string', `${path} missing Arabic text`)
    assert.equal(typeof get(catalogs.en, path), 'string', `${path} missing English text`)
    assert.notEqual(get(catalogs.ar, path), get(catalogs.en, path), `${path} should be localized`)
  }
})

const allowedVisibleText = [
  /^(Menu|MenuOS|WhatsApp|HEX|English|Bella Pasta|Handmade Italian kitchen|Fresh Pasta|Fresh Pasta · Pizza · Dolci|Pizza|Dolci|Tagliatelle|Truffle Fettuccine|Tagliatelle al Pomodoro|Hand-cut pasta and basil|Classic Tiramisu|Espresso and mascarpone)$/,
  /^(ILS|USD|JOD|JPG|PNG|WebP|QR|PWA|URL|SVG|API|Alt\+[MCSD])$/,
  /^hello@menuos\.app$/,
]
const allowed = (text) => allowedVisibleText.some((pattern) => pattern.test(text.trim()))
const sourceFiles = (directory) => readdirSync(directory).flatMap((name) => {
  const path = join(directory, name)
  return statSync(path).isDirectory() ? sourceFiles(path) : path.endsWith('.vue') || path.endsWith('.js') ? [path] : []
})

test('frontend source does not bypass i18n with likely user-facing English text', () => {
  const findings = []
  for (const path of sourceFiles(join(root, 'src'))) {
    const source = readFileSync(path, 'utf8')
    const template = source.match(/<template>([\s\S]*?)<\/template>/)?.[1] || ''
    for (const match of template.matchAll(/>([^<>{}\n]*[A-Za-z][^<>{}\n]*)</g)) {
      const text = match[1].trim()
      if (text && !allowed(text) && /[A-Za-z]{3}/.test(text)) findings.push(`${relative(root, path)}: visible text “${text}”`)
    }
    for (const match of template.matchAll(/(?<![:\w-])(?:aria-label|title|alt|placeholder|label)=["']([A-Za-z][^"']*)["']/g)) {
      if (!allowed(match[1])) findings.push(`${relative(root, path)}: static attribute “${match[1]}”`)
    }
    for (const match of source.matchAll(/\btoast\.(?:success|error|info|warning)\(\s*["']([A-Za-z][^"']*)["']/g)) {
      if (!allowed(match[1])) findings.push(`${relative(root, path)}: static message “${match[1]}”`)
    }
    for (const match of source.matchAll(/\bapiError\([^,]+,\s*["']([A-Za-z][^"']*)["']/g)) {
      if (!allowed(match[1])) findings.push(`${relative(root, path)}: static API fallback “${match[1]}”`)
    }
  }
  assert.deepEqual(findings, [], `Hardcoded UI text found. Translate it or document a narrowly justified allowlist entry:\n${findings.join('\n')}`)
})

test('release-blocking localized surfaces use the shared catalogs', () => {
  for (const path of [
    'src/views/DashboardPage.vue', 'src/views/RestaurantSetupPage.vue', 'src/views/RestaurantSettingsPage.vue', 'src/views/QrCodePage.vue',
    'src/views/NotFoundPage.vue', 'src/views/ServerErrorPage.vue', 'src/views/UnauthorizedPage.vue', 'src/views/NetworkErrorPage.vue',
    'src/components/ImageEditorModal.vue', 'src/components/ThemeSelector.vue', 'src/components/PwaInstallPrompt.vue',
  ]) assert.match(read(path), /(?:\$t|\bt\()/, `${path} must use Vue I18n`)
})

test('offline fallback detects the same stored locale without Vue or network access', () => {
  const offline = read('public/offline.html')
  assert.match(offline, /localStorage\.getItem\('menuos_locale'\)/)
  assert.match(offline, /document\.documentElement\.dir/)
  assert.match(offline, /location\.reload\(\)/)
  assert.match(offline, /أنت غير متصل بالإنترنت/)
  assert.match(offline, /You are offline/)
})

test('localized metadata helper reacts to locale changes', () => {
  const helper = read('src/composables/useLocalizedMeta.js')
  assert.match(helper, /watch\(locale/)
  assert.match(helper, /document\.title/)
  assert.match(helper, /meta\[name="description"\]/)
  assert.match(helper, /document\.title === appliedTitle/)
  assert.ok(!Object.values(catalogs.en.meta).some((value) => typeof value === 'string' && value.includes(' | ')), 'Vue I18n reserves | for plural forms')
  assert.ok(!Object.values(catalogs.ar.meta).some((value) => typeof value === 'string' && value.includes(' | ')), 'Vue I18n reserves | for plural forms')
})

test('public menu SEO cleanup cannot overwrite metadata from a newer route', () => {
  const seo = read('src/utils/seo.js')
  const publicMenu = read('src/views/PublicMenuPage.vue')
  assert.match(seo, /document\.title === expectedTitle/)
  assert.match(publicMenu, /descriptionMeta\?\.content === appliedSeo\?\.description/)
})

test('manifest includes complete raster icon set and Apple touch icon', () => {
  const manifest = JSON.parse(read('public/manifest.webmanifest'))
  const required = ['icon-192.png', 'icon-512.png', 'icon-192-maskable.png', 'icon-512-maskable.png']
  for (const file of required) {
    assert.ok(manifest.icons.some(({ src }) => src === `/${file}`), `${file} missing from manifest`)
    assert.ok(existsSync(join(root, 'public', file)), `${file} missing from public assets`)
  }
  assert.ok(existsSync(join(root, 'public/apple-touch-icon.png')))
  assert.match(read('index.html'), /apple-touch-icon\.png/)
})
