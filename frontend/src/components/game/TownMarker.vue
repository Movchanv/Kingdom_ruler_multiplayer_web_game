<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  town: { type: Object, required: true },
  position: { type: Object, required: true },
  emblem: { type: String, default: null },
  selected: { type: Boolean, default: false },
})

defineEmits(['select'])

const emblemMissing = ref(false)
const useEmblem = computed(() => Boolean(props.emblem) && !emblemMissing.value)

const markerStyle = computed(() => ({
  left: `${props.position.x}%`,
  top: `${props.position.y}%`,
}))

const isDestroyed = computed(() => props.town.destroyed_at !== null)

const variant = computed(() => {
  if (isDestroyed.value) return 'destroyed'

  return props.town.is_current ? 'current' : 'available'
})

const icon = computed(() => (isDestroyed.value ? '🔥' : '🏘️'))
</script>

<template>
  <button
    type="button"
    class="town-marker group absolute -translate-x-1/2 -translate-y-1/2"
    :class="[
      `town-marker--${variant}`,
      { 'town-marker--selected': selected, 'town-marker--emblem': useEmblem },
    ]"
    :style="markerStyle"
    :title="town.name"
    :disabled="isDestroyed"
    @click="$emit('select', town)"
  >
    <span v-if="town.is_current" class="here-flag">Vous êtes ici</span>

    <img
      v-if="useEmblem"
      :src="emblem"
      :alt="town.name"
      class="emblem"
      draggable="false"
      @error="emblemMissing = true"
    />

    <template v-else>
      <span class="badge">
        <span class="text-xl leading-none drop-shadow">{{ icon }}</span>
      </span>
      <span class="nameplate">{{ town.name }}</span>
    </template>
  </button>
</template>

<style scoped>
.town-marker {
  @apply z-10 flex flex-col items-center gap-1 outline-none transition-transform duration-200 ease-out;
}

.badge {
  @apply flex h-10 w-10 items-center justify-center rounded-full border-2 bg-iron-900/80 shadow-lg backdrop-blur-sm transition-all duration-200;
}

.nameplate {
  @apply whitespace-nowrap rounded-md border px-2 py-0.5 font-heading text-xs shadow-md transition-colors duration-200;
}

.here-flag {
  @apply z-10 whitespace-nowrap rounded-full bg-gold-500 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-iron-900 shadow;
}

.emblem {
  @apply pointer-events-none h-28 w-28 select-none object-contain drop-shadow-lg transition-all duration-200;
}


.town-marker--emblem.town-marker--available .emblem {
  filter: saturate(0.92);
}
.town-marker--emblem.town-marker--available:hover,
.town-marker--emblem.town-marker--available:focus-visible,
.town-marker--emblem.town-marker--available.town-marker--selected {
  transform: translate(-50%, -50%) translateY(-5px) scale(1.08);
}
.town-marker--emblem.town-marker--available:hover .emblem,
.town-marker--emblem.town-marker--available:focus-visible .emblem,
.town-marker--emblem.town-marker--available.town-marker--selected .emblem {
  filter: saturate(1.1) brightness(1.06) drop-shadow(0 0 14px rgba(245, 197, 66, 0.55));
}
.town-marker--emblem.town-marker--current .emblem {
  filter: drop-shadow(0 0 16px rgba(245, 197, 66, 0.5));
}
.town-marker--emblem.town-marker--current:hover,
.town-marker--emblem.town-marker--current.town-marker--selected {
  transform: translate(-50%, -50%) translateY(-5px) scale(1.08);
}
.town-marker--emblem.town-marker--destroyed .emblem {
  filter: grayscale(1) brightness(0.6);
}

.town-marker--available .badge {
  @apply border-parchment-200/70;
}
.town-marker--available .nameplate {
  @apply border-parchment-200/40 bg-iron-900/85 text-parchment-100;
}
.town-marker:not(.town-marker--emblem).town-marker--available:hover,
.town-marker:not(.town-marker--emblem).town-marker--available:focus-visible,
.town-marker:not(.town-marker--emblem).town-marker--available.town-marker--selected {
  transform: translate(-50%, -50%) translateY(-4px) scale(1.1);
}
.town-marker--available:hover .badge,
.town-marker--available:focus-visible .badge,
.town-marker--available.town-marker--selected .badge {
  @apply border-gold-400;
  box-shadow: 0 0 16px 3px rgba(245, 197, 66, 0.35);
}
.town-marker--available:hover .nameplate,
.town-marker--available:focus-visible .nameplate,
.town-marker--available.town-marker--selected .nameplate {
  @apply border-gold-400 text-gold-400;
}

.town-marker--current .badge {
  @apply border-gold-400;
  box-shadow: 0 0 18px 4px rgba(245, 197, 66, 0.45);
}
.town-marker--current .nameplate {
  @apply border-gold-400 bg-gold-500 font-semibold text-iron-900;
}
.town-marker:not(.town-marker--emblem).town-marker--current:hover,
.town-marker:not(.town-marker--emblem).town-marker--current.town-marker--selected {
  transform: translate(-50%, -50%) translateY(-4px) scale(1.1);
}

.town-marker--destroyed {
  @apply cursor-not-allowed;
}
.town-marker:not(.town-marker--emblem).town-marker--destroyed {
  @apply opacity-60 grayscale;
}
.town-marker--destroyed .badge {
  @apply border-iron-700;
}
.town-marker--destroyed .nameplate {
  @apply border-iron-700 bg-iron-900/85 text-parchment-100/60 line-through;
}
</style>
