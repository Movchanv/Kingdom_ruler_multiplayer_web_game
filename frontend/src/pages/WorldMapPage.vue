<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { useGameStore } from '@/stores/game'
import { gameService } from '@/services/game.service'
import { WORLD_MAP_IMAGE, positionFor } from '@/config/worldMap'
import CountryMarker from '@/components/game/CountryMarker.vue'

const auth = useAuthStore()
const game = useGameStore()
const toast = useToast()

const mapImageMissing = ref(false)
const selectedCountry = ref(null)
const loading = ref(true)
const lastSeason = ref(null)

const user = computed(() => auth.user)

onMounted(async () => {
  try {
    await Promise.all([
      game.fetchCountries(),
      game.fetchState(),
      auth.user ? Promise.resolve() : auth.fetchUser(),
    ])

    if (!game.hasJoined) {
      const { data } = await gameService.getLastSeason()
      lastSeason.value = data.data
    }
  } finally {
    loading.value = false
  }
})

function variantFor(country) {
  if (game.hasJoined) {
    return country.id === game.myCountryId ? 'mine' : 'locked'
  }

  return country.can_join ? 'available' : 'locked'
}

function selectCountry(country) {
  selectedCountry.value = country
}

const selectedVariant = computed(() =>
  selectedCountry.value ? variantFor(selectedCountry.value) : null,
)

async function joinSelected() {
  if (!selectedCountry.value) return

  const name = selectedCountry.value.name

  await game.join(selectedCountry.value.id)
  toast.success(`Bienvenue en ${name} ! Votre aventure commence.`)

  selectedCountry.value = game.countries.find((c) => c.id === selectedCountry.value.id) ?? null
}
</script>

<template>
  <div class="relative flex h-screen w-screen items-center justify-center overflow-hidden bg-iron-900">
    <div class="world-map relative aspect-square max-h-full max-w-full">
      <img
        v-if="!mapImageMissing"
        :src="WORLD_MAP_IMAGE"
        alt="Carte du monde médiéval"
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
          Ajoutez l'image <code class="text-gold-400">world-map.png</code> dans
          <code class="text-gold-400">frontend/public/</code> pour afficher la carte.
        </p>
      </div>

      <CountryMarker
        v-for="country in game.countries"
        :key="country.id"
        :country="country"
        :position="positionFor(country.slug)"
        :variant="variantFor(country)"
        :selected="selectedCountry?.id === country.id"
        @select="selectCountry"
      />
    </div>

    <div class="pointer-events-none absolute inset-0 shadow-[inset_0_0_120px_40px_rgba(0,0,0,0.55)]" />

    <header class="absolute inset-x-0 top-0 z-20 flex items-center justify-between p-4">
      <RouterLink to="/" class="btn-ghost">← Accueil</RouterLink>

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
        <RouterLink v-if="user?.role === 'admin'" :to="{ name: 'admin' }" class="btn-ghost">
          ⚙️ Admin
        </RouterLink>
        <button type="button" class="btn-ghost" @click="auth.logout()">Déconnexion</button>
      </div>
    </header>

    <div class="pointer-events-none absolute inset-x-0 top-16 z-10 text-center sm:top-20">
      <h1 class="font-heading text-2xl text-gold-400 drop-shadow-lg sm:text-3xl">
        {{ game.hasJoined ? 'Votre royaume vous attend' : 'Choisissez votre royaume' }}
      </h1>

      <RouterLink
        v-if="!game.hasJoined && lastSeason"
        :to="{ name: 'season-results', params: { id: lastSeason.id } }"
        class="pointer-events-auto mt-3 inline-flex items-center gap-2 rounded-md border border-gold-400/50 bg-iron-900/85 px-4 py-2 text-sm text-parchment-100 shadow-lg backdrop-blur transition hover:border-gold-400"
      >
        🏁 Votre saison « {{ lastSeason.name }} » est terminée —
        <span class="text-gold-400">voir le classement</span>
      </RouterLink>
    </div>

    <div
      v-if="loading"
      class="absolute inset-0 z-30 flex items-center justify-center bg-iron-900/70 backdrop-blur-sm"
    >
      <p class="animate-pulse font-heading text-xl text-gold-400">Déploiement de la carte…</p>
    </div>

    <transition name="panel">
      <aside
        v-if="selectedCountry"
        class="absolute inset-x-0 bottom-0 z-20 mx-auto w-full max-w-lg p-4"
      >
        <div class="rounded-lg border border-gold-400/40 bg-iron-900/90 p-5 shadow-2xl backdrop-blur">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="font-heading text-xl text-gold-400">{{ selectedCountry.name }}</h2>
              <p class="mt-1 text-sm text-parchment-100/80">
                <template v-if="selectedCountry.season">
                  {{ selectedCountry.season.name }} —
                  {{ selectedCountry.players_count }} joueur(s)
                </template>
                <template v-else>Aucune saison en cours.</template>
              </p>
            </div>
            <button
              type="button"
              class="text-parchment-100/50 transition hover:text-parchment-100"
              aria-label="Fermer"
              @click="selectedCountry = null"
            >
              ✕
            </button>
          </div>

          <div class="mt-4">
            <button
              v-if="!game.hasJoined && selectedCountry.can_join"
              type="button"
              class="btn-play w-full !px-6 !py-3 !text-lg"
              :disabled="game.joining"
              @click="joinSelected"
            >
              {{ game.joining ? 'En route…' : `Rejoindre ${selectedCountry.name}` }}
            </button>

            <p v-else-if="!game.hasJoined" class="text-center text-sm text-parchment-100/60">
              Ce royaume n'accueille pas de nouveaux seigneurs pour le moment.
            </p>

            <div v-else-if="selectedVariant === 'mine'" class="space-y-2 text-center">
              <p class="text-sm text-parchment-100/80">
                👑 Vous servez ce royaume<template v-if="game.town"> — vous êtes à
                  <span class="text-gold-400">{{ game.town.name }}</span></template>.
              </p>
              <RouterLink :to="{ name: 'kingdom' }" class="btn-gold w-full">
                Entrer dans le royaume
              </RouterLink>
            </div>

            <p v-else class="text-center text-sm text-parchment-100/60">
              Vous servez déjà un autre royaume. Terminez votre saison avant de changer d'allégeance.
            </p>
          </div>
        </div>
      </aside>
    </transition>
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
