<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()

const form = reactive({
  email: '',
  password: '',
})

const submitting = ref(false)

async function submit() {
  submitting.value = true
  try {
    await auth.login({ ...form, device_name: 'web' })
    toast.success('Connexion réussie.')
    router.push(route.query.redirect ?? { name: 'home' })
  } catch {
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="submit">
    <h1 class="text-center text-xl text-gold-400">Connexion</h1>

    <div>
      <label for="email" class="mb-1 block text-sm">Email</label>
      <input id="email" v-model="form.email" type="email" autocomplete="email" class="input" required />
    </div>

    <div>
      <label for="password" class="mb-1 block text-sm">Mot de passe</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        autocomplete="current-password"
        class="input"
        required
      />
    </div>

    <button type="submit" class="btn-primary w-full" :disabled="submitting">
      {{ submitting ? 'Connexion…' : 'Se connecter' }}
    </button>
  </form>
</template>
