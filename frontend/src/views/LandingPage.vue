<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { setLocale } from '../i18n'
import { useLocalizedMeta } from '../composables/useLocalizedMeta'

const { locale, t, tm, rt } = useI18n()
useLocalizedMeta('meta.landingTitle', 'meta.landingDescription')
const values = (key) => tm(key).map((value) => Array.isArray(value) ? value.map(rt) : rt(value))
const copy = computed(() => ({
  nav: values('landing.nav'),
  trust: values('landing.trust'),
  features: values('landing.features'),
  benefits: values('landing.benefits'),
  faqs: values('landing.faqs'),
  ...Object.fromEntries(['login', 'start', 'eyebrow', 'title', 'intro', 'demo', 'featuresTitle', 'benefitsTitle', 'showcase', 'owner', 'publicMenu', 'pricing', 'pricingText', 'contact', 'faqTitle', 'finalTitle', 'finalText', 'footer'].map((key) => [key, t(`landing.${key}`)])),
}))
</script>

<template>
  <main class="landing-page">
    <nav class="landing-nav" :aria-label="t('landing.navLabel')"><RouterLink class="landing-brand" to="/">Menu<span>OS</span></RouterLink><div class="landing-nav-links"><a href="#features">{{ copy.nav[0] }}</a><a href="#benefits">{{ copy.nav[1] }}</a><a href="#pricing">{{ copy.nav[2] }}</a><a href="#faq">{{ copy.nav[3] }}</a></div><div class="landing-nav-actions"><button class="landing-language" type="button" @click="setLocale(locale === 'ar' ? 'en' : 'ar')">{{ locale === 'ar' ? 'English' : 'العربية' }}</button><RouterLink to="/login">{{ copy.login }}</RouterLink><RouterLink class="landing-button small" to="/register">{{ copy.start }}</RouterLink></div></nav>
    <header class="landing-hero"><div class="landing-hero-copy"><p class="landing-eyebrow">{{ copy.eyebrow }}</p><h1>{{ copy.title }}</h1><p class="landing-lead">{{ copy.intro }}</p><div class="landing-cta"><RouterLink class="landing-button" to="/register">{{ copy.start }}</RouterLink><RouterLink class="landing-button secondary" to="/menu/bella-pasta">{{ copy.demo }}</RouterLink></div><ul class="landing-trust"><li v-for="item in copy.trust" :key="item">✓ {{ item }}</li></ul></div><div class="landing-visual" :aria-label="t('landing.productPreview')"><div class="landing-browser"><div class="browser-bar"><i /><i /><i /></div><div class="browser-hero"><span>MenuOS</span><strong>Bella Pasta</strong><small>Handmade Italian kitchen</small></div><div class="browser-categories"><span>Fresh Pasta</span><span>Pizza</span><span>Dolci</span></div><div class="browser-items"><article><i /><b>Tagliatelle</b><small>₪42</small></article><article><i /><b>Truffle Fettuccine</b><small>₪58</small></article></div></div><span class="landing-float-card analytics"><b>+28%</b><small>{{ t('landing.menuViews') }}</small></span><span class="landing-float-card qr">▦<small>{{ t('landing.scanMenu') }}</small></span></div></header>
    <section id="features" class="landing-section"><div class="landing-heading"><p class="landing-eyebrow">MenuOS</p><h2>{{ copy.featuresTitle }}</h2></div><div class="landing-feature-grid"><article v-for="(feature, index) in copy.features" :key="feature[0]"><span>0{{ index + 1 }}</span><h3>{{ feature[0] }}</h3><p>{{ feature[1] }}</p></article></div></section>
    <section id="benefits" class="landing-section landing-benefits"><div><p class="landing-eyebrow">{{ t('landing.benefitsEyebrow') }}</p><h2>{{ copy.benefitsTitle }}</h2><ul><li v-for="benefit in copy.benefits" :key="benefit">{{ benefit }}</li></ul></div><div class="landing-phone"><div class="phone-cover" /><strong>Bella Pasta</strong><span>Fresh Pasta · Pizza · Dolci</span><article><i /><div><b>Tagliatelle al Pomodoro</b><small>Hand-cut pasta and basil</small></div><em>₪42</em></article><article><i /><div><b>Classic Tiramisu</b><small>Espresso and mascarpone</small></div><em>₪28</em></article></div></section>
    <section class="landing-section"><div class="landing-heading"><p class="landing-eyebrow">{{ t('landing.tour') }}</p><h2>{{ copy.showcase }}</h2></div><div class="landing-showcase"><article><span>{{ copy.owner }}</span><div class="mini-dashboard"><i /><i /><i /><i /><b /></div></article><article><span>{{ copy.publicMenu }}</span><div class="mini-menu"><b /><i /><i /><i /></div></article></div></section>
    <section id="pricing" class="landing-section landing-pricing"><div><p class="landing-eyebrow">{{ t('landing.pricingLabel') }}</p><h2>{{ copy.pricing }}</h2><p>{{ copy.pricingText }}</p></div><a class="landing-button secondary" href="mailto:hello@menuos.app">{{ copy.contact }}</a></section>
    <section id="faq" class="landing-section"><div class="landing-heading"><p class="landing-eyebrow">{{ t('landing.faqLabel') }}</p><h2>{{ copy.faqTitle }}</h2></div><div class="landing-faq"><details v-for="faq in copy.faqs" :key="faq[0]"><summary>{{ faq[0] }}</summary><p>{{ faq[1] }}</p></details></div></section>
    <section class="landing-final"><h2>{{ copy.finalTitle }}</h2><p>{{ copy.finalText }}</p><RouterLink class="landing-button light" to="/register">{{ copy.start }}</RouterLink></section>
    <footer class="landing-footer"><RouterLink class="landing-brand" to="/">Menu<span>OS</span></RouterLink><p>{{ copy.footer }}</p><div><RouterLink to="/login">{{ copy.login }}</RouterLink><a href="mailto:hello@menuos.app">hello@menuos.app</a></div></footer>
  </main>
</template>
