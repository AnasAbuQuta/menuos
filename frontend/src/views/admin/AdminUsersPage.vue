<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { getAdminUsers, updateAdminUserStatus } from '../../services/admin'
import { useToastStore } from '../../stores/toast'
import BaseAlert from '../../components/ui/BaseAlert.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseConfirmDialog from '../../components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '../../components/ui/BaseEmptyState.vue'
import BaseLoading from '../../components/ui/BaseLoading.vue'

const { t } = useI18n()
const toast = useToastStore()
const loading = ref(true)
const error = ref('')
const users = ref([])
const meta = ref({})
const filters = reactive({ search: '', status: '', sort: 'newest', page: 1 })
const selected = ref(null)
const saving = ref(false)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await getAdminUsers(filters)
    users.value = data.users
    meta.value = data.meta
  } catch { error.value = 'admin.loadError' } finally { loading.value = false }
}

async function confirmStatus() {
  saving.value = true
  try {
    const status = selected.value.account_status === 'active' ? 'disabled' : 'active'
    const updated = await updateAdminUserStatus(selected.value.id, status)
    users.value = users.value.map((user) => user.id === updated.id ? updated : user)
    toast.success(t(status === 'active' ? 'admin.userReactivated' : 'admin.userDisabled'))
    selected.value = null
  } catch { toast.error(t('admin.updateError')) } finally { saving.value = false }
}

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="page-heading"><div><h1>{{ $t('admin.users') }}</h1><p>{{ $t('admin.usersIntro') }}</p></div></header>
    <form class="admin-filters" @submit.prevent="filters.page = 1; load()">
      <label>{{ $t('admin.search') }}<input v-model="filters.search" type="search"></label>
      <label>{{ $t('admin.status') }}<select v-model="filters.status"><option value="">{{ $t('admin.all') }}</option><option value="active">{{ $t('admin.active') }}</option><option value="disabled">{{ $t('admin.disabled') }}</option></select></label>
      <label>{{ $t('admin.sort') }}<select v-model="filters.sort"><option value="newest">{{ $t('admin.newest') }}</option><option value="oldest">{{ $t('admin.oldest') }}</option><option value="name">{{ $t('admin.name') }}</option></select></label>
      <BaseButton type="submit">{{ $t('admin.apply') }}</BaseButton>
    </form>
    <BaseAlert v-if="error" type="error">{{ $t(error) }}</BaseAlert>
    <BaseLoading v-if="loading" :rows="5" />
    <BaseEmptyState v-else-if="!users.length" :title="$t('admin.noUsers')" :message="$t('admin.noUsersHelp')" />
    <div v-else class="admin-table-wrap"><table><caption class="sr-only">{{ $t('admin.usersTableCaption') }}</caption><thead><tr><th>{{ $t('admin.name') }}</th><th>{{ $t('admin.email') }}</th><th>{{ $t('admin.status') }}</th><th>{{ $t('admin.restaurant') }}</th><th>{{ $t('admin.created') }}</th><th>{{ $t('admin.actions') }}</th></tr></thead><tbody><tr v-for="user in users" :key="user.id"><td>{{ user.name }} <BaseBadge v-if="user.is_super_admin" variant="info">{{ $t('admin.superAdmin') }}</BaseBadge></td><td>{{ user.email }}</td><td><BaseBadge :variant="user.account_status === 'active' ? 'success' : 'danger'">{{ $t(`admin.${user.account_status}`) }}</BaseBadge></td><td>{{ user.restaurant?.name || '—' }}</td><td>{{ new Date(user.created_at).toLocaleDateString() }}</td><td class="table-actions"><RouterLink class="ui-button ui-button-ghost" :to="{ name: 'admin-user-detail', params: { id: user.id } }">{{ $t('admin.viewDetails') }}</RouterLink><BaseButton v-if="!user.is_super_admin" :variant="user.account_status === 'active' ? 'danger' : 'primary'" @click="selected = user">{{ $t(user.account_status === 'active' ? 'admin.disable' : 'admin.reactivate') }}</BaseButton></td></tr></tbody></table></div>
    <div v-if="meta.last_page > 1" class="pagination"><BaseButton variant="ghost" :disabled="meta.current_page <= 1" @click="filters.page--; load()">{{ $t('admin.previous') }}</BaseButton><span>{{ meta.current_page }} / {{ meta.last_page }}</span><BaseButton variant="ghost" :disabled="meta.current_page >= meta.last_page" @click="filters.page++; load()">{{ $t('admin.next') }}</BaseButton></div>
    <BaseConfirmDialog :open="Boolean(selected)" :loading="saving" danger :message="$t(selected?.account_status === 'active' ? 'admin.disableUserConfirm' : 'admin.reactivateUserConfirm')" @confirm="confirmStatus" @cancel="selected = null" />
  </section>
</template>
