<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { adminService } from '@/services/admin.service'
import { bonusLabel } from '@/config/townBuildings'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

const loading = ref(true)
const games = ref([])
const laws = ref([])

const selectedGameId = ref(null)
const selectedLawIds = ref([])
const hours = ref(12)
const launching = ref(false)

const lawForm = ref({ name: '', description: '', bonus_key: 'gold_bonus_pct', bonus_value: 10 })
const creating = ref(false)

const BONUS_OPTIONS = [
  { value: 'gold_bonus_pct', label: "Production d'or (%)" },
  { value: 'food_bonus_pct', label: 'Production de nourriture (%)' },
  { value: 'wood_bonus_pct', label: 'Production de bois (%)' },
  { value: 'stone_bonus_pct', label: 'Production de pierre (%)' },
  { value: 'soldiers_bonus_pct', label: 'Recrutement de soldats (%)' },
  { value: 'gold_per_day', label: 'Or par jour' },
  { value: 'food_per_day', label: 'Nourriture par jour' },
  { value: 'loyalty_per_day', label: 'Loyauté par jour' },
]

const selectedGame = computed(() => games.value.find((g) => g.id === selectedGameId.value) ?? null)
const canLaunch = computed(
  () => selectedGame.value && selectedLawIds.value.length === 3 && hours.value >= 1,
)

onMounted(async () => {
  try {
    if (!auth.user) {
      await auth.fetchUser()
    }

    if (auth.user?.role !== 'admin') {
      toast.error('Accès réservé aux administrateurs.')
      router.replace({ name: 'home' })
      return
    }

    await Promise.all([loadGames(), loadLaws()])
  } finally {
    loading.value = false
  }
})

async function loadGames() {
  const { data } = await adminService.getGames()
  games.value = data.data

  if (games.value.length > 0) {
    selectedGameId.value = games.value[0].id
    hours.value = games.value[0].vote_hours ?? 12
  }
}

async function loadLaws() {
  const { data } = await adminService.getLaws()
  laws.value = data.data
}

function toggleLaw(id) {
  const index = selectedLawIds.value.indexOf(id)

  if (index >= 0) {
    selectedLawIds.value.splice(index, 1)
  } else if (selectedLawIds.value.length < 3) {
    selectedLawIds.value.push(id)
  } else {
    toast.info('Un vote propose exactement 3 lois — retirez-en une d’abord.')
  }
}

async function launchVote() {
  if (!canLaunch.value) return

  launching.value = true
  try {
    await adminService.openVote({
      gameId: selectedGame.value.id,
      countryId: selectedGame.value.country.id,
      lawIds: selectedLawIds.value,
      hours: hours.value,
    })
    toast.success(`Vote lancé pour ${hours.value} h — que le peuple tranche !`)
    selectedLawIds.value = []
  } catch {
    // Rien
  } finally {
    launching.value = false
  }
}

async function createLaw() {
  creating.value = true
  try {
    await adminService.createLaw(lawForm.value)
    toast.success(`Loi « ${lawForm.value.name} » créée.`)
    lawForm.value = { name: '', description: '', bonus_key: 'gold_bonus_pct', bonus_value: 10 }
    await loadLaws()
  } catch {
    // Rien
  } finally {
    creating.value = false
  }
}

function lawBonusText(law) {
  return Object.entries(law.bonus ?? {})
    .map(([key, value]) => bonusLabel(key, value))
    .join(' · ')
}
</script>

<template>
  <div class="min-h-screen w-full bg-iron-900 px-4 py-6">
    <div class="mx-auto max-w-3xl">
      <header class="flex items-center justify-between">
        <RouterLink to="/play" class="btn-ghost">← Retour au jeu</RouterLink>
        <h1 class="font-heading text-2xl text-gold-400">⚙️ Administration</h1>
        <button type="button" class="btn-ghost" @click="auth.logout()">Déconnexion</button>
      </header>

      <p v-if="loading" class="mt-10 animate-pulse text-center text-parchment-100/60">
        Ouverture de la salle du conseil…
      </p>

      <template v-else>
        <section class="mt-6 rounded-lg border border-gold-400/30 bg-iron-800/50 p-5">
          <h2 class="font-heading text-lg text-gold-400">📜 Lancer un vote de loi</h2>
          <p class="mt-1 text-sm text-parchment-100/70">
            Choisissez <strong class="text-gold-400">exactement 3 lois</strong> : les joueurs du
            royaume auront {{ hours }} h pour élire la leur. À l'échéance, la loi la plus votée
            entre en vigueur dans toutes les villes.
          </p>

          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="block">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">Royaume</span>
              <select v-model="selectedGameId" class="input mt-1">
                <option v-for="g in games" :key="g.id" :value="g.id">
                  {{ g.country.name }} — {{ g.name }}
                </option>
              </select>
            </label>

            <label class="block">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                Durée du vote (heures)
              </span>
              <input v-model.number="hours" type="number" min="1" max="168" class="input mt-1" />
            </label>
          </div>

          <div class="mt-4">
            <p class="text-xs uppercase tracking-wide text-parchment-100/60">
              Lois proposées ({{ selectedLawIds.length }}/3)
            </p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
              <button
                v-for="law in laws"
                :key="law.id"
                type="button"
                class="rounded-md border p-3 text-left transition-colors"
                :class="
                  selectedLawIds.includes(law.id)
                    ? 'border-gold-400 bg-gold-500/10'
                    : 'border-iron-700 bg-iron-800/60 hover:border-parchment-200/40'
                "
                @click="toggleLaw(law.id)"
              >
                <p class="text-sm font-semibold text-parchment-100">
                  <span v-if="selectedLawIds.includes(law.id)" class="text-gold-400">✓</span>
                  {{ law.name }}
                </p>
                <p class="mt-0.5 text-xs text-gold-400/90">{{ lawBonusText(law) }}</p>
                <p v-if="law.description" class="mt-0.5 text-xs text-parchment-100/60">
                  {{ law.description }}
                </p>
              </button>
            </div>
          </div>

          <button
            type="button"
            class="btn-play mt-4 w-full !px-6 !py-3 !text-lg"
            :disabled="!canLaunch || launching"
            @click="launchVote"
          >
            {{ launching ? 'Proclamation…' : `⏳ Lancer le vote (${hours} h)` }}
          </button>
        </section>

        <section class="mt-6 rounded-lg border border-iron-700 bg-iron-800/50 p-5">
          <h2 class="font-heading text-lg text-gold-400">🖋️ Créer une loi</h2>

          <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="createLaw">
            <label class="block sm:col-span-2">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">Nom</span>
              <input
                v-model="lawForm.name"
                type="text"
                required
                minlength="3"
                maxlength="100"
                placeholder="Ex. : Dîme royale"
                class="input mt-1"
              />
            </label>

            <label class="block sm:col-span-2">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                Description (optionnelle)
              </span>
              <input
                v-model="lawForm.description"
                type="text"
                maxlength="255"
                placeholder="Ce que cette loi change pour le royaume…"
                class="input mt-1"
              />
            </label>

            <label class="block">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">Effet</span>
              <select v-model="lawForm.bonus_key" class="input mt-1">
                <option v-for="option in BONUS_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </label>

            <label class="block">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">Valeur</span>
              <input
                v-model.number="lawForm.bonus_value"
                type="number"
                min="1"
                max="100"
                required
                class="input mt-1"
              />
            </label>

            <button
              type="submit"
              class="btn-gold sm:col-span-2"
              :disabled="creating || lawForm.name.trim().length < 3"
            >
              {{ creating ? 'Rédaction…' : 'Promulguer la loi' }}
            </button>
          </form>
        </section>
      </template>
    </div>
  </div>
</template>
