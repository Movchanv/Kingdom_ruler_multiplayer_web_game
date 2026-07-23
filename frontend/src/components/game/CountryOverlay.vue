<script setup>
import { ref } from 'vue'

const props = defineProps({
  country: { type: Object, required: true },
  image: { type: String, required: true },
  variant: { type: String, default: 'available' },
  selected: { type: Boolean, default: false },
})

const emit = defineEmits(['select'])

const missing = ref(false)
</script>

<template>
  <button
    v-if="!missing"
    type="button"
    class="country-overlay absolute inset-0 h-full w-full outline-none"
    :class="[`country-overlay--${variant}`, { 'country-overlay--selected': selected }]"
    :title="country.name"
    :disabled="variant === 'locked'"
    @click="emit('select', country)"
  >
    <img
      :src="image"
      :alt="country.name"
      class="pointer-events-none h-full w-full select-none object-fill"
      draggable="false"
      @error="missing = true"
    />
  </button>
</template>

<style scoped>
.country-overlay {
  @apply cursor-pointer transition-transform duration-200 ease-out;
}
.country-overlay img {
  transition:
    filter 0.25s ease,
    transform 0.25s ease;
}

.country-overlay--available img {
  filter: grayscale(0.35) brightness(0.9);
}
.country-overlay--available:hover img,
.country-overlay--available:focus-visible img,
.country-overlay--available.country-overlay--selected img {
  filter: grayscale(0) brightness(1.15) saturate(1.15)
    drop-shadow(0 0 14px rgba(245, 197, 66, 0.55));
  transform: scale(1.02);
}

.country-overlay--mine {
  cursor: default;
}
.country-overlay--mine img {
  filter: brightness(1.1) saturate(1.1) drop-shadow(0 0 12px rgba(245, 197, 66, 0.5));
}

.country-overlay--locked {
  @apply cursor-not-allowed;
}
.country-overlay--locked img {
  filter: grayscale(1) brightness(0.6);
}
</style>
