<script setup>
import { computed } from 'vue'
import { buildingMeta } from '@/config/townBuildings'

const props = defineProps({
  building: { type: Object, required: true },
  position: { type: Object, required: true }, // { x, y } en % de l'image
  selected: { type: Boolean, default: false },
})

defineEmits(['select'])

const meta = computed(() => buildingMeta(props.building.key))

const markerStyle = computed(() => ({
  left: `${props.position.x}%`,
  top: `${props.position.y}%`,
}))
</script>

<template>
  <button
    type="button"
    class="building-marker group absolute -translate-x-1/2 -translate-y-1/2"
    :class="{ 'building-marker--selected': selected }"
    :style="markerStyle"
    :title="building.name"
    @click="$emit('select', building)"
  >
    <span class="badge">
      <span class="text-2xl leading-none drop-shadow">{{ meta.icon }}</span>
      <span class="level">{{ building.level }}</span>
    </span>
    <span class="nameplate">{{ building.name }}</span>
  </button>
</template>

<style scoped>
.building-marker {
  @apply z-10 flex flex-col items-center gap-1 outline-none transition-transform duration-200 ease-out;
}

.badge {
  @apply relative flex h-12 w-12 items-center justify-center rounded-full border-2 border-parchment-200/70 bg-iron-900/80 shadow-lg backdrop-blur-sm transition-all duration-200;
}

.level {
  @apply absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border border-gold-400 bg-iron-900 text-[10px] font-bold text-gold-400;
}

.nameplate {
  @apply whitespace-nowrap rounded-md border border-parchment-200/40 bg-iron-900/85 px-2 py-0.5 font-heading text-xs text-parchment-100 shadow-md transition-colors duration-200;
}

.building-marker:hover,
.building-marker:focus-visible,
.building-marker--selected {
  transform: translate(-50%, -50%) translateY(-4px) scale(1.1);
}
.building-marker:hover .badge,
.building-marker:focus-visible .badge,
.building-marker--selected .badge {
  @apply border-gold-400;
  box-shadow: 0 0 16px 3px rgba(245, 197, 66, 0.4);
}
.building-marker:hover .nameplate,
.building-marker:focus-visible .nameplate,
.building-marker--selected .nameplate {
  @apply border-gold-400 text-gold-400;
}
</style>
