<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

const genders = [
  { value: 'male', label: 'Homme' },
  { value: 'female', label: 'Femme' },
  { value: 'other', label: 'Autre' },
  { value: 'prefer_not_to_say', label: 'Préfère ne pas répondre' },
]

const form = reactive({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  date_of_birth: '',
  country: '',
  gender: '',
  terms_accepted: false,
})

const submitting = ref(false)

async function submit() {
  submitting.value = true
  try {
    await auth.register({ ...form, device_name: 'web' })
    toast.success('Compte créé ! Vérifie tes emails pour activer ton compte.')
    router.push({ name: 'home' })
  } catch {
    // Errors 422
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="submit">
    <h1 class="text-center text-xl text-gold-400">Créer un compte</h1>

    <div>
      <label for="username" class="mb-1 block text-sm">Nom de joueur</label>
      <input id="username" v-model="form.username" type="text" autocomplete="username" class="input" required />
    </div>

    <div>
      <label for="email" class="mb-1 block text-sm">Email</label>
      <input id="email" v-model="form.email" type="email" autocomplete="email" class="input" required />
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label for="password" class="mb-1 block text-sm">Mot de passe</label>
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
    </div>

    <div>
      <label for="date_of_birth" class="mb-1 block text-sm">Date de naissance</label>
      <input id="date_of_birth" v-model="form.date_of_birth" type="date" class="input" required />
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label for="country" class="mb-1 block text-sm">Pays <span class="text-iron-700">(optionnel)</span></label>
        <input id="country" v-model="form.country" type="text" maxlength="2" placeholder="FR" class="input" />
      </div>
      <div>
        <label for="gender" class="mb-1 block text-sm">Sexe <span class="text-iron-700">(optionnel)</span></label>
        <select id="gender" v-model="form.gender" class="input">
          <option value="">—</option>
          <option v-for="g in genders" :key="g.value" :value="g.value">{{ g.label }}</option>
        </select>
      </div>
    </div>

    <label class="flex items-start gap-2 text-sm">
      <input v-model="form.terms_accepted" type="checkbox" class="mt-1" required />
      <span>J'accepte les conditions générales d'utilisation et la politique de confidentialité.</span>
    </label>

    <button type="submit" class="btn-primary w-full" :disabled="submitting">
      {{ submitting ? 'Création…' : "S'inscrire" }}
    </button>

    <p class="text-center text-sm text-parchment-100/80">
      Déjà un compte ?
      <RouterLink to="/login" class="text-gold-400 hover:underline">Se connecter</RouterLink>
    </p>
  </form>
</template>
