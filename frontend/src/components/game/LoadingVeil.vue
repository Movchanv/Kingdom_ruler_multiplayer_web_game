<script setup>
defineProps({
  show: { type: Boolean, default: false },
  label: { type: String, required: true },
  /** Avancement du préchargement, de 0 à 1. `null` = barre masquée. */
  progress: { type: Number, default: null },
})
</script>

<template>
  <transition name="veil">
    <div
      v-if="show"
      class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-5 bg-iron-900"
      role="status"
      aria-live="polite"
    >
      <p class="animate-pulse font-heading text-xl text-gold-400">{{ label }}</p>

      <div
        v-if="progress !== null"
        class="h-1.5 w-56 overflow-hidden rounded-full border border-iron-700 bg-iron-800"
      >
        <div
          class="h-full rounded-full bg-gold-400 transition-[width] duration-300 ease-out"
          :style="{ width: `${Math.round(progress * 100)}%` }"
        />
      </div>
    </div>
  </transition>
</template>

<style scoped>
/* Le voile est opaque : il ne s'efface qu'une fois la carte prête à peindre. */
.veil-leave-active {
  transition: opacity 0.45s ease;
}
.veil-leave-to {
  opacity: 0;
}
</style>
