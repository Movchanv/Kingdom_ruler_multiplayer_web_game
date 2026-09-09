<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="relative flex min-h-screen flex-col items-center justify-center px-4">
    <RouterLink :to="{ name: 'home' }" class="btn-ghost absolute left-4 top-4">
      ← Accueil
    </RouterLink>

    <RouterLink :to="{ name: 'home' }" class="mb-8 text-2xl font-heading text-gold-400">
      Les Seigneuries
    </RouterLink>

    <div class="w-full max-w-sm rounded-lg border border-iron-800 bg-iron-800/50 p-6 shadow-xl">
      <slot />
    </div>
  </div>
</template>
