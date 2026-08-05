<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import BaseAlert from '../components/ui/BaseAlert.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseInput from '../components/ui/BaseInput.vue'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import { useI18n } from 'vue-i18n'
import { useLocalizedMeta } from '../composables/useLocalizedMeta'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const error = ref('')
const loading = ref(false)
const { t } = useI18n()
useLocalizedMeta('meta.login')

async function submit() {
  if (loading.value) return
  error.value = ''
  loading.value = true
  try {
    await auth.login(form)
    await router.push({ name: auth.hasRestaurant ? 'dashboard' : 'restaurant-setup' })
  } catch (requestError) {
    error.value = apiError(requestError, t('auth.loginError'))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <LanguageSwitcher />
    <BaseCard>
      <form class="auth-card" @submit.prevent="submit">
        <div><p class="eyebrow">{{ $t('auth.brand') }}</p><h1>{{ $t('auth.welcome') }}</h1><p>{{ $t('auth.loginIntro') }}</p></div>
        <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
        <BaseInput v-model="form.email" :label="$t('auth.email')" type="email" autocomplete="email" required autofocus />
        <BaseInput v-model="form.password" :label="$t('auth.password')" type="password" autocomplete="current-password" required />
        <BaseButton type="submit" :loading="loading">{{ $t('auth.signIn') }}</BaseButton>
        <p class="form-footer">{{ $t('auth.new') }} <RouterLink to="/register">{{ $t('auth.createAccount') }}</RouterLink></p>
      </form>
    </BaseCard>
  </main>
</template>
