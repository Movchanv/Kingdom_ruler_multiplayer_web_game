<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="py-16 text-center">
    <h1 class="text-5xl text-gold-400">404</h1>
    <p class="mt-2 text-parchment-100">Cette contrée n'existe pas.</p>
    <RouterLink to="/" class="btn-primary mt-6 inline-flex">Retour au royaume</RouterLink>
  </section>
</template>
