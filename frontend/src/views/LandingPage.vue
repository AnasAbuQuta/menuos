<script setup>
import { computed, onBeforeUnmount, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { setLocale } from '../i18n'

const { locale } = useI18n()
const content = {
  en: {
    nav: ['Features', 'Benefits', 'Pricing', 'FAQ'], login: 'Owner login', start: 'Create your menu', eyebrow: 'The restaurant menu, reimagined',
    title: 'A beautiful digital menu your restaurant can launch today.', intro: 'Build a bilingual, branded menu, share it with one QR code, and understand what guests love—without complicated hardware.',
    demo: 'View Bella Pasta demo', trust: ['Arabic + English', 'No app required', 'Built for mobile'], featuresTitle: 'Everything needed to serve a modern menu',
    features: [['Bilingual by design', 'Publish Arabic and English content with true RTL and LTR layouts.'], ['Your restaurant, your brand', 'Choose themes, colors, logo, cover, and opening hours.'], ['QR-ready sharing', 'Generate a restaurant QR code and share one fast public URL.'], ['Useful analytics', 'See anonymous views, visitors, clicks, and popular dishes.'], ['WhatsApp ordering', 'Guests can build a cart and send their selection through WhatsApp.'], ['Simple management', 'Organize categories and dishes from a focused owner workspace.']],
    benefitsTitle: 'Less friction for owners. Better browsing for guests.', benefits: ['Update your menu without reprinting.', 'Show prices and availability instantly.', 'Give every dish a clear, searchable home.', 'Learn which categories and items attract attention.'],
    showcase: 'One system. Two polished experiences.', owner: 'Owner workspace', publicMenu: 'Guest menu', pricing: 'Simple pricing is coming', pricingText: 'MenuOS is preparing its commercial plans. Join now to shape the right package for your restaurant.', contact: 'Talk to us',
    faqTitle: 'Frequently asked questions', faqs: [['Do guests need an app?', 'No. Every menu opens in the browser from a link or QR code.'], ['Can I use Arabic and English?', 'Yes. Restaurant, category, and item content supports both languages.'], ['Does MenuOS include a POS?', 'No. MenuOS stays focused on the digital menu and guest experience.'], ['Can I change my theme later?', 'Yes. Branding and menu content can be updated whenever you need.']],
    finalTitle: 'Ready to give your menu a better home?', finalText: 'Create your restaurant workspace and publish a polished menu in minutes.', footer: 'Digital menus made thoughtfully for restaurants.',
  },
  ar: {
    nav: ['المزايا', 'الفوائد', 'الأسعار', 'الأسئلة'], login: 'دخول المالك', start: 'أنشئ قائمتك', eyebrow: 'قائمة المطعم بأسلوب جديد',
    title: 'قائمة رقمية جميلة يمكن لمطعمك إطلاقها اليوم.', intro: 'أنشئ قائمة ثنائية اللغة بهوية مطعمك، وشاركها برمز QR واحد، وتعرّف على ما يحبه ضيوفك دون أجهزة معقدة.',
    demo: 'شاهد نموذج بيلا باستا', trust: ['العربية والإنجليزية', 'لا يحتاج إلى تطبيق', 'مصمم للجوال'], featuresTitle: 'كل ما تحتاجه لتقديم قائمة عصرية',
    features: [['ثنائي اللغة من الأساس', 'انشر المحتوى بالعربية والإنجليزية مع دعم كامل لاتجاهي RTL وLTR.'], ['هوية مطعمك', 'اختر القالب والألوان والشعار والغلاف وساعات العمل.'], ['مشاركة عبر QR', 'أنشئ رمز QR للمطعم وشارك رابطاً عاماً سريعاً.'], ['تحليلات مفيدة', 'تابع المشاهدات والزوار والنقرات والأطباق الشائعة دون بيانات شخصية.'], ['الطلب عبر واتساب', 'يمكن للضيف تجهيز سلته وإرسال اختياره عبر واتساب.'], ['إدارة بسيطة', 'نظّم التصنيفات والأطباق من مساحة عمل واضحة للمالك.']],
    benefitsTitle: 'وقت أقل في الإدارة وتجربة أفضل للضيف.', benefits: ['حدّث القائمة دون إعادة الطباعة.', 'اعرض الأسعار والتوفر فوراً.', 'اجعل كل طبق واضحاً وسهل البحث.', 'تعرّف على التصنيفات والأطباق الأكثر جذباً.'],
    showcase: 'نظام واحد وتجربتان متكاملتان.', owner: 'مساحة المالك', publicMenu: 'قائمة الضيف', pricing: 'خطط بسيطة قريباً', pricingText: 'يستعد MenuOS لإطلاق خططه التجارية. انضم الآن وساعدنا في بناء الباقة الأنسب لمطعمك.', contact: 'تواصل معنا',
    faqTitle: 'الأسئلة الشائعة', faqs: [['هل يحتاج الضيف إلى تطبيق؟', 'لا. تُفتح القائمة في المتصفح مباشرة من الرابط أو رمز QR.'], ['هل يمكنني استخدام العربية والإنجليزية؟', 'نعم. يدعم المطعم والتصنيفات والأطباق اللغتين.'], ['هل يتضمن MenuOS نظام نقاط بيع؟', 'لا. يركز MenuOS على القائمة الرقمية وتجربة الضيف.'], ['هل يمكن تغيير القالب لاحقاً؟', 'نعم. يمكنك تحديث الهوية والمحتوى في أي وقت.']],
    finalTitle: 'هل أنت مستعد لمنح قائمتك مكاناً أفضل؟', finalText: 'أنشئ مساحة مطعمك وانشر قائمة احترافية خلال دقائق.', footer: 'قوائم رقمية صُممت بعناية للمطاعم.',
  },
}
const copy = computed(() => content[locale.value])
const original = { title: document.title, description: document.querySelector('meta[name="description"]')?.content }
const stopMetadataWatch = watch(locale, (value) => {
  document.title = value === 'ar' ? 'MenuOS | قوائم رقمية للمطاعم' : 'MenuOS | Digital menus for restaurants'
  document.querySelector('meta[name="description"]')?.setAttribute(
    'content',
    value === 'ar'
      ? 'أنشئ قائمة مطعم رقمية ثنائية اللغة بهويتك مع QR وتحليلات وواتساب.'
      : 'Create a bilingual branded restaurant menu with QR sharing, analytics, and WhatsApp.',
  )
}, { immediate: true })
onBeforeUnmount(() => {
  stopMetadataWatch()
  document.title = original.title
  document.querySelector('meta[name="description"]')?.setAttribute('content', original.description || '')
})
</script>

<template>
  <main class="landing-page">
    <nav class="landing-nav" aria-label="Main navigation"><RouterLink class="landing-brand" to="/">Menu<span>OS</span></RouterLink><div class="landing-nav-links"><a href="#features">{{ copy.nav[0] }}</a><a href="#benefits">{{ copy.nav[1] }}</a><a href="#pricing">{{ copy.nav[2] }}</a><a href="#faq">{{ copy.nav[3] }}</a></div><div class="landing-nav-actions"><button class="landing-language" type="button" @click="setLocale(locale === 'ar' ? 'en' : 'ar')">{{ locale === 'ar' ? 'English' : 'العربية' }}</button><RouterLink to="/login">{{ copy.login }}</RouterLink><RouterLink class="landing-button small" to="/register">{{ copy.start }}</RouterLink></div></nav>
    <header class="landing-hero"><div class="landing-hero-copy"><p class="landing-eyebrow">{{ copy.eyebrow }}</p><h1>{{ copy.title }}</h1><p class="landing-lead">{{ copy.intro }}</p><div class="landing-cta"><RouterLink class="landing-button" to="/register">{{ copy.start }}</RouterLink><RouterLink class="landing-button secondary" to="/menu/bella-pasta">{{ copy.demo }}</RouterLink></div><ul class="landing-trust"><li v-for="item in copy.trust" :key="item">✓ {{ item }}</li></ul></div><div class="landing-visual" aria-label="MenuOS product preview"><div class="landing-browser"><div class="browser-bar"><i /><i /><i /></div><div class="browser-hero"><span>MenuOS</span><strong>Bella Pasta</strong><small>Handmade Italian kitchen</small></div><div class="browser-categories"><span>Fresh Pasta</span><span>Pizza</span><span>Dolci</span></div><div class="browser-items"><article><i /><b>Tagliatelle</b><small>₪42</small></article><article><i /><b>Truffle Fettuccine</b><small>₪58</small></article></div></div><span class="landing-float-card analytics"><b>+28%</b><small>menu views</small></span><span class="landing-float-card qr">▦<small>Scan menu</small></span></div></header>
    <section id="features" class="landing-section"><div class="landing-heading"><p class="landing-eyebrow">MenuOS</p><h2>{{ copy.featuresTitle }}</h2></div><div class="landing-feature-grid"><article v-for="(feature, index) in copy.features" :key="feature[0]"><span>0{{ index + 1 }}</span><h3>{{ feature[0] }}</h3><p>{{ feature[1] }}</p></article></div></section>
    <section id="benefits" class="landing-section landing-benefits"><div><p class="landing-eyebrow">Built for service</p><h2>{{ copy.benefitsTitle }}</h2><ul><li v-for="benefit in copy.benefits" :key="benefit">{{ benefit }}</li></ul></div><div class="landing-phone"><div class="phone-cover" /><strong>Bella Pasta</strong><span>Fresh Pasta · Pizza · Dolci</span><article><i /><div><b>Tagliatelle al Pomodoro</b><small>Hand-cut pasta and basil</small></div><em>₪42</em></article><article><i /><div><b>Classic Tiramisu</b><small>Espresso and mascarpone</small></div><em>₪28</em></article></div></section>
    <section class="landing-section"><div class="landing-heading"><p class="landing-eyebrow">Product tour</p><h2>{{ copy.showcase }}</h2></div><div class="landing-showcase"><article><span>{{ copy.owner }}</span><div class="mini-dashboard"><i /><i /><i /><i /><b /></div></article><article><span>{{ copy.publicMenu }}</span><div class="mini-menu"><b /><i /><i /><i /></div></article></div></section>
    <section id="pricing" class="landing-section landing-pricing"><div><p class="landing-eyebrow">Pricing</p><h2>{{ copy.pricing }}</h2><p>{{ copy.pricingText }}</p></div><a class="landing-button secondary" href="mailto:hello@menuos.app">{{ copy.contact }}</a></section>
    <section id="faq" class="landing-section"><div class="landing-heading"><p class="landing-eyebrow">FAQ</p><h2>{{ copy.faqTitle }}</h2></div><div class="landing-faq"><details v-for="faq in copy.faqs" :key="faq[0]"><summary>{{ faq[0] }}</summary><p>{{ faq[1] }}</p></details></div></section>
    <section class="landing-final"><h2>{{ copy.finalTitle }}</h2><p>{{ copy.finalText }}</p><RouterLink class="landing-button light" to="/register">{{ copy.start }}</RouterLink></section>
    <footer class="landing-footer"><RouterLink class="landing-brand" to="/">Menu<span>OS</span></RouterLink><p>{{ copy.footer }}</p><div><RouterLink to="/login">{{ copy.login }}</RouterLink><a href="mailto:hello@menuos.app">hello@menuos.app</a></div></footer>
  </main>
</template>
