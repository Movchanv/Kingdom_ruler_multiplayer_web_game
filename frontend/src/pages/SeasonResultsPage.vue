<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { gameService } from '@/services/game.service'

const route = useRoute()
const auth = useAuthStore()

const loading = ref(true)
const season = ref(null)

const REASONS = {
  all_towns_destroyed: 'Toutes les villes du royaume sont tombées.',
  admin_ended: 'La saison a été clôturée par les administrateurs.',
}

const ranking = computed(() => season.value?.results?.ranking ?? [])
const podium = computed(() => ranking.value.slice(0, 3))
const rest = computed(() => ranking.value.slice(3))
const reasonLabel = computed(() => REASONS[season.value?.results?.reason] ?? null)

const myRank = computed(
  () => ranking.value.find((entry) => entry.user_id === auth.user?.id) ?? null,
)

const MEDALS = ['🥇', '🥈', '🥉']

onMounted(async () => {
  try {
    if (!auth.user) {
      await auth.fetchUser()
    }

    const { data } = await gameService.getSeasonResults(route.params.id)
    season.value = data.data
  } finally {
    loading.value = false
  }
})

function nameOf(entry) {
  return entry.username ?? 'Chevalier anonyme'
}

function endedDate() {
  if (!season.value?.ended_at) return ''

  return new Date(season.value.ended_at).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}
</script>

<template>
  <div class="min-h-screen w-full bg-iron-900 px-4 py-6">
    <div class="mx-auto max-w-2xl">
      <header class="flex items-center justify-between">
        <RouterLink to="/play" class="btn-ghost">← Carte du monde</RouterLink>
        <button type="button" class="btn-ghost" @click="auth.logout()">Déconnexion</button>
      </header>

      <p v-if="loading" class="mt-16 animate-pulse text-center text-parchment-100/60">
        Les chroniqueurs compilent les archives…
      </p>

      <template v-else-if="season">
        <div class="mt-8 text-center">
          <p class="text-sm uppercase tracking-widest text-parchment-100/50">Fin de saison</p>
          <h1 class="mt-1 font-heading text-3xl text-gold-400">{{ season.name }}</h1>
          <p class="mt-2 text-sm text-parchment-100/70">
            <template v-if="season.country">{{ season.country }} — </template>{{ endedDate() }}
          </p>
          <p v-if="reasonLabel" class="mt-1 text-sm italic text-red-400/90">⚔️ {{ reasonLabel }}</p>
        </div>

        <div
          v-if="myRank"
          class="mt-6 rounded-lg border border-gold-400/50 bg-gold-500/10 p-4 text-center"
        >
          <p class="text-sm text-parchment-100">
            Vous terminez <span class="font-heading text-lg text-gold-400">#{{ myRank.rank }}</span>
            avec <span class="text-gold-400">{{ myRank.xp }} XP</span>
            ({{ myRank.actions }} actions). Votre expérience et votre titre vous accompagnent dans
            votre prochain royaume !
          </p>
        </div>

        <div v-if="podium.length" class="mt-8 grid grid-cols-3 items-end gap-3">
          <div
            v-for="(entry, index) in podium"
            :key="entry.rank"
            class="rounded-lg border p-4 text-center"
            :class="[
              index === 0
                ? 'order-2 border-gold-400 bg-gold-500/10 pb-8'
                : index === 1
                  ? 'order-1 border-parchment-200/40 bg-iron-800/60'
                  : 'order-3 border-amber-700/50 bg-iron-800/60',
              entry.user_id === auth.user?.id ? 'ring-2 ring-gold-400' : '',
            ]"
          >
            <p class="text-3xl">{{ MEDALS[index] }}</p>
            <p class="mt-1 truncate font-heading text-sm text-parchment-100">{{ nameOf(entry) }}</p>
            <p class="text-xs text-gold-400">{{ entry.xp }} XP</p>
            <p class="text-xs text-parchment-100/50">{{ entry.actions }} actions</p>
          </div>
        </div>

        <p v-else class="mt-8 text-center text-sm text-parchment-100/60">
          Aucun participant classé pour cette saison.
        </p>

        <div v-if="rest.length" class="mt-6 overflow-hidden rounded-lg border border-iron-700">
          <table class="w-full text-sm">
            <thead class="bg-iron-800/80 text-left text-xs uppercase text-parchment-100/60">
              <tr>
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Seigneur</th>
                <th class="px-4 py-2 text-right">XP</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="entry in rest"
                :key="entry.rank"
                class="border-t border-iron-800"
                :class="entry.user_id === auth.user?.id ? 'bg-gold-500/10' : ''"
              >
                <td class="px-4 py-2 text-parchment-100/70">{{ entry.rank }}</td>
                <td class="px-4 py-2 text-parchment-100">{{ nameOf(entry) }}</td>
                <td class="px-4 py-2 text-right text-gold-400">{{ entry.xp }}</td>
                <td class="px-4 py-2 text-right text-parchment-100/70">{{ entry.actions }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-8 text-center">
          <RouterLink to="/play" class="btn-play !px-8 !py-3 !text-lg">
            Rejoindre un nouveau royaume
          </RouterLink>
        </div>
      </template>

      <p v-else class="mt-16 text-center text-parchment-100/60">Saison introuvable.</p>
    </div>
  </div>
</template>
