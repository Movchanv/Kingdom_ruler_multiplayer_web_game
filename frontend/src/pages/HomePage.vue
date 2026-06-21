<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useEcho } from '@/composables/useEcho'

const { echo } = useEcho()
const announcements = ref([])

onMounted(() => {
  echo.channel('lobby').listen('.server.announcement', (payload) => {
    announcements.value.unshift(payload)
  })
})

onUnmounted(() => {
  echo.leaveChannel('lobby')
})
</script>

<template>
  <section class="space-y-6">
    <div>
      <h1 class="text-3xl text-gold-400">Bienvenue dans Medieval Realm</h1>
      <p class="mt-2 text-parchment-100">
        Base technique temps réel : Laravel 11 · Vue 3 · Reverb · Redis · PostgreSQL.
      </p>
    </div>

    <div class="rounded-lg border border-iron-800 bg-iron-800/40 p-4">
      <h2 class="mb-3 text-lg text-gold-400">Annonces en temps réel (canal public « lobby »)</h2>
      <p v-if="!announcements.length" class="text-sm text-iron-700">
        En attente d'un événement diffusé…
      </p>
      <ul v-else class="space-y-2">
        <li
          v-for="(item, index) in announcements"
          :key="index"
          class="rounded border border-iron-700 bg-iron-900 px-3 py-2 text-sm"
        >
          <span class="text-parchment-50">{{ item.message }}</span>
          <span class="ml-2 text-xs text-iron-700">{{ item.at }}</span>
        </li>
      </ul>
    </div>
  </section>
</template>
