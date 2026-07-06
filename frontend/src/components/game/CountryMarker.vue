<script setup>
import { computed } from 'vue'

const props = defineProps({
  country: { type: Object, required: true },
  position: { type: Object, required: true }, // { x, y } en % de la carte
  /** 'available' | 'mine' | 'locked' */
  variant: { type: String, default: 'available' },
  selected: { type: Boolean, default: false },
})

defineEmits(['select'])

const markerStyle = computed(() => ({
  left: `${props.position.x}%`,
  top: `${props.position.y}%`,
}))

const icon = computed(() => (props.variant === 'mine' ? '👑' : '🏰'))
</script>

<template>
  <button
    type="button"
    class="country-marker group absolute -translate-x-1/2 -translate-y-1/2"
    :class="[`country-marker--${variant}`, { 'country-marker--selected': selected }]"
    :style="markerStyle"
    :title="country.name"
    @click="$emit('select', country)"
  >
    <span class="badge">
      <span class="text-2xl leading-none drop-shadow">{{ icon }}</span>
    </span>
    <span class="nameplate">{{ country.name }}</span>
  </button>
</template>

<style scoped>
.country-marker {
  @apply z-10 flex flex-col items-center gap-1 outline-none transition-transform duration-200 ease-out;
}

.badge {
  @apply flex h-12 w-12 items-center justify-center rounded-full border-2 bg-iron-900/80 shadow-lg backdrop-blur-sm transition-all duration-200;
}

.nameplate {
  @apply whitespace-nowrap rounded-md border px-2.5 py-0.5 font-heading text-sm shadow-md transition-colors duration-200;
}

/* Pays disponible : doré, s'anime au survol (préfigure la surbrillance des frontières). */
.country-marker--available .badge {
  @apply border-gold-400/80;
}
.country-marker--available .nameplate {
  @apply border-gold-400/50 bg-iron-900/85 text-parchment-100;
}
.country-marker--available:hover,
.country-marker--available:focus-visible,
.country-marker--available.country-marker--selected {
  transform: translate(-50%, -50%) translateY(-4px) scale(1.08);
}
.country-marker--available:hover .badge,
.country-marker--available:focus-visible .badge,
.country-marker--available.country-marker--selected .badge {
  @apply border-gold-400 shadow-gold-400/40;
  box-shadow: 0 0 18px 4px rgba(245, 197, 66, 0.35);
}
.country-marker--available:hover .nameplate,
.country-marker--available:focus-visible .nameplate,
.country-marker--available.country-marker--selected .nameplate {
  @apply border-gold-400 text-gold-400;
}

/* Mon royaume : couronne + halo permanent. */
.country-marker--mine .badge {
  @apply border-gold-400;
  box-shadow: 0 0 20px 5px rgba(245, 197, 66, 0.45);
  animation: marker-pulse 2.4s ease-in-out infinite;
}
.country-marker--mine .nameplate {
  @apply border-gold-400 bg-gold-500 font-semibold text-iron-900;
}
.country-marker--mine:hover,
.country-marker--mine.country-marker--selected {
  transform: translate(-50%, -50%) translateY(-4px) scale(1.08);
}

/* Pays verrouillé (le joueur appartient déjà à un autre royaume) : grisé. */
.country-marker--locked {
  @apply cursor-not-allowed opacity-55 grayscale;
}
.country-marker--locked .badge {
  @apply border-iron-700;
}
.country-marker--locked .nameplate {
  @apply border-iron-700 bg-iron-900/85 text-parchment-100/70;
}

@keyframes marker-pulse {
  0%,
  100% {
    box-shadow: 0 0 14px 3px rgba(245, 197, 66, 0.35);
  }
  50% {
    box-shadow: 0 0 24px 7px rgba(245, 197, 66, 0.55);
  }
}
</style>
