<script setup>
import { computed, ref } from 'vue'
import { useGameStore } from '@/stores/game'
import { resourceIcon } from '@/config/townBuildings'

const emit = defineEmits(['close'])

const game = useGameStore()

const result = ref(null)
const noActionsLeft = computed(() => (game.player?.actions_remaining ?? 0) <= 0)

const DIFFICULTIES = {
  easy: { label: 'Facile', class: 'border-green-400/60 text-green-400' },
  medium: { label: 'Périlleuse', class: 'border-gold-400/60 text-gold-400' },
  hard: { label: 'Redoutable', class: 'border-red-400/60 text-red-400' },
}

const difficulty = computed(() => {
  const key = result.value?.event?.difficulty

  return key ? (DIFFICULTIES[key] ?? null) : null
})

async function embark() {
  try {
    result.value = await game.adventure()
  } catch {
    // L'erreur est déjà signalée par l'intercepteur API.
  }
}

function effectLabel(effect) {
  const icon = effect.key === 'loyalty' ? '❤️' : resourceIcon(effect.key)
  const sign = effect.delta > 0 ? '+' : ''

  return `${sign}${effect.delta} ${icon} ${effect.key === 'loyalty' ? 'loyauté' : effect.key}`
}
</script>

<template>
  <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-iron-900/70 backdrop-blur-sm" @click="emit('close')" />

    <div
      class="relative z-10 max-h-[85vh] w-full max-w-md overflow-y-auto rounded-lg border border-gold-400/40 bg-iron-900/95 p-5 shadow-2xl"
    >
      <!-- En-tête -->
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="text-4xl">🐎</span>
          <div>
            <h2 class="font-heading text-xl text-gold-400">Partir à l'aventure</h2>
            <p class="text-xs text-parchment-100/70">Au-delà des portes de la ville…</p>
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

      <!-- Avant le départ -->
      <template v-if="!result">
        <p class="mt-3 text-sm text-parchment-100/80">
          Franchissez les portes et tentez votre chance sur les routes du royaume : trésors,
          rencontres… ou embuscades. Ce qui vous arrive rejaillit sur toute la ville.
        </p>

        <button
          type="button"
          class="btn-play mt-5 w-full !px-6 !py-3 !text-lg"
          :disabled="game.acting || noActionsLeft"
          @click="embark"
        >
          {{ game.acting ? 'En chevauchée…' : "Partir à l'aventure (1 ⚡)" }}
        </button>
        <p v-if="noActionsLeft" class="mt-2 text-center text-xs text-red-400">
          Plus d'actions aujourd'hui — revenez demain !
        </p>
      </template>

      <!-- La carte événement tirée -->
      <template v-else>
        <div class="adventure-card mt-4 rounded-lg border border-gold-400/50 bg-iron-800/70 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-heading text-lg text-parchment-100">{{ result.event.name }}</h3>
            <span
              v-if="difficulty"
              class="shrink-0 rounded-full border px-2 py-0.5 text-xs"
              :class="difficulty.class"
            >
              {{ difficulty.label }}
            </span>
          </div>

          <p class="mt-2 text-sm italic text-parchment-100/80">
            {{ result.event.description }}
          </p>

          <div class="mt-3 flex flex-wrap gap-2">
            <span
              v-for="effect in result.effects"
              :key="effect.key"
              class="rounded-md border px-2 py-1 text-sm"
              :class="
                effect.delta >= 0
                  ? 'border-green-400/40 bg-green-400/10 text-green-300'
                  : 'border-red-400/40 bg-red-400/10 text-red-300'
              "
            >
              {{ effectLabel(effect) }}
            </span>

            <span
              v-if="result.free_actions > 0"
              class="rounded-md border border-gold-400/40 bg-gold-400/10 px-2 py-1 text-sm text-gold-400"
            >
              ⚡ +{{ result.free_actions }} action offerte
            </span>

            <span
              class="rounded-md border border-royal-500/50 bg-royal-600/20 px-2 py-1 text-sm text-parchment-100"
            >
              ✨ +{{ result.xp_gained }} XP
            </span>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <button
            type="button"
            class="btn-gold flex-1"
            :disabled="game.acting || noActionsLeft"
            @click="embark"
          >
            {{ game.acting ? 'En chevauchée…' : 'Repartir (1 ⚡)' }}
          </button>
          <button type="button" class="btn-ghost flex-1" @click="emit('close')">
            Rentrer en ville
          </button>
        </div>
        <p v-if="noActionsLeft" class="mt-2 text-center text-xs text-red-400">
          Plus d'actions aujourd'hui.
        </p>
      </template>
    </div>
  </div>
</template>

<style scoped>
.adventure-card {
  animation: card-reveal 0.35s ease-out;
}

@keyframes card-reveal {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
