<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { researchService } from '@/services/research.service'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

const loading = ref(true)
const overview = ref(null)
const actions = ref([])
const seasons = ref([])
const downloading = ref(null)

const apiBase = `${import.meta.env.VITE_API_URL}/api/v1`

const JSON_ENDPOINTS = [
  { path: '/research/overview', label: "Vue d'ensemble (agrégats globaux)" },
  { path: '/research/actions', label: 'Usage des actions (comptages + XP)' },
  { path: '/research/seasons', label: 'Classements des saisons (pseudonymisés)' },
]

const stats = computed(() =>
  overview.value
    ? [
        { label: 'Comptes consentants', value: overview.value.accounts, icon: '👤' },
        { label: 'Saisons actives', value: overview.value.seasons.active, icon: '⚔️' },
        { label: 'Saisons terminées', value: overview.value.seasons.ended, icon: '🏁' },
        { label: 'Actions jouées', value: overview.value.actions_total, icon: '⚡' },
        { label: 'XP total distribué', value: overview.value.xp_total, icon: '✨' },
      ]
    : [],
)

onMounted(async () => {
  try {
    if (!auth.user) {
      await auth.fetchUser()
    }

    if (!['researcher', 'admin'].includes(auth.user?.role)) {
      toast.error('Accès réservé aux chercheurs.')
      router.replace({ name: 'home' })
      return
    }

    const [o, a, s] = await Promise.all([
      researchService.getOverview(),
      researchService.getActions(),
      researchService.getSeasons(),
    ])
    overview.value = o.data.data
    actions.value = a.data.data
    seasons.value = s.data.data
  } finally {
    loading.value = false
  }
})

async function download(kind) {
  downloading.value = kind
  try {
    if (kind === 'actions') {
      await researchService.downloadActions()
    } else {
      await researchService.downloadSeasons()
    }
    toast.success('Export téléchargé — ouvrez-le dans Excel.')
  } catch {
    // Continue
  } finally {
    downloading.value = null
  }
}

function endedDate(season) {
  if (!season.ended_at) return ''

  return new Date(season.ended_at).toLocaleDateString('fr-FR')
}
</script>

<template>
  <div class="min-h-screen w-full bg-iron-900 px-4 py-6">
    <div class="mx-auto max-w-3xl">
      <header class="flex items-center justify-between">
        <RouterLink to="/play" class="btn-ghost">← Retour au jeu</RouterLink>
        <h1 class="font-heading text-2xl text-gold-400">🔬 Espace chercheurs</h1>
        <button type="button" class="btn-ghost" @click="auth.logout()">Déconnexion</button>
      </header>

      <p class="mt-2 text-center text-xs text-parchment-100/60">
        Données agrégées et pseudonymisées — aucune donnée personnelle n'est exposée (RGPD).
      </p>

      <p v-if="loading" class="mt-10 animate-pulse text-center text-parchment-100/60">
        Compilation des données…
      </p>

      <template v-else>
        <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
          <div
            v-for="stat in stats"
            :key="stat.label"
            class="rounded-lg border border-iron-700 bg-iron-800/50 p-3 text-center"
          >
            <p class="text-xl">{{ stat.icon }}</p>
            <p class="font-heading text-xl text-gold-400">{{ stat.value }}</p>
            <p class="mt-0.5 text-[11px] leading-tight text-parchment-100/60">{{ stat.label }}</p>
          </div>
        </section>

        <section class="mt-6 rounded-lg border border-iron-700 bg-iron-800/50 p-5">
          <div class="flex items-center justify-between gap-3">
            <h2 class="font-heading text-lg text-gold-400">⚡ Usage des actions</h2>
            <button
              type="button"
              class="btn-gold !px-3 !py-1.5 text-sm"
              :disabled="downloading === 'actions'"
              @click="download('actions')"
            >
              {{ downloading === 'actions' ? 'Export…' : '⬇️ Exporter (Excel)' }}
            </button>
          </div>

          <p v-if="actions.length === 0" class="mt-3 text-sm text-parchment-100/60">
            Aucune action enregistrée pour le moment.
          </p>

          <table v-else class="mt-3 w-full text-sm">
            <thead class="text-left text-xs uppercase text-parchment-100/60">
              <tr>
                <th class="py-1.5">Action</th>
                <th class="py-1.5 text-right">Occurrences</th>
                <th class="py-1.5 text-right">XP distribué</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in actions" :key="row.action" class="border-t border-iron-800">
                <td class="py-1.5 text-parchment-100">{{ row.action }}</td>
                <td class="py-1.5 text-right text-gold-400">{{ row.count }}</td>
                <td class="py-1.5 text-right text-parchment-100/70">{{ row.xp }}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="mt-6 rounded-lg border border-iron-700 bg-iron-800/50 p-5">
          <div class="flex items-center justify-between gap-3">
            <h2 class="font-heading text-lg text-gold-400">🏁 Saisons terminées</h2>
            <button
              type="button"
              class="btn-gold !px-3 !py-1.5 text-sm"
              :disabled="downloading === 'seasons' || seasons.length === 0"
              @click="download('seasons')"
            >
              {{ downloading === 'seasons' ? 'Export…' : '⬇️ Exporter (Excel)' }}
            </button>
          </div>

          <p v-if="seasons.length === 0" class="mt-3 text-sm text-parchment-100/60">
            Aucune saison terminée pour le moment.
          </p>

          <div v-for="season in seasons" :key="season.id" class="mt-4">
            <p class="text-sm font-semibold text-parchment-100">
              {{ season.name }}
              <span class="ml-1 text-xs text-parchment-100/50">— {{ endedDate(season) }}</span>
            </p>
            <table class="mt-1.5 w-full text-sm">
              <thead class="text-left text-xs uppercase text-parchment-100/60">
                <tr>
                  <th class="py-1">#</th>
                  <th class="py-1">Sujet (pseudonyme)</th>
                  <th class="py-1 text-right">XP</th>
                  <th class="py-1 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="entry in season.ranking"
                  :key="entry.rank"
                  class="border-t border-iron-800"
                >
                  <td class="py-1 text-parchment-100/70">{{ entry.rank }}</td>
                  <td class="py-1 font-mono text-xs text-parchment-100">{{ entry.subject }}</td>
                  <td class="py-1 text-right text-gold-400">{{ entry.xp }}</td>
                  <td class="py-1 text-right text-parchment-100/70">{{ entry.actions }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="mt-6 rounded-lg border border-iron-700 bg-iron-800/50 p-5">
          <h2 class="font-heading text-lg text-gold-400">🔌 Accès API (JSON)</h2>
          <p class="mt-1 text-sm text-parchment-100/70">
            Les mêmes jeux de données sont accessibles par script (R, Python…) avec un jeton
            Bearer obtenu via <code class="text-gold-400">POST /auth/login</code> :
          </p>
          <ul class="mt-3 space-y-1.5">
            <li v-for="endpoint in JSON_ENDPOINTS" :key="endpoint.path" class="text-sm">
              <code class="rounded bg-iron-900 px-2 py-0.5 text-xs text-gold-400">
                GET {{ apiBase }}{{ endpoint.path }}
              </code>
              <span class="ml-2 text-parchment-100/60">{{ endpoint.label }}</span>
            </li>
          </ul>
        </section>
      </template>
    </div>
  </div>
</template>
