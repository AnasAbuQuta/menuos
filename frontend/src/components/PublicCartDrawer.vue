<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useCartStore } from '../stores/cart'
import { buildWhatsAppOrder, normalizeWhatsAppNumber, whatsappOrderUrl } from '../utils/whatsappOrder'

const props = defineProps({
  open: { type: Boolean, required: true },
  restaurant: { type: Object, required: true },
  formatMoney: { type: Function, required: true },
})
const emit = defineEmits(['close', 'whatsapp'])
const cart = useCartStore()
const closeButton = ref(null)
const whatsappNumber = computed(() => normalizeWhatsAppNumber(props.restaurant.whatsapp))
const whatsappError = computed(() => !props.restaurant.whatsapp ? 'This restaurant has not added a WhatsApp number.' : (!whatsappNumber.value ? 'The restaurant WhatsApp number is invalid.' : ''))

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
      <aside class="cart-drawer" role="dialog" aria-modal="true" aria-labelledby="cart-title">
        <header class="cart-header"><div><span class="public-menu-eyebrow">Your order</span><h2 id="cart-title">Cart ({{ cart.totalQuantity }})</h2></div><button ref="closeButton" class="cart-close" type="button" aria-label="Close cart" @click="$emit('close')">×</button></header>
        <div v-if="!cart.items.length" class="cart-empty"><h3>Your cart is empty</h3><p>Add an item from the menu to begin.</p></div>
        <template v-else>
          <ul class="cart-lines">
            <li v-for="line in cart.items" :key="line.item.id" class="cart-line">
              <div><strong>{{ line.item.name }}</strong><small>{{ formatMoney(line.item.price) }} each</small></div>
              <div class="cart-line-total">{{ formatMoney(Number(line.item.price) * line.quantity) }}</div>
              <div class="cart-quantity">
                <button type="button" :aria-label="`Decrease ${line.item.name} quantity`" @click="cart.decrease(line.item.id)">−</button><span :aria-label="`${line.quantity} items`">{{ line.quantity }}</span><button type="button" :aria-label="`Increase ${line.item.name} quantity`" @click="cart.increase(line.item.id)">+</button>
              </div>
              <button class="cart-remove" type="button" :aria-label="`Remove ${line.item.name} from cart`" @click="cart.remove(line.item.id)">Remove</button>
            </li>
          </ul>
          <label class="cart-note">General note<textarea :value="cart.note" maxlength="500" rows="3" placeholder="Example: no onions" @input="cart.updateNote($event.target.value)"></textarea></label>
          <div class="cart-summary"><span>Total ({{ cart.totalQuantity }} items)</span><strong>{{ formatMoney(cart.totalPrice) }}</strong></div>
          <p v-if="whatsappError" class="error" role="status">{{ whatsappError }}</p>
          <button class="cart-whatsapp" type="button" :disabled="!cart.items.length || !whatsappNumber" @click="sendOrder">Send order via WhatsApp</button>
          <button class="cart-clear" type="button" @click="cart.clear">Clear cart</button>
        </template>
      </aside>
    </div>
  </Teleport>
</template>
