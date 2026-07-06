<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useGameStore } from '@/stores/game'
import { gameService } from '@/services/game.service'
import { buildingMeta, resourceIcon, bonusLabel } from '@/config/townBuildings'

const props = defineProps({
  building: { type: Object, required: true },
})

const emit = defineEmits(['close'])

const game = useGameStore()
const toast = useToast()

// Après un rafraîchissement de l'état, on relit le bâtiment à jour dans le store.
const live = computed(
  () => game.town?.buildings?.find((b) => b.id === props.building.id) ?? props.building,
)

const meta = computed(() => buildingMeta(live.value.key))
const isMaxLevel = computed(() => live.value.level >= live.value.max_level)
const noActionsLeft = computed(() => (game.player?.actions_remaining ?? 0) <= 0)

/** Progression vers le niveau suivant : coût restant + contributions. */
const upgradeRows = computed(() => {
  const cost = live.value.next_cost ?? {}
  const contributions = live.value.contributions ?? {}

  return Object.entries(cost).map(([key, required]) => {
    const done = contributions[key] ?? 0

    return { key, required, done, pct: Math.min(100, Math.round((done / required) * 100)) }
  })
})

async function produce() {
  try {
    const result = await game.performAction(meta.value.action)

    const gains = result.resources
      .map((r) => `+${r.gained} ${resourceIcon(r.key)} ${r.name}`)
      .join(' · ')
    toast.success(`${gains} · +${result.xp_gained} XP`)
  } catch {
    // L'erreur est déjà signalée par l'intercepteur API.
  }
}

async function contribute() {
  try {
    const result = await game.build(live.value.id)

    if (result.leveled_up) {
      toast.success(`🎉 ${live.value.name} passe au niveau ${result.level} !`)
    } else {
      const spent = Object.entries(result.spent)
        .map(([key, qty]) => `${qty} ${resourceIcon(key)}`)
        .join(' · ')
      toast.success(`Travaux avancés : ${spent} investis · +${result.xp_gained} XP`)
    }
  } catch {
    // L'erreur est déjà signalée par l'intercepteur API.
  }
}

// --- Lois (hôtel de ville) ---
const vote = ref(null)
const voteLoading = ref(false)
const voting = ref(false)

/** Lois déjà votées, en vigueur dans la ville. */
const activeLaws = computed(() => game.town?.laws ?? [])

// Chronomètre du vote en cours.
const now = ref(Date.now())
let clock = null

const remainingLabel = computed(() => {
  if (!vote.value?.ends_at) return null

  const ms = new Date(vote.value.ends_at).getTime() - now.value
  if (ms <= 0) return 'Vote en cours de clôture…'

  const hours = Math.floor(ms / 3_600_000)
  const minutes = Math.floor((ms % 3_600_000) / 60_000)

  return hours > 0 ? `Se termine dans ${hours} h ${minutes} min` : `Se termine dans ${minutes} min`
})

onMounted(async () => {
  if (meta.value.special !== 'laws') return

  clock = setInterval(() => {
    now.value = Date.now()
  }, 30_000)

  voteLoading.value = true
  try {
    const { data } = await gameService.getCurrentVote()
    vote.value = data.data
  } finally {
    voteLoading.value = false
  }
})

onUnmounted(() => {
  if (clock) clearInterval(clock)
})

function lawBonusText(law) {
  return Object.entries(law.bonus ?? {})
    .map(([key, value]) => bonusLabel(key, value))
    .join(' · ')
}

async function castBallot(optionId) {
  voting.value = true
  try {
    const { data } = await gameService.castBallot(vote.value.id, optionId)
    vote.value = data.data
    toast.success('Votre voix a été enregistrée.')
  } catch {
    // L'erreur est déjà signalée par l'intercepteur API.
  } finally {
    voting.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
    <!-- Fond -->
    <div class="absolute inset-0 bg-iron-900/70 backdrop-blur-sm" @click="emit('close')" />

    <div
      class="relative z-10 max-h-[85vh] w-full max-w-md overflow-y-auto rounded-lg border border-gold-400/40 bg-iron-900/95 p-5 shadow-2xl"
    >
      <!-- En-tête -->
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="text-4xl">{{ meta.icon }}</span>
          <div>
            <h2 class="font-heading text-xl text-gold-400">{{ live.name }}</h2>
            <p class="text-xs text-parchment-100/70">
              Niveau {{ live.level }} / {{ live.max_level }}
            </p>
          </div>
        </div>
        <button
          type="button"
          class="text-parchment-100/50 transition hover:text-parchment-100"
          aria-label="Fermer"
          @click="emit('close')"
        >
          ✕
        </button>
      </div>

      <p class="mt-2 text-sm text-parchment-100/80">{{ meta.description }}</p>

      <!-- Production -->
      <section v-if="meta.action" class="mt-5">
        <h3 class="font-heading text-sm uppercase tracking-wide text-parchment-100/60">
          Production
        </h3>
        <button
          type="button"
          class="btn-gold mt-2 w-full"
          :disabled="game.acting || noActionsLeft"
          @click="produce"
        >
          {{ meta.actionLabel }} (1 ⚡)
        </button>
        <p v-if="noActionsLeft" class="mt-1 text-center text-xs text-red-400">
          Plus d'actions aujourd'hui — revenez demain !
        </p>
      </section>

      <!-- Lois (hôtel de ville) -->
      <section v-if="meta.special === 'laws'" class="mt-5">
        <!-- Lois en vigueur -->
        <h3 class="font-heading text-sm uppercase tracking-wide text-parchment-100/60">
          Lois en vigueur
        </h3>
        <p v-if="activeLaws.length === 0" class="mt-2 text-sm text-parchment-100/60">
          Aucune loi n'a encore été adoptée.
        </p>
        <ul v-else class="mt-2 space-y-1.5">
          <li
            v-for="law in activeLaws"
            :key="law.name"
            class="rounded-md border border-gold-400/30 bg-gold-500/5 px-3 py-2"
          >
            <p class="text-sm font-semibold text-parchment-100">📜 {{ law.name }}</p>
            <p class="text-xs text-gold-400/90">{{ lawBonusText(law) }}</p>
          </li>
        </ul>

        <!-- Vote en cours -->
        <h3 class="mt-4 font-heading text-sm uppercase tracking-wide text-parchment-100/60">
          Vote de loi
        </h3>

        <p v-if="voteLoading" class="mt-2 animate-pulse text-sm text-parchment-100/60">
          Consultation du conseil…
        </p>

        <p v-else-if="!vote" class="mt-2 text-sm text-parchment-100/60">
          Aucun vote n'est ouvert pour le moment.
        </p>

        <div v-else class="mt-2 space-y-2">
          <p v-if="remainingLabel" class="text-xs font-semibold text-gold-400">
            ⏳ {{ remainingLabel }}
          </p>
          <div
            v-for="option in vote.options"
            :key="option.id"
            class="flex items-center justify-between gap-3 rounded-md border p-3 transition-colors"
            :class="
              vote.my_ballot === option.id
                ? 'border-gold-400 bg-gold-500/10'
                : 'border-iron-700 bg-iron-800/60'
            "
          >
            <div class="min-w-0">
              <p class="truncate text-sm text-parchment-100">{{ option.law.name }}</p>
              <p class="truncate text-xs text-gold-400/80">{{ lawBonusText(option.law) }}</p>
              <p class="text-xs text-parchment-100/60">
                {{ option.votes }} voix
                <span v-if="vote.my_ballot === option.id" class="text-gold-400">— votre choix</span>
              </p>
            </div>
            <button
              type="button"
              class="btn-ghost shrink-0 !px-3 !py-1 text-sm"
              :disabled="voting || vote.my_ballot === option.id"
              @click="castBallot(option.id)"
            >
              Voter
            </button>
          </div>
          <p class="text-right text-xs text-parchment-100/50">
            {{ vote.total_ballots }} bulletin(s) déposé(s)
          </p>
        </div>
      </section>

      <!-- Amélioration -->
      <section class="mt-5">
        <h3 class="font-heading text-sm uppercase tracking-wide text-parchment-100/60">
          Amélioration
        </h3>

        <p v-if="isMaxLevel" class="mt-2 text-sm text-gold-400">
          🏆 Niveau maximum atteint.
        </p>

        <template v-else>
          <div class="mt-2 space-y-2">
            <div v-for="row in upgradeRows" :key="row.key">
              <div class="flex items-center justify-between text-xs text-parchment-100/80">
                <span>{{ resourceIcon(row.key) }} {{ row.key }}</span>
                <span>{{ row.done }} / {{ row.required }}</span>
              </div>
              <div class="mt-0.5 h-2 overflow-hidden rounded-full bg-iron-800">
                <div
                  class="h-full rounded-full bg-gold-400 transition-all"
                  :style="{ width: `${row.pct}%` }"
                />
              </div>
            </div>
          </div>

          <button
            type="button"
            class="btn-ghost mt-3 w-full"
            :disabled="game.acting || noActionsLeft"
            @click="contribute"
          >
            🏗️ Contribuer aux travaux (1 ⚡)
          </button>
          <p class="mt-1 text-center text-xs text-parchment-100/50">
            Les ressources de la ville financent le chantier — l'effort est collectif.
          </p>
        </template>
      </section>
    </div>
  </div>
</template>
