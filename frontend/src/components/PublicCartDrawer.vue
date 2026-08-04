<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '../stores/cart'
import { buildWhatsAppOrder, normalizeWhatsAppNumber, whatsappOrderUrl } from '../utils/whatsappOrder'

const props = defineProps({
  open: { type: Boolean, required: true },
  restaurant: { type: Object, required: true },
  formatMoney: { type: Function, required: true },
  themeStyle: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['close', 'whatsapp'])
const cart = useCartStore()
const { locale, t } = useI18n()
const closeButton = ref(null)
const whatsappNumber = computed(() => normalizeWhatsAppNumber(props.restaurant.whatsapp))
const whatsappError = computed(() => !props.restaurant.whatsapp ? t('cart.missingWhatsApp') : (!whatsappNumber.value ? t('cart.invalidWhatsApp') : ''))

function sendOrder() {
  if (!cart.items.length || !whatsappNumber.value) return
  const message = buildWhatsAppOrder({
    restaurantName: props.restaurant.name,
    lines: cart.items,
    total: cart.totalPrice,
    note: cart.note,
    formatMoney: props.formatMoney,
  })
  emit('whatsapp')
  window.open(whatsappOrderUrl(props.restaurant.whatsapp, message), '_blank', 'noopener,noreferrer')
}

function onKeydown(event) {
  if (event.key === 'Escape' && props.open) emit('close')
}

watch(() => props.open, async (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
  if (isOpen) { await nextTick(); closeButton.value?.focus() }
})
window.addEventListener('keydown', onKeydown)
onBeforeUnmount(() => { window.removeEventListener('keydown', onKeydown); document.body.style.overflow = '' })
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="cart-overlay" @click.self="$emit('close')">
      <aside class="cart-drawer" :dir="locale === 'ar' ? 'rtl' : 'ltr'" :style="themeStyle" role="dialog" aria-modal="true" aria-labelledby="cart-title">
        <header class="cart-header"><div><span class="public-menu-eyebrow">{{ t('cart.yourOrder') }}</span><h2 id="cart-title">{{ t('cart.title') }} ({{ cart.totalQuantity }})</h2></div><button ref="closeButton" class="cart-close" type="button" :aria-label="t('common.close')" @click="$emit('close')">×</button></header>
        <div v-if="!cart.items.length" class="cart-empty"><h3>{{ t('cart.empty') }}</h3><p>{{ t('cart.emptyHelp') }}</p></div>
        <template v-else>
          <ul class="cart-lines">
            <li v-for="line in cart.items" :key="line.item.id" class="cart-line">
              <div><strong>{{ line.item.name }}</strong><small>{{ t('cart.each', { price: formatMoney(line.item.price) }) }}</small></div>
              <div class="cart-line-total">{{ formatMoney(Number(line.item.price) * line.quantity) }}</div>
              <div class="cart-quantity">
                <button type="button" :aria-label="t('cart.decrease', { name: line.item.name })" @click="cart.decrease(line.item.id)">−</button><span>{{ line.quantity }}</span><button type="button" :aria-label="t('cart.increase', { name: line.item.name })" @click="cart.increase(line.item.id)">+</button>
              </div>
              <button class="cart-remove" type="button" :aria-label="t('cart.remove', { name: line.item.name })" @click="cart.remove(line.item.id)">{{ t('common.remove') }}</button>
            </li>
          </ul>
          <label class="cart-note">{{ t('cart.note') }}<textarea :value="cart.note" maxlength="500" rows="3" :placeholder="t('cart.notePlaceholder')" @input="cart.updateNote($event.target.value)"></textarea></label>
          <div class="cart-summary"><span>{{ t('cart.total', { count: cart.totalQuantity }) }}</span><strong>{{ formatMoney(cart.totalPrice) }}</strong></div>
          <p v-if="whatsappError" class="error" role="status">{{ whatsappError }}</p>
          <button class="cart-whatsapp" type="button" :disabled="!cart.items.length || !whatsappNumber" @click="sendOrder">{{ t('cart.send') }}</button>
          <button class="cart-clear" type="button" @click="cart.clear">{{ t('cart.clear') }}</button>
        </template>
      </aside>
    </div>
  </Teleport>
</template>
