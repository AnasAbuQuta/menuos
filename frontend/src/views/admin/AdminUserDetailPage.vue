<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getAdminUser, updateAdminUserStatus } from '../../services/admin'
import { useToastStore } from '../../stores/toast'
import BaseAlert from '../../components/ui/BaseAlert.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseConfirmDialog from '../../components/ui/BaseConfirmDialog.vue'
import BaseLoading from '../../components/ui/BaseLoading.vue'

const route = useRoute()
const { t } = useI18n()
const toast = useToastStore()
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const user = ref(null)
const confirming = ref(false)

async function load() { try { user.value = await getAdminUser(route.params.id) } catch { error.value = 'admin.loadError' } finally { loading.value = false } }
async function changeStatus() { saving.value = true; try { const status = user.value.account_status === 'active' ? 'disabled' : 'active'; user.value = await updateAdminUserStatus(user.value.id, status); toast.success(t(status === 'active' ? 'admin.userReactivated' : 'admin.userDisabled')); confirming.value = false } catch { toast.error(t('admin.updateError')) } finally { saving.value = false } }
onMounted(load)
</script>

<template><section class="admin-page"><RouterLink class="admin-back" :to="{ name: 'admin-users' }">← {{ $t('admin.backToUsers') }}</RouterLink><BaseAlert v-if="error" type="error">{{ $t(error) }}</BaseAlert><BaseLoading v-if="loading" :rows="4" /><template v-else-if="user"><header class="page-heading"><div><h1>{{ user.name }}</h1><p>{{ user.email }}</p></div><BaseBadge :variant="user.account_status === 'active' ? 'success' : 'danger'">{{ $t(`admin.${user.account_status}`) }}</BaseBadge></header><div class="admin-detail-grid"><BaseCard><h2>{{ $t('admin.accountDetails') }}</h2><dl class="admin-details"><div><dt>{{ $t('admin.created') }}</dt><dd>{{ new Date(user.created_at).toLocaleString() }}</dd></div><div><dt>{{ $t('admin.updated') }}</dt><dd>{{ new Date(user.updated_at).toLocaleString() }}</dd></div><div><dt>{{ $t('admin.role') }}</dt><dd>{{ $t(user.is_super_admin ? 'admin.superAdmin' : 'admin.owner') }}</dd></div></dl><BaseButton v-if="!user.is_super_admin" :variant="user.account_status === 'active' ? 'danger' : 'primary'" @click="confirming = true">{{ $t(user.account_status === 'active' ? 'admin.disable' : 'admin.reactivate') }}</BaseButton></BaseCard><BaseCard><h2>{{ $t('admin.restaurant') }}</h2><div v-if="user.restaurant" class="admin-details"><strong>{{ user.restaurant.name_ar || user.restaurant.name_en || user.restaurant.name }}</strong><span>{{ user.restaurant.slug }}</span><div class="table-actions"><RouterLink class="ui-button ui-button-ghost" :to="{ name: 'admin-restaurant-detail', params: { id: user.restaurant.id } }">{{ $t('admin.viewRestaurant') }}</RouterLink><a class="ui-button ui-button-ghost" :href="`/menu/${user.restaurant.slug}`" target="_blank" rel="noopener">{{ $t('admin.openPublicMenu') }}</a></div></div><p v-else>{{ $t('admin.noRestaurantForUser') }}</p></BaseCard></div><BaseConfirmDialog :open="confirming" :loading="saving" danger :message="$t(user.account_status === 'active' ? 'admin.disableUserConfirm' : 'admin.reactivateUserConfirm')" @confirm="changeStatus" @cancel="confirming = false" /></template></section></template>
