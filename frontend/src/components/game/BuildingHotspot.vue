<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  image: { type: String, required: true },
  rect: { type: Object, required: true },
  label: { type: String, default: '' },
  level: { type: Number, default: null },
  selected: { type: Boolean, default: false },
  accent: { type: String, default: 'gold' },
})

const emit = defineEmits(['select'])

const missing = ref(false)

const style = computed(() => ({
  left: `${props.rect.x}%`,
  top: `${props.rect.y}%`,
  width: `${props.rect.w}%`,
  height: `${props.rect.h}%`,
}))
</script>

<template>
  <button
    v-if="!missing"
    type="button"
    class="hotspot group absolute"
    :class="[`hotspot--${accent}`, { 'hotspot--selected': selected }]"
    :style="style"
    :title="label"
    @click="emit('select')"
  >
    <img
      :src="image"
      :alt="label"
      class="crop pointer-events-none h-full w-full select-none object-fill"
      draggable="false"
      @error="missing = true"
    />

    <span v-if="level !== null" class="level">{{ level }}</span>
    <span v-if="label" class="name">{{ label }}</span>
  </button>
</template>

<style scoped>
.hotspot {
  @apply z-10 cursor-pointer border-0 bg-transparent p-0 outline-none;
  transition: transform 0.2s ease;
}

.crop {
  opacity: 0;
  transition:
    opacity 0.2s ease,
    filter 0.2s ease;
}

.hotspot:hover,
.hotspot:focus-visible,
.hotspot--selected {
  z-index: 30;
  transform: translateY(-6px) scale(1.03);
}

.hotspot:hover .crop,
.hotspot:focus-visible .crop,
.hotspot--selected .crop {
  opacity: 1;
}

.hotspot--gold:hover .crop,
.hotspot--gold:focus-visible .crop,
.hotspot--gold.hotspot--selected .crop {
  filter: brightness(1.12) saturate(1.1) drop-shadow(0 8px 14px rgba(0, 0, 0, 0.55))
    drop-shadow(0 0 10px rgba(245, 197, 66, 0.6));
}

.hotspot--red:hover .crop,
.hotspot--red:focus-visible .crop,
.hotspot--red.hotspot--selected .crop {
  filter: brightness(1.12) saturate(1.15) drop-shadow(0 8px 14px rgba(0, 0, 0, 0.55))
    drop-shadow(0 0 10px rgba(248, 113, 113, 0.65));
}

.level {
  @apply pointer-events-none absolute -top-2 right-1 flex h-6 w-6 items-center justify-center
    rounded-full border-2 border-gold-400 bg-iron-900 text-xs font-bold text-gold-400 opacity-0
    shadow-lg transition-opacity duration-200;
}

.name {
  @apply pointer-events-none absolute -bottom-3 left-1/2 -translate-x-1/2 whitespace-nowrap
    rounded-md border border-gold-400/60 bg-iron-900/90 px-2 py-0.5 font-heading text-xs
    text-gold-400 opacity-0 shadow-lg transition-opacity duration-200;
}

.hotspot:hover .level,
.hotspot:focus-visible .level,
.hotspot--selected .level,
.hotspot:hover .name,
.hotspot:focus-visible .name,
.hotspot--selected .name {
  opacity: 1;
}
</style>
