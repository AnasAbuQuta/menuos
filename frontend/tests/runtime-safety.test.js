import test from 'node:test'
import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { prefetchRouteComponent } from '../src/utils/routePrefetch.js'

test('route prefetch invokes lazy components and ignores resolved component objects', async () => {
  let calls = 0
  const lazyRouter = {
    resolve: () => ({ matched: [{ components: { default: async () => { calls++ } } }] }),
  }
  const resolvedRouter = {
    resolve: () => ({ matched: [{ components: { default: { name: 'ResolvedPage' } } }] }),
  }

  await prefetchRouteComponent(lazyRouter, 'dashboard')
  await prefetchRouteComponent(resolvedRouter, 'dashboard')

  assert.equal(calls, 1)
})

test('route prefetch treats import failures as non-blocking optimization failures', async () => {
  const router = {
    resolve: () => ({ matched: [{ components: { default: async () => { throw new Error('offline') } } }] }),
  }

  await assert.doesNotReject(() => prefetchRouteComponent(router, 'dashboard'))
})

test('service worker only caches same-origin HTTP requests', async () => {
  const source = await readFile(new URL('../public/sw.js', import.meta.url), 'utf8')

  assert.match(source, /\['http:', 'https:'\]\.includes\(url\.protocol\)/)
  assert.match(source, /url\.origin !== self\.location\.origin/)
})
