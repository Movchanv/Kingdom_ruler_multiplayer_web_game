<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useToast } from 'vue-toastification'
import { authService } from '@/services/auth.service'

const email = ref('')
const submitting = ref(false)
const sent = ref(false)
const toast = useToast()

async function submit() {
  submitting.value = true
  try {
    await authService.forgotPassword(email.value)
    sent.value = true
    toast.success('Si un compte correspond à cet email, un lien vient d\'être envoyé.')
  } catch {
    // Errors 442
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <h1 class="text-center text-xl text-gold-400">Mot de passe oublié</h1>

    <form v-if="!sent" class="space-y-4" @submit.prevent="submit">
      <p class="text-sm text-parchment-100/80">
        Entre ton adresse email : on t'enverra un lien pour réinitialiser ton mot de passe.
      </p>
      <div>
        <label for="email" class="mb-1 block text-sm">Email</label>
        <input id="email" v-model="email" type="email" autocomplete="email" class="input" required />
      </div>
      <button type="submit" class="btn-primary w-full" :disabled="submitting">
        {{ submitting ? 'Envoi…' : 'Envoyer le lien' }}
      </button>
    </form>

    <p v-else class="text-sm text-parchment-100/80">
      Vérifie ta boîte mail (et le dossier indésirables). Le lien est valable un temps limité.
    </p>

    <p class="text-center text-sm">
      <RouterLink to="/login" class="text-gold-400 hover:underline">Retour à la connexion</RouterLink>
    </p>
  </div>
</template>
