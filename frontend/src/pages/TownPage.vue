<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useGameStore } from '@/stores/game'
import { useEcho } from '@/composables/useEcho'
import { toPercent } from '@/config/worldMap'
import {
  townImageFor,
  TOWN_IMAGE_RATIO,
  resourceIcon,
  adventureSpotFor,
  buildingImageFor,
  ADVENTURE_IMAGE,
} from '@/config/townBuildings'
import { rectFor } from '@/config/townRects'
import BuildingMarker from '@/components/game/BuildingMarker.vue'
import BuildingHotspot from '@/components/game/BuildingHotspot.vue'
import BuildingPanel from '@/components/game/BuildingPanel.vue'
import AdventurePanel from '@/components/game/AdventurePanel.vue'
import CountryChat from '@/components/game/CountryChat.vue'

const auth = useAuthStore()
const game = useGameStore()
const router = useRouter()
const { echo, onReconnect } = useEcho()

onReconnect(() => game.fetchState())

const mapImageMissing = ref(false)
const selectedBuilding = ref(null)
const adventureOpen = ref(false)
const loading = ref(true)

const user = computed(() => auth.user)
const town = computed(() => game.town)
const townImage = computed(() => (town.value ? townImageFor(town.value.name) : null))

const adventureSpot = computed(() => {
  const spot = adventureSpotFor(town.value?.name)

  return { x: toPercent(spot.x), y: toPercent(spot.y) }
})

let townChannelName = null

function subscribeToTown(townId) {
  townChannelName = `town.${townId}`
  echo.private(townChannelName).listen('.town.updated', () => {
    game.fetchState()
  })
}

onMounted(async () => {
  try {
    await Promise.all([game.fetchState(), auth.user ? Promise.resolve() : auth.fetchUser()])

    if (!game.hasJoined) {
      router.replace({ name: 'play' })
      return
    }

    if (!game.town) {
      router.replace({ name: 'kingdom' })
      return
    }

    subscribeToTown(game.town.id)
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  if (townChannelName) {
    echo.leave(townChannelName)
  }
})

function positionOf(building) {
  return { x: toPercent(building.map_x), y: toPercent(building.map_y) }
}

const buildingViews = computed(() =>
  (town.value?.buildings ?? []).map((building) => {
    const rect = rectFor(town.value?.name, building.key)
    const image = buildingImageFor(building.key)

    return { building, hotspot: rect && image ? { rect, image } : null }
  }),
)

const adventureHotspot = computed(() => {
  const rect = rectFor(town.value?.name, 'adventure')

  return rect ? { rect, image: ADVENTURE_IMAGE } : null
})
</script>

<template>
  <div class="relative flex h-screen w-screen items-center justify-center overflow-hidden bg-iron-900">
    <div class="relative max-h-full max-w-full" :style="{ aspectRatio: TOWN_IMAGE_RATIO }">
      <img
        v-if="townImage && !mapImageMissing"
        :src="townImage"
        :alt="`Vue de ${town?.name ?? 'la ville'}`"
        class="h-full w-full select-none object-fill"
        draggable="false"
        @error="mapImageMissing = true"
      />
      <div
        v-else
        class="flex h-full w-full flex-col items-center justify-center gap-2 border border-iron-700 bg-gradient-to-b from-iron-800 to-iron-900 text-center"
      >
        <span class="text-4xl">🏰</span>
        <p class="max-w-xs text-sm text-parchment-100/70">
          Image de ville introuvable pour
          <code class="text-gold-400">{{ town?.name ?? '…' }}</code
          >.
        </p>
      </div>

      <template v-for="view in buildingViews" :key="view.building.id">
        <BuildingHotspot
          v-if="view.hotspot"
          :image="view.hotspot.image"
          :rect="view.hotspot.rect"
          :label="view.building.name"
          :level="view.building.level"
          :selected="selectedBuilding?.id === view.building.id"
          @select="selectedBuilding = view.building"
        />
        <BuildingMarker
          v-else
          :building="view.building"
          :position="positionOf(view.building)"
          :selected="selectedBuilding?.id === view.building.id"
          @select="selectedBuilding = $event"
        />
      </template>

      <BuildingHotspot
        v-if="town && adventureHotspot"
        :image="adventureHotspot.image"
        :rect="adventureHotspot.rect"
        label="S'aventurer"
        accent="red"
        :selected="adventureOpen"
        @select="adventureOpen = true"
      />
      <button
        v-else-if="town"
        type="button"
        class="adventure-marker group absolute -translate-x-1/2 -translate-y-1/2"
        :class="{ 'adventure-marker--selected': adventureOpen }"
        :style="{ left: `${adventureSpot.x}%`, top: `${adventureSpot.y}%` }"
        title="Partir à l'aventure"
        @click="adventureOpen = true"
      >
        <span class="badge">
          <span class="text-2xl leading-none drop-shadow">🐎</span>
        </span>
        <span class="nameplate">Aventure</span>
      </button>
    </div>

    <div class="pointer-events-none absolute inset-0 shadow-[inset_0_0_120px_40px_rgba(0,0,0,0.55)]" />

    <header class="absolute inset-x-0 top-0 z-20 flex items-center justify-between p-4">
      <RouterLink :to="{ name: 'kingdom' }" class="btn-ghost">← Carte du royaume</RouterLink>

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

    <div
      v-if="town"
      class="absolute inset-x-0 bottom-0 z-20 flex flex-wrap items-center justify-center gap-2 p-3"
    >
      <div
        class="flex flex-wrap items-center gap-3 rounded-lg border border-iron-700 bg-iron-900/85 px-4 py-2 shadow-xl backdrop-blur"
      >
        <span
          v-for="resource in town.resources"
          :key="resource.key"
          class="flex items-center gap-1 text-sm text-parchment-100"
          :title="`${resource.name}${resource.capacity ? ` (max ${resource.capacity})` : ''}`"
        >
          {{ resourceIcon(resource.key) }} {{ resource.amount }}
        </span>

        <span class="h-4 w-px bg-iron-700" />

        <span class="flex items-center gap-1 text-sm text-parchment-100" title="Loyauté de la ville">
          ❤️ {{ town.loyalty }}%
        </span>

        <span class="h-4 w-px bg-iron-700" />

        <span
          class="flex items-center gap-1 text-sm"
          :class="game.player?.actions_remaining > 0 ? 'text-gold-400' : 'text-red-400'"
          title="Actions restantes aujourd'hui"
        >
          ⚡ {{ game.player?.actions_remaining ?? 0 }}/{{ game.player?.daily_actions ?? 5 }}
        </span>
      </div>
    </div>

    <div class="pointer-events-none absolute inset-x-0 top-16 z-10 text-center sm:top-20">
      <h1 class="font-heading text-2xl text-gold-400 drop-shadow-lg sm:text-3xl">
        {{ town?.name ?? 'Votre ville' }}
      </h1>
    </div>

    <div
      v-if="loading"
      class="absolute inset-0 z-30 flex items-center justify-center bg-iron-900/70 backdrop-blur-sm"
    >
      <p class="animate-pulse font-heading text-xl text-gold-400">Passage des portes de la ville…</p>
    </div>

    <BuildingPanel
      v-if="selectedBuilding"
      :building="selectedBuilding"
      @close="selectedBuilding = null"
    />

    <AdventurePanel v-if="adventureOpen" @close="adventureOpen = false" />

    <CountryChat />
  </div>
</template>

<style scoped>
.adventure-marker {
  @apply z-10 flex flex-col items-center gap-1 outline-none transition-transform duration-200 ease-out;
}

.adventure-marker .badge {
  @apply flex h-12 w-12 items-center justify-center rounded-full border-2 border-red-400/70 bg-iron-900/80 shadow-lg backdrop-blur-sm transition-all duration-200;
}

.adventure-marker .nameplate {
  @apply whitespace-nowrap rounded-md border border-red-400/50 bg-iron-900/85 px-2 py-0.5 font-heading text-xs text-parchment-100 shadow-md transition-colors duration-200;
}

.adventure-marker:hover,
.adventure-marker:focus-visible,
.adventure-marker--selected {
  transform: translate(-50%, -50%) translateY(-4px) scale(1.1);
}
.adventure-marker:hover .badge,
.adventure-marker:focus-visible .badge,
.adventure-marker--selected .badge {
  @apply border-red-400;
  box-shadow: 0 0 16px 3px rgba(248, 113, 113, 0.4);
}
.adventure-marker:hover .nameplate,
.adventure-marker:focus-visible .nameplate,
.adventure-marker--selected .nameplate {
  @apply border-red-400 text-red-300;
}
</style>
