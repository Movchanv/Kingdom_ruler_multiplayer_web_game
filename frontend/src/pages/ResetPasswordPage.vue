<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { authService } from '@/services/auth.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const token = computed(() => String(route.query.token ?? ''))
const email = computed(() => String(route.query.email ?? ''))

const form = reactive({
  password: '',
  password_confirmation: '',
})
const submitting = ref(false)

async function submit() {
  submitting.value = true
  try {
    await authService.resetPassword({
      token: token.value,
      email: email.value,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    toast.success('Mot de passe réinitialisé. Tu peux te connecter.')
    router.push({ name: 'login' })
  } catch {
    // Errors
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="submit">
    <h1 class="text-center text-xl text-gold-400">Nouveau mot de passe</h1>
    <p v-if="email" class="text-center text-sm text-parchment-100/70">{{ email }}</p>

    <div>
      <label for="password" class="mb-1 block text-sm">Nouveau mot de passe</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        autocomplete="new-password"
        class="input"
        required
      />
    </div>

    <div>
      <label for="password_confirmation" class="mb-1 block text-sm">Confirmation</label>
      <input
        id="password_confirmation"
        v-model="form.password_confirmation"
        type="password"
        autocomplete="new-password"
        class="input"
        required
      />
    </div>

    <button type="submit" class="btn-primary w-full" :disabled="submitting || !token">
      {{ submitting ? 'Réinitialisation…' : 'Réinitialiser' }}
    </button>

    <p v-if="!token" class="text-center text-sm text-red-400">
      Lien invalide ou expiré.
      <RouterLink to="/forgot-password" class="text-gold-400 hover:underline">Demander un nouveau lien</RouterLink>
    </p>
  </form>
</template>
