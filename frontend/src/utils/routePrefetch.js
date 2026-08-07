export async function prefetchRouteComponent(router, routeName) {
  const component = router.resolve({ name: routeName }).matched.at(-1)?.components?.default
  if (typeof component !== 'function') return

  try {
    await component()
  } catch {
    // Prefetching is optional and must never interrupt navigation.
  }
}
