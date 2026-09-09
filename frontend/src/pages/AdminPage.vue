<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { adminService } from '@/services/admin.service'
import { supportService } from '@/services/support.service'
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

const events = ref([])
const emptyEventForm = () => ({
  name: '',
  description: '',
  icon: '⚔️',
  type: 'world',
  difficulty: 'medium',
  effect_key_1: 'gold',
  effect_value_1: -20,
  effect_key_2: '',
  effect_value_2: -5,
  requirement_key: 'soldiers',
  requirement_value: 8,
  success_key: 'gold',
  success_value: 40,
  failure_key: 'gold',
  failure_value: -60,
  delay_min_minutes: 60,
  delay_max_minutes: 180,
})
const eventForm = ref(emptyEventForm())
const creatingEvent = ref(false)
const triggerTownId = ref(null)
const triggeringEventId = ref(null)

const EVENT_TYPE_OPTIONS = [
  { value: 'world', label: 'Monde (tirage automatique)' },
  { value: 'adventure', label: 'Aventure' },
  { value: 'manual', label: 'Manuel (admin uniquement)' },
]

const EVENT_TYPE_LABELS = {
  world: 'Monde',
  adventure: 'Aventure',
  manual: 'Manuel',
}

const EVENT_DIFFICULTY_OPTIONS = [
  { value: 'easy', label: 'Facile' },
  { value: 'medium', label: 'Moyenne' },
  { value: 'hard', label: 'Difficile' },
]

const EVENT_DIFFICULTY_LABELS = {
  easy: 'Facile',
  medium: 'Moyenne',
  hard: 'Difficile',
}

const EFFECT_OPTIONS = [
  { value: 'gold', label: 'Or' },
  { value: 'food', label: 'Nourriture' },
  { value: 'wood', label: 'Bois' },
  { value: 'stone', label: 'Pierre' },
  { value: 'soldiers', label: 'Soldats' },
  { value: 'iron', label: 'Fer' },
  { value: 'coal', label: 'Charbon' },
  { value: 'loyalty', label: 'Loyauté' },
  { value: 'free_action', label: 'Action offerte' },
]

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

    await Promise.all([loadGames(), loadLaws(), loadEvents(), loadMonitoring(), loadReports()])
  } finally {
    loading.value = false
  }
})

const monitoring = ref(null)
const reports = ref([])

async function loadMonitoring() {
  const { data } = await supportService.getMonitoring()
  monitoring.value = data.data
}

async function loadReports() {
  const { data } = await supportService.getReports()
  reports.value = data.data.data
}

async function refreshMonitoring() {
  await Promise.all([loadMonitoring(), loadReports()])
  toast.success('Supervision actualisée.')
}

const SEVERITY_CLASSES = {
  blocking: 'border-red-400/50 bg-red-400/10 text-red-300',
  major: 'border-gold-400/50 bg-gold-400/10 text-gold-400',
  minor: 'border-iron-700 bg-iron-800/60 text-parchment-100/80',
}

function reportDate(report) {
  return report.created_at ? new Date(report.created_at).toLocaleString('fr-FR') : ''
}

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
  } finally {
    creating.value = false
  }
}

async function loadEvents() {
  const { data } = await adminService.getEvents()
  events.value = data.data
}

const isAdventure = computed(() => eventForm.value.type === 'adventure')

async function createEvent() {
  const form = eventForm.value
  const payload = {
    name: form.name,
    description: form.description || null,
    icon: form.icon || null,
    type: form.type,
    difficulty: form.difficulty,
  }

  if (isAdventure.value) {
    const effects = { [form.effect_key_1]: Number(form.effect_value_1) }

    if (form.effect_key_2 && form.effect_key_2 !== form.effect_key_1) {
      effects[form.effect_key_2] = Number(form.effect_value_2)
    }

    payload.effects = effects
  } else {
    payload.requirement = form.requirement_key
      ? { [form.requirement_key]: Number(form.requirement_value) }
      : null
    payload.success_effects = { [form.success_key]: Number(form.success_value) }
    payload.failure_effects = { [form.failure_key]: Number(form.failure_value) }
    payload.delay_min_minutes = Number(form.delay_min_minutes)
    payload.delay_max_minutes = Number(form.delay_max_minutes)
  }

  creatingEvent.value = true
  try {
    await adminService.createEvent(payload)
    toast.success(`Événement « ${form.name} » créé.`)
    eventForm.value = emptyEventForm()
    await loadEvents()
  } catch {
  } finally {
    creatingEvent.value = false
  }
}

const townChoices = computed(() =>
  games.value.flatMap((game) =>
    (game.towns ?? []).map((town) => ({ id: town.id, label: `${town.name} — ${game.name}` })),
  ),
)

async function triggerEvent(event) {
  if (!triggerTownId.value) {
    toast.error('Choisissez une ville cible.')
    return
  }

  triggeringEventId.value = event.id
  try {
    const { data } = await adminService.triggerEvent(event.id, triggerTownId.value)
    const effects = (data.data.effects ?? [])
      .map((effect) => `${effect.key} ${effect.delta > 0 ? '+' : ''}${effect.delta}`)
      .join(' · ')
    toast.success(`« ${event.name} » déclenché${effects ? ` : ${effects}` : ''}.`)
  } catch {
  } finally {
    triggeringEventId.value = null
  }
}

function eventEffectsText(event) {
  return Object.entries(event.effects ?? {})
    .map(([key, value]) => {
      const label = EFFECT_OPTIONS.find((option) => option.value === key)?.label ?? key
      return `${label} ${value > 0 ? '+' : ''}${value}`
    })
    .join(' · ')
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

        <section class="mt-6 rounded-lg border border-iron-700 bg-iron-800/50 p-5">
          <h2 class="font-heading text-lg text-gold-400">⚡ Créer un événement</h2>
          <p class="mt-1 text-xs text-parchment-100/60">
            Les événements de type « Monde » sont tirés automatiquement selon la pression de la
            saison ; leurs effets sont alors amplifiés jusqu'au double.
          </p>

          <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="createEvent">
            <label class="block sm:col-span-2">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">Nom</span>
              <input
                v-model="eventForm.name"
                type="text"
                required
                minlength="3"
                maxlength="100"
                placeholder="Ex. : Incendie au grenier"
                class="input mt-1"
              />
            </label>

            <label class="block sm:col-span-2">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                Description (optionnelle)
              </span>
              <input
                v-model="eventForm.description"
                type="text"
                maxlength="255"
                placeholder="Ce que subit la ville…"
                class="input mt-1"
              />
            </label>

            <label class="block">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">Type</span>
              <select v-model="eventForm.type" class="input mt-1">
                <option v-for="option in EVENT_TYPE_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </label>

            <label class="block">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">Difficulté</span>
              <select v-model="eventForm.difficulty" class="input mt-1">
                <option
                  v-for="option in EVENT_DIFFICULTY_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </label>

            <label class="block">
              <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                Pastille (emoji)
              </span>
              <input
                v-model="eventForm.icon"
                type="text"
                maxlength="4"
                placeholder="🪓"
                class="input mt-1"
              />
            </label>

            <template v-if="isAdventure">
              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">Effet</span>
                <select v-model="eventForm.effect_key_1" class="input mt-1">
                  <option
                    v-for="option in EFFECT_OPTIONS"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                  Valeur (négative = perte)
                </span>
                <input
                  v-model.number="eventForm.effect_value_1"
                  type="number"
                  min="-500"
                  max="500"
                  class="input mt-1"
                />
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                  Second effet (optionnel)
                </span>
                <select v-model="eventForm.effect_key_2" class="input mt-1">
                  <option value="">Aucun</option>
                  <option
                    v-for="option in EFFECT_OPTIONS"
                    :key="option.value"
                    :value="option.value"
                    :disabled="option.value === eventForm.effect_key_1"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">Valeur</span>
                <input
                  v-model.number="eventForm.effect_value_2"
                  type="number"
                  min="-500"
                  max="500"
                  :disabled="!eventForm.effect_key_2"
                  class="input mt-1"
                />
              </label>
            </template>

            <template v-else>
              <p class="text-xs text-parchment-100/50 sm:col-span-2">
                La ville devra réunir l'exigence avant l'échéance. Sans exigence, l'événement est
                toujours une victoire.
              </p>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                  Exigence
                </span>
                <select v-model="eventForm.requirement_key" class="input mt-1">
                  <option value="">Aucune</option>
                  <option
                    v-for="option in EFFECT_OPTIONS"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">Quantité</span>
                <input
                  v-model.number="eventForm.requirement_value"
                  type="number"
                  min="1"
                  :disabled="!eventForm.requirement_key"
                  class="input mt-1"
                />
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-green-300/70">
                  En cas de victoire
                </span>
                <select v-model="eventForm.success_key" class="input mt-1">
                  <option
                    v-for="option in EFFECT_OPTIONS"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">Valeur</span>
                <input
                  v-model.number="eventForm.success_value"
                  type="number"
                  min="-500"
                  max="500"
                  class="input mt-1"
                />
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-red-300/70">
                  En cas de défaite
                </span>
                <select v-model="eventForm.failure_key" class="input mt-1">
                  <option
                    v-for="option in EFFECT_OPTIONS"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">Valeur</span>
                <input
                  v-model.number="eventForm.failure_value"
                  type="number"
                  min="-500"
                  max="500"
                  class="input mt-1"
                />
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                  Délai minimum (min)
                </span>
                <input
                  v-model.number="eventForm.delay_min_minutes"
                  type="number"
                  min="0"
                  max="10080"
                  class="input mt-1"
                />
              </label>

              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                  Délai maximum (min)
                </span>
                <input
                  v-model.number="eventForm.delay_max_minutes"
                  type="number"
                  :min="eventForm.delay_min_minutes"
                  max="10080"
                  class="input mt-1"
                />
              </label>
            </template>

            <button
              type="submit"
              class="btn-gold sm:col-span-2"
              :disabled="
                creatingEvent ||
                eventForm.name.trim().length < 3 ||
                (isAdventure && Number(eventForm.effect_value_1) === 0)
              "
            >
              {{ creatingEvent ? 'Création…' : "Créer l'événement" }}
            </button>
          </form>

          <div v-if="events.length" class="mt-6 border-t border-iron-700 pt-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
              <h3 class="font-heading text-base text-gold-400">Catalogue</h3>
              <label class="block">
                <span class="text-xs uppercase tracking-wide text-parchment-100/60">
                  Ville ciblée par un déclenchement
                </span>
                <select v-model.number="triggerTownId" class="input mt-1">
                  <option :value="null">Choisir une ville…</option>
                  <option v-for="town in townChoices" :key="town.id" :value="town.id">
                    {{ town.label }}
                  </option>
                </select>
              </label>
            </div>

            <ul class="mt-4 space-y-2">
              <li
                v-for="event in events"
                :key="event.id"
                class="rounded border border-iron-700 bg-iron-900/40 px-3 py-2 text-sm"
              >
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-parchment-100">{{ event.name }}</span>
                    <span
                      class="rounded-full border px-2 py-0.5 text-xs"
                      :class="
                        event.type === 'manual'
                          ? 'border-gold-400/50 bg-gold-400/10 text-gold-300'
                          : 'border-iron-600 text-parchment-100/70'
                      "
                    >
                      {{ EVENT_TYPE_LABELS[event.type] ?? event.type }}
                    </span>
                    <span
                      class="rounded-full border border-iron-600 px-2 py-0.5 text-xs text-parchment-100/70"
                    >
                      {{ EVENT_DIFFICULTY_LABELS[event.difficulty] ?? event.difficulty }}
                    </span>
                  </div>

                  <button
                    type="button"
                    class="btn-ghost text-xs"
                    :disabled="!triggerTownId || triggeringEventId === event.id"
                    @click="triggerEvent(event)"
                  >
                    {{ triggeringEventId === event.id ? 'Déclenchement…' : '⚡ Déclencher' }}
                  </button>
                </div>
                <p class="mt-1 text-xs text-parchment-100/60">{{ eventEffectsText(event) }}</p>
              </li>
            </ul>
          </div>
        </section>

        <section v-if="monitoring" class="mt-6 rounded-lg border border-iron-700 bg-iron-800/50 p-5">
          <div class="flex items-center justify-between gap-3">
            <h2 class="font-heading text-lg text-gold-400">📡 Supervision</h2>
            <div class="flex items-center gap-2">
              <span
                class="rounded-full border px-2 py-0.5 text-xs font-semibold"
                :class="
                  monitoring.status === 'ok'
                    ? 'border-green-400/50 bg-green-400/10 text-green-300'
                    : 'border-red-400/50 bg-red-400/10 text-red-300'
                "
              >
                {{ monitoring.status === 'ok' ? '● Opérationnel' : '▲ Dégradé' }}
              </span>
              <button type="button" class="btn-ghost !px-3 !py-1 text-sm" @click="refreshMonitoring">
                Actualiser
              </button>
            </div>
          </div>

          <ul v-if="monitoring.alerts.length" class="mt-3 space-y-1.5">
            <li
              v-for="(alert, i) in monitoring.alerts"
              :key="i"
              class="rounded-md border border-red-400/40 bg-red-400/10 px-3 py-2 text-sm text-red-300"
            >
              ▲ {{ alert.message }}
            </li>
          </ul>

          <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div
              v-for="(up, name) in monitoring.dependencies"
              :key="name"
              class="rounded-lg border border-iron-700 bg-iron-900/60 p-3 text-center"
            >
              <p class="text-xl">{{ up ? '✅' : '❌' }}</p>
              <p class="mt-0.5 text-xs capitalize text-parchment-100/70">{{ name }}</p>
            </div>

            <div class="rounded-lg border border-iron-700 bg-iron-900/60 p-3 text-center">
              <p class="font-heading text-xl" :class="monitoring.queues.failed ? 'text-red-400' : 'text-gold-400'">
                {{ monitoring.queues.failed }}
              </p>
              <p class="mt-0.5 text-xs text-parchment-100/70">Jobs en échec</p>
            </div>

            <div class="rounded-lg border border-iron-700 bg-iron-900/60 p-3 text-center">
              <p
                class="font-heading text-xl"
                :class="monitoring.scheduler.healthy ? 'text-gold-400' : 'text-red-400'"
              >
                {{ monitoring.scheduler.lag_minutes ?? '—' }}
              </p>
              <p class="mt-0.5 text-xs text-parchment-100/70">Retard ordonn. (min)</p>
            </div>

            <div class="rounded-lg border border-iron-700 bg-iron-900/60 p-3 text-center">
              <p
                class="font-heading text-xl"
                :class="monitoring.anomalies.blocking_open ? 'text-red-400' : 'text-gold-400'"
              >
                {{ monitoring.anomalies.open }}
              </p>
              <p class="mt-0.5 text-xs text-parchment-100/70">Anomalies ouvertes</p>
            </div>

            <div class="rounded-lg border border-iron-700 bg-iron-900/60 p-3 text-center">
              <p class="font-heading text-xl text-gold-400">{{ monitoring.game.actions_last_24h }}</p>
              <p class="mt-0.5 text-xs text-parchment-100/70">Actions / 24 h</p>
            </div>
          </div>
        </section>

        <section class="mt-6 rounded-lg border border-iron-700 bg-iron-800/50 p-5">
          <h2 class="font-heading text-lg text-gold-400">🐞 Anomalies consignées</h2>

          <p v-if="reports.length === 0" class="mt-3 text-sm text-parchment-100/60">
            Aucune anomalie signalée pour le moment.
          </p>

          <ul v-else class="mt-3 space-y-2">
            <li
              v-for="report in reports"
              :key="report.id"
              class="rounded-md border border-iron-700 bg-iron-900/60 p-3"
            >
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs text-gold-400">{{ report.reference }}</span>
                <span
                  class="rounded border px-1.5 py-0.5 text-xs"
                  :class="SEVERITY_CLASSES[report.severity]"
                >
                  {{ report.severity_label }}
                </span>
                <span class="text-xs text-parchment-100/50">{{ report.status_label }}</span>
                <span class="ml-auto text-xs text-parchment-100/50">{{ reportDate(report) }}</span>
              </div>
              <p class="mt-1 text-sm text-parchment-100">{{ report.title }}</p>
              <p class="mt-0.5 text-xs text-parchment-100/60">
                {{ report.scope }}<template v-if="report.page"> · {{ report.page }}</template>
                <template v-if="report.reported_by"> · signalé par {{ report.reported_by }}</template>
              </p>
            </li>
          </ul>
        </section>
      </template>
    </div>
  </div>
</template>
