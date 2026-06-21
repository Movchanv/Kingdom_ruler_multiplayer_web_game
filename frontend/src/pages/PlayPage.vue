<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const user = computed(() => auth.user)
</script>

<template>
  <section class="space-y-4">
    <h1 class="text-2xl text-gold-400">Salle de jeu</h1>
    <p class="text-parchment-100">
      Bonjour <span class="text-gold-400">{{ user?.name ?? 'aventurier' }}</span>, cet espace
      authentifié accueillera les fonctionnalités du jeu.
    </p>
  </section>
</template>
