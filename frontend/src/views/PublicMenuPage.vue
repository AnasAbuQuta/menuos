<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getPublicMenu } from '../services/publicMenu'
import PublicMenuItemCard from '../components/PublicMenuItemCard.vue'
import PublicCartDrawer from '../components/PublicCartDrawer.vue'
import { useCartStore } from '../stores/cart'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseEmptyState from '../components/ui/BaseEmptyState.vue'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import { setLocale } from '../i18n'
import { useI18n } from 'vue-i18n'
import { themeVariables } from '../theme/restaurantThemes'

const route = useRoute()
const router = useRouter()
const menu = ref(null)
const loading = ref(true)
const notFound = ref(false)
const error = ref('')
const search = ref('')
const cartOpen = ref(false)
const cart = useCartStore()
const originalTitle = document.title
const descriptionMeta = document.querySelector('meta[name="description"]')
const originalDescription = descriptionMeta?.content || ''
const { t, locale } = useI18n()
const requestedLanguage = computed(() => ['ar', 'en'].includes(route.query.lang) ? route.query.lang : locale.value)

const menuTheme = computed(() => themeVariables(menu.value?.theme_key, /^#[0-9a-f]{6}$/i.test(menu.value?.primary_color || '') ? menu.value.primary_color : null))
const categories = computed(() => {
  const term = search.value.trim().toLocaleLowerCase()
  if (!term) return menu.value?.categories || []
  return (menu.value?.categories || []).map((category) => ({
    ...category,
    menu_items: category.menu_items.filter((item) => `${item.name} ${item.description || ''}`.toLocaleLowerCase().includes(term)),
  })).filter((category) => category.menu_items.length)
})
const featuredItems = computed(() => (menu.value?.categories || []).flatMap((category) => category.menu_items).filter((item) => item.is_featured))
const whatsappUrl = computed(() => menu.value?.whatsapp ? `https://wa.me/${menu.value.whatsapp.replace(/\D/g, '')}` : null)

function money(value) {
  return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-PS' : 'en-US', { style: 'currency', currency: menu.value?.currency || 'ILS' }).format(Number(value))
}

function scrollToCategory(id) {
  document.getElementById(`category-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function changeLanguage(language) {
  await router.replace({ query: { ...route.query, lang: language } })
}

function setMetadata() {
  document.title = `${menu.value.name} | MenuOS`
  if (descriptionMeta) descriptionMeta.content = menu.value.description || t('forms.publicMeta', { name: menu.value.name })
}

async function loadMenu() {
  loading.value = true
  notFound.value = false
  error.value = ''
  try {
    menu.value = await getPublicMenu(route.params.slug, requestedLanguage.value)
    setLocale(menu.value.language)
    cart.initialize(menu.value.slug, menu.value.categories.flatMap((category) => category.menu_items))
    await nextTick()
    setMetadata()
  } catch (requestError) {
    if (requestError.response?.status === 404) notFound.value = true
    else error.value = t('public.loadErrorHelp')
  } finally {
    loading.value = false
  }
}

onMounted(loadMenu)
watch(() => route.query.lang, loadMenu)
onBeforeUnmount(() => {
  document.title = originalTitle
  if (descriptionMeta) descriptionMeta.content = originalDescription
})
</script>

<template>
  <main class="public-menu-page" :style="menuTheme">
    <LanguageSwitcher @change="changeLanguage" />
    <section v-if="loading" class="public-menu-loading"><BaseLoading :rows="6" :label="$t('public.loading')" /></section>
    <section v-else-if="notFound" class="public-menu-state">
      <span class="public-menu-code">404</span><h1>{{ $t('public.notFound') }}</h1><p>{{ $t('public.notFoundHelp') }}</p>
    </section>
    <section v-else-if="error" class="public-menu-state" role="alert">
      <h1>{{ $t('public.loadError') }}</h1><p>{{ error }}</p><button class="public-menu-button" @click="loadMenu">{{ $t('common.retry') }}</button>
    </section>

    <template v-else-if="menu">
      <header class="public-menu-hero" :class="{ 'has-cover': menu.cover_image_url }" :style="menu.cover_image_url ? { backgroundImage: `linear-gradient(180deg, rgb(10 24 19 / 35%), rgb(10 24 19 / 82%)), url(${menu.cover_image_url})` } : {}">
        <div class="public-menu-hero-content">
          <img v-if="menu.logo_url" class="public-menu-logo" :src="menu.logo_url" :alt="`${menu.name} logo`" width="104" height="104">
          <span v-else class="public-menu-logo-placeholder" aria-hidden="true">{{ menu.name.charAt(0) }}</span>
          <div><p class="public-menu-eyebrow">{{ $t('public.menu') }}</p><h1>{{ menu.name }}</h1><p v-if="menu.description" class="public-menu-description">{{ menu.description }}</p></div>
          <span v-if="menu.is_open_now !== null" class="public-menu-status" :class="{ closed: !menu.is_open_now }">{{ menu.is_open_now ? $t('public.open') : $t('public.closed') }}</span>
        </div>
      </header>

      <section class="public-menu-contact" aria-label="Restaurant details">
        <span v-if="menu.address">{{ menu.address }}</span>
        <a v-if="menu.phone" :href="`tel:${menu.phone}`">{{ $t('public.call', { phone: menu.phone }) }}</a>
        <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener noreferrer">{{ $t('public.whatsapp') }}</a>
      </section>

      <div class="public-menu-container">
        <label class="public-menu-search"><span class="sr-only">{{ $t('public.search') }}</span><input v-model="search" type="search" :placeholder="$t('public.search')"></label>

        <nav v-if="menu.categories.length" class="public-menu-nav" aria-label="Menu categories">
          <button v-for="category in menu.categories" :key="category.id" @click="scrollToCategory(category.id)">{{ category.name }}</button>
        </nav>

        <section v-if="featuredItems.length && !search" class="public-menu-section">
          <h2>{{ $t('public.featured') }}</h2><div class="public-menu-grid"><PublicMenuItemCard v-for="item in featuredItems" :key="`featured-${item.id}`" :item="item" :formatted-price="money(item.price)" @add="cart.add" /></div>
        </section>

        <BaseEmptyState v-if="!menu.categories.length" :title="$t('public.coming')" :message="$t('public.comingHelp')" />
        <BaseEmptyState v-else-if="!categories.length" :title="$t('public.noMatches')" :message="$t('public.noMatchesHelp')" />
        <section v-for="category in categories" :id="`category-${category.id}`" :key="category.id" class="public-menu-section public-menu-category">
          <h2>{{ category.name }}</h2><div class="public-menu-grid"><PublicMenuItemCard v-for="item in category.menu_items" :key="item.id" :item="item" :formatted-price="money(item.price)" @add="cart.add" /></div>
        </section>
      </div>
      <footer class="public-menu-footer">{{ $t('public.powered') }}</footer>
      <button class="public-cart-floating" type="button" :aria-label="$t('cart.open')" @click="cartOpen = true"><span>{{ $t('cart.title') }}</span><strong>{{ cart.totalQuantity }}</strong><span>{{ money(cart.totalPrice) }}</span></button>
      <PublicCartDrawer :open="cartOpen" :restaurant="menu" :format-money="money" @close="cartOpen = false" />
    </template>
  </main>
</template>
