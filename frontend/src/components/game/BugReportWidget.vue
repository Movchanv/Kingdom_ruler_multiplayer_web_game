<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { supportService } from '@/services/support.service'

const route = useRoute()
const toast = useToast()

const open = ref(false)
const sending = ref(false)
const reference = ref(null)

const form = ref({
  title: '',
  severity: 'major',
  scope: 'frontend',
  description: '',
})

const SEVERITIES = [
  { value: 'blocking', label: 'Bloquant — le jeu est inutilisable' },
  { value: 'major', label: 'Majeur — une fonctionnalité est dégradée' },
  { value: 'minor', label: 'Mineur — gêne légère' },
]

const SCOPES = [
  { value: 'frontend', label: "Interface / affichage" },
  { value: 'backend', label: 'Action de jeu / données' },
  { value: 'realtime', label: 'Temps réel (chat, mises à jour)' },
  { value: 'other', label: 'Autre' },
]

const canSend = computed(
  () => form.value.title.trim().length >= 5 && form.value.description.trim().length >= 20,
)

async function send() {
  if (!canSend.value || sending.value) return

  sending.value = true
  try {
    const { data } = await supportService.report({ ...form.value, page: route.fullPath })
    reference.value = data.data.reference
    toast.success(data.message)
    form.value = { title: '', severity: 'major', scope: 'frontend', description: '' }
  } catch {
    // L'erreur est déjà signalée par l'intercepteur API.
  } finally {
    sending.value = false
  }
}

function close() {
  open.value = false
  reference.value = null
}
</script>

<template>
  <button
    type="button"
    class="fixed bottom-4 left-4 z-30 flex h-11 w-11 items-center justify-center rounded-full border-2 border-iron-700 bg-iron-900/90 text-lg shadow-xl backdrop-blur transition hover:border-red-400/70 hover:shadow-red-900/30"
    title="Signaler un problème"
    aria-label="Signaler un problème"
    @click="open = true"
  >
    🐞
  </button>

  <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-iron-900/70 backdrop-blur-sm" @click="close" />

    <div
      class="relative z-10 max-h-[85vh] w-full max-w-md overflow-y-auto rounded-lg border border-gold-400/40 bg-iron-900/95 p-5 shadow-2xl"
    >
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="text-3xl">🐞</span>
          <div>
            <h2 class="font-heading text-lg text-gold-400">Signaler un problème</h2>
            <p class="text-xs text-parchment-100/70">Votre retour aide à améliorer le jeu.</p>
          </div>
        </div>
        <button
          type="button"
          class="text-parchment-100/50 transition hover:text-parchment-100"
          aria-label="Fermer"
          @click="close"
        >
          ✕
        </button>
      </div>

      <div v-if="reference" class="mt-5 text-center">
        <p class="text-sm text-parchment-100">
          Merci ! Votre signalement est enregistré sous la référence
          <span class="font-mono text-gold-400">{{ reference }}</span
          >.
        </p>
        <button type="button" class="btn-gold mt-4 w-full" @click="close">Fermer</button>
      </div>

      <form v-else class="mt-4 space-y-3" @submit.prevent="send">
        <label class="block">
          <span class="text-xs uppercase tracking-wide text-parchment-100/60">Résumé</span>
          <input
            v-model="form.title"
            type="text"
            required
            minlength="5"
            maxlength="150"
            placeholder="Ex. : le chat ne se met pas à jour"
            class="input mt-1"
          />
        </label>

        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block">
            <span class="text-xs uppercase tracking-wide text-parchment-100/60">Gravité</span>
            <select v-model="form.severity" class="input mt-1">
              <option v-for="s in SEVERITIES" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs uppercase tracking-wide text-parchment-100/60">Domaine</span>
            <select v-model="form.scope" class="input mt-1">
              <option v-for="s in SCOPES" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </label>
        </div>

        <label class="block">
          <span class="text-xs uppercase tracking-wide text-parchment-100/60">
            Description (étapes, résultat attendu, résultat observé)
          </span>
          <textarea
            v-model="form.description"
            rows="5"
            required
            minlength="20"
            maxlength="2000"
            placeholder="1. J'ouvre la ville…&#10;Attendu : …&#10;Observé : …"
            class="input mt-1"
          />
        </label>

        <p class="text-xs text-parchment-100/50">
          La page consultée et votre navigateur sont joints automatiquement au signalement.
        </p>

        <button type="submit" class="btn-gold w-full" :disabled="!canSend || sending">
          {{ sending ? 'Envoi…' : 'Envoyer le signalement' }}
        </button>
      </form>
    </div>
  </div>
</template>
