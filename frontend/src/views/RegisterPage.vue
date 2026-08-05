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
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const loading = ref(false)
const { t } = useI18n()
useLocalizedMeta('meta.register')

async function submit() {
  if (loading.value) return
  error.value = ''
  loading.value = true
  try {
    await auth.register(form)
    await router.push({ name: 'restaurant-setup' })
  } catch (requestError) {
    error.value = apiError(requestError, t('auth.registerError'))
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
        <div><p class="eyebrow">{{ $t('auth.brand') }}</p><h1>{{ $t('auth.registerTitle') }}</h1><p>{{ $t('auth.registerIntro') }}</p></div>
        <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
        <BaseInput v-model="form.name" :label="$t('auth.name')" autocomplete="name" maxlength="255" required autofocus />
        <BaseInput v-model="form.email" :label="$t('auth.email')" type="email" autocomplete="email" required />
        <BaseInput v-model="form.password" :label="$t('auth.password')" type="password" autocomplete="new-password" minlength="8" :hint="$t('auth.passwordHint')" required />
        <BaseInput v-model="form.password_confirmation" :label="$t('auth.confirmPassword')" type="password" autocomplete="new-password" required />
        <BaseButton type="submit" :loading="loading">{{ $t('auth.createAccount') }}</BaseButton>
        <p class="form-footer">{{ $t('auth.already') }} <RouterLink to="/login">{{ $t('auth.signIn') }}</RouterLink></p>
      </form>
    </BaseCard>
  </main>
</template>
