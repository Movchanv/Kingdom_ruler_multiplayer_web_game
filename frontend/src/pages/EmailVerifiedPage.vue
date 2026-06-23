<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="space-y-5 text-center">
    <div class="text-5xl">✅</div>
    <h1 class="text-xl text-gold-400">Email vérifié</h1>
    <p class="text-sm text-parchment-100/80">
      Ton adresse email a bien été confirmée. Tu peux maintenant te connecter et jouer.
    </p>
    <RouterLink to="/login" class="btn-primary w-full">Se connecter</RouterLink>
  </div>
</template>
