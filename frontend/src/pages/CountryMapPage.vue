<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { useGameStore } from '@/stores/game'
import { countryMapFor, toPercent, COUNTRY_MAP_RATIO } from '@/config/worldMap'
import { townEmblemFor } from '@/config/townBuildings'
import TownMarker from '@/components/game/TownMarker.vue'
import CountryChat from '@/components/game/CountryChat.vue'

const auth = useAuthStore()
const game = useGameStore()
const router = useRouter()
const toast = useToast()

const mapImageMissing = ref(false)
const selectedTown = ref(null)
const loading = ref(true)

const user = computed(() => auth.user)
const country = computed(() => game.player?.country ?? null)
const mapImage = computed(() => (country.value ? countryMapFor(country.value.slug) : null))

onMounted(async () => {
  try {
    await Promise.all([game.fetchState(), auth.user ? Promise.resolve() : auth.fetchUser()])

    if (!game.hasJoined) {
      router.replace({ name: 'play' })
      return
    }

    await game.fetchTowns()
  } finally {
    loading.value = false
  }
})

function positionOf(town) {
  return { x: toPercent(town.map_x), y: toPercent(town.map_y) }
}

async function travelToSelected() {
  if (!selectedTown.value) return

  const name = selectedTown.value.name

  await game.travelTo(selectedTown.value.id)
  toast.success(`Vous voilà à ${name}.`)

  selectedTown.value = game.towns.find((t) => t.id === selectedTown.value.id) ?? null
}
</script>

<template>
  <div class="relative flex h-screen w-screen items-center justify-center overflow-hidden bg-iron-900">
    <div class="relative max-h-full max-w-full" :style="{ aspectRatio: COUNTRY_MAP_RATIO }">
      <img
        v-if="mapImage && !mapImageMissing"
        :src="mapImage"
        :alt="`Carte de ${country?.name ?? 'votre royaume'}`"
        class="h-full w-full select-none object-fill"
        draggable="false"
        @error="mapImageMissing = true"
      />
      <div
        v-else
        class="flex h-full w-full flex-col items-center justify-center gap-2 border border-iron-700 bg-gradient-to-b from-iron-800 to-iron-900 text-center"
      >
        <span class="text-4xl">🗺️</span>
        <p class="max-w-xs text-sm text-parchment-100/70">
          Carte du royaume introuvable pour
          <code class="text-gold-400">{{ country?.slug ?? '…' }}</code
          >.
        </p>
      </div>

      <TownMarker
        v-for="town in game.towns"
        :key="town.id"
        :town="town"
        :position="positionOf(town)"
        :emblem="townEmblemFor(town.name)"
        :selected="selectedTown?.id === town.id"
        @select="selectedTown = $event"
      />
    </div>

    <div class="pointer-events-none absolute inset-0 shadow-[inset_0_0_120px_40px_rgba(0,0,0,0.55)]" />

    <header class="absolute inset-x-0 top-0 z-20 flex items-center justify-between p-4">
      <RouterLink :to="{ name: 'play' }" class="btn-ghost">← Carte du monde</RouterLink>

      <div class="flex items-center gap-3">
        <div
          v-if="user"
          class="flex items-center gap-2 rounded-md border border-iron-700 bg-iron-900/80 px-3 py-1.5 backdrop-blur-sm"
        >
          <span class="text-gold-400">⚔️</span>
          <span class="text-sm text-parchment-100">{{ user.username }}</span>
          <span v-if="user.title" class="rounded bg-gold-500/15 px-1.5 py-0.5 text-xs text-gold-400">
            {{ user.title }}
          </span>
          <span class="text-xs text-parchment-100/60">{{ user.xp }} XP</span>
        </div>
        <button type="button" class="btn-ghost" @click="auth.logout()">Déconnexion</button>
      </div>
    </header>

    <div class="pointer-events-none absolute inset-x-0 top-16 z-10 text-center sm:top-20">
      <h1 class="font-heading text-2xl text-gold-400 drop-shadow-lg sm:text-3xl">
        {{ country?.name ?? 'Votre royaume' }}
      </h1>
      <p v-if="game.player" class="mt-1 text-sm text-parchment-100/80">
        {{ game.player.actions_remaining }}/{{ game.player.daily_actions }} actions aujourd'hui
      </p>
    </div>

    <div
      v-if="loading"
      class="absolute inset-0 z-30 flex items-center justify-center bg-iron-900/70 backdrop-blur-sm"
    >
      <p class="animate-pulse font-heading text-xl text-gold-400">Chevauchée vers le royaume…</p>
    </div>

    <transition name="panel">
      <aside v-if="selectedTown" class="absolute inset-x-0 bottom-0 z-20 mx-auto w-full max-w-lg p-4">
        <div class="rounded-lg border border-gold-400/40 bg-iron-900/90 p-5 shadow-2xl backdrop-blur">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <h2 class="font-heading text-xl text-gold-400">{{ selectedTown.name }}</h2>
              <div class="mt-2 space-y-1.5 text-sm text-parchment-100/80">
                <p>👥 {{ selectedTown.population }} habitant(s)</p>
                <div class="flex items-center gap-2">
                  <span>❤️</span>
                  <div class="h-2 flex-1 overflow-hidden rounded-full bg-iron-800">
                    <div
                      class="h-full rounded-full transition-all"
                      :class="selectedTown.loyalty > 50 ? 'bg-gold-400' : 'bg-red-500'"
                      :style="{ width: `${selectedTown.loyalty}%` }"
                    />
                  </div>
                  <span class="text-xs">{{ selectedTown.loyalty }}%</span>
                </div>
              </div>
            </div>
            <button
              type="button"
              class="text-parchment-100/50 transition hover:text-parchment-100"
              aria-label="Fermer"
              @click="selectedTown = null"
            >
              ✕
            </button>
          </div>

          <div class="mt-4">
            <div v-if="selectedTown.is_current" class="space-y-2 text-center">
              <p class="text-sm text-parchment-100/80">📍 Vous êtes dans cette ville.</p>
              <RouterLink :to="{ name: 'town' }" class="btn-gold w-full">
                Entrer dans la ville
              </RouterLink>
            </div>

            <button
              v-else
              type="button"
              class="btn-gold w-full"
              :disabled="game.traveling"
              @click="travelToSelected"
            >
              {{ game.traveling ? 'En chemin…' : `Voyager vers ${selectedTown.name}` }}
            </button>
          </div>
        </div>
      </aside>
    </transition>

    <CountryChat />
  </div>
</template>

<style scoped>
.panel-enter-active,
.panel-leave-active {
  transition:
    transform 0.25s ease,
    opacity 0.25s ease;
}
.panel-enter-from,
.panel-leave-to {
  transform: translateY(16px);
  opacity: 0;
}
</style>
