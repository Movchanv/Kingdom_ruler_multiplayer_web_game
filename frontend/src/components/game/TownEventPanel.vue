<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { resourceIcon, resourceLabel } from '@/config/townBuildings'

const props = defineProps({
  pendingEvents: { type: Array, default: () => [] },
  history: { type: Array, default: () => [] },
  resources: { type: Array, default: () => [] },
  loyalty: { type: Number, default: 0 },
})

const now = ref(Date.now())
let timer = null

onMounted(() => {
  timer = setInterval(() => {
    now.value = Date.now()
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const openedId = ref(null)
const historyOpen = ref(false)

const amounts = computed(() => {
  const map = { loyalty: props.loyalty }
  props.resources.forEach((resource) => {
    map[resource.key] = resource.amount
  })
  return map
})

function remaining(event) {
  return Math.max(0, new Date(event.resolves_at).getTime() - now.value)
}

function countdown(event) {
  const ms = remaining(event)
  const totalMinutes = Math.floor(ms / 60000)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60

  if (hours > 0) return hours + ' h ' + String(minutes).padStart(2, '0')
  if (totalMinutes > 0) return minutes + ' min'

  return Math.floor(ms / 1000) + ' s'
}

function urgency(event) {
  const minutes = remaining(event) / 60000
  if (minutes <= 15) return 'critical'
  if (minutes <= 60) return 'soon'
  return 'calm'
}

function requirementIsMet(event) {
  return Object.entries(event.requirement ?? {}).every(
    ([key, needed]) => (amounts.value[key] ?? 0) >= needed,
  )
}

const resourceNames = computed(() =>
  Object.fromEntries(props.resources.map((resource) => [resource.key, resource.name])),
)

function label(key) {
  return resourceNames.value[key] ?? resourceLabel(key)
}

function icon(key) {
  if (key === 'loyalty') return '❤️'
  if (key === 'free_action') return '✨'
  return resourceIcon(key)
}

function effectsText(effects) {
  return Object.entries(effects ?? {})
    .map(([key, value]) => icon(key) + ' ' + (value > 0 ? '+' : '') + value)
    .join('  ')
}

function outcomeText(entry) {
  const applied = (entry.outcome ?? [])
    .filter((effect) => effect.delta !== 0)
    .map((effect) => icon(effect.key) + ' ' + (effect.delta > 0 ? '+' : '') + effect.delta)
    .join('  ')

  return applied || 'aucun effet'
}

const opened = computed(
  () => props.pendingEvents.find((event) => event.id === openedId.value) ?? null,
)

function toggle(event) {
  openedId.value = openedId.value === event.id ? null : event.id
}
</script>

<template>
  <div class="absolute right-3 top-20 z-20 flex flex-col items-end gap-2">
    <button
      v-for="event in pendingEvents"
      :key="event.id"
      type="button"
      class="event-badge"
      :class="['event-badge--' + urgency(event), { 'event-badge--open': openedId === event.id }]"
      :title="event.name"
      @click="toggle(event)"
    >
      <span class="text-2xl leading-none">{{ event.icon }}</span>
      <span class="event-badge__time">{{ countdown(event) }}</span>
    </button>

    <button
      v-if="history.length"
      type="button"
      class="rounded-md border border-iron-700 bg-iron-900/85 px-2 py-1 text-xs text-parchment-100/70 backdrop-blur hover:text-parchment-100"
      @click="historyOpen = !historyOpen"
    >
      📜 Historique ({{ history.length }})
    </button>

    <div
      v-if="opened"
      class="w-72 rounded-lg border border-iron-700 bg-iron-900/95 p-4 text-sm shadow-xl backdrop-blur"
    >
      <div class="flex items-start gap-2">
        <span class="text-2xl leading-none">{{ opened.icon }}</span>
        <div>
          <h3 class="font-heading text-base text-gold-400">{{ opened.name }}</h3>
          <p class="text-xs text-parchment-100/60">Dans {{ countdown(opened) }}</p>
        </div>
      </div>

      <p v-if="opened.description" class="mt-2 text-xs text-parchment-100/80">
        {{ opened.description }}
      </p>

      <div v-if="Object.keys(opened.requirement ?? {}).length" class="mt-3">
        <p class="text-xs uppercase tracking-wide text-parchment-100/50">Il vous faut</p>
        <ul class="mt-1 space-y-1">
          <li
            v-for="(needed, key) in opened.requirement"
            :key="key"
            class="flex items-center justify-between text-xs"
            :class="(amounts[key] ?? 0) >= needed ? 'text-green-300' : 'text-red-300'"
          >
            <span>{{ icon(key) }} {{ label(key) }}</span>
            <span>{{ amounts[key] ?? 0 }} / {{ needed }}</span>
          </li>
        </ul>
      </div>

      <div class="mt-3 space-y-1 text-xs">
        <p class="text-green-300">
          <span class="text-parchment-100/50">Victoire :</span>
          {{ effectsText(opened.success_effects) || 'rien' }}
        </p>
        <p class="text-red-300">
          <span class="text-parchment-100/50">Défaite :</span>
          {{ effectsText(opened.failure_effects) || 'rien' }}
        </p>
      </div>

      <p
        class="mt-3 rounded px-2 py-1 text-center text-xs"
        :class="
          requirementIsMet(opened) ? 'bg-green-400/10 text-green-300' : 'bg-red-400/10 text-red-300'
        "
      >
        {{ requirementIsMet(opened) ? 'La ville est prête' : "La ville n'est pas prête" }}
      </p>
    </div>

    <div
      v-if="historyOpen && history.length"
      class="w-72 rounded-lg border border-iron-700 bg-iron-900/95 p-4 text-sm shadow-xl backdrop-blur"
    >
      <h3 class="font-heading text-base text-gold-400">📜 Événements passés</h3>
      <ul class="mt-2 space-y-2">
        <li
          v-for="entry in history"
          :key="entry.id"
          class="border-b border-iron-800 pb-2 last:border-0"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs text-parchment-100">{{ entry.icon }} {{ entry.name }}</span>
            <span
              class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
              :class="
                entry.status === 'succeeded'
                  ? 'bg-green-400/15 text-green-300'
                  : 'bg-red-400/15 text-red-300'
              "
            >
              {{ entry.status === 'succeeded' ? 'Victoire' : 'Défaite' }}
            </span>
          </div>
          <p class="mt-0.5 text-[11px] text-parchment-100/60">{{ outcomeText(entry) }}</p>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.event-badge {
  position: relative;
  display: flex;
  height: 3.5rem;
  width: 3.5rem;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border-width: 2px;
  backdrop-filter: blur(4px);
  transition: transform 0.15s ease;
}
.event-badge:hover,
.event-badge--open {
  transform: scale(1.08);
}
.event-badge__time {
  font-size: 0.6rem;
  line-height: 1;
  margin-top: 0.1rem;
  font-variant-numeric: tabular-nums;
}
.event-badge--calm {
  border-color: rgb(120 113 108 / 0.8);
  background-color: rgb(28 25 23 / 0.85);
  color: rgb(231 229 228);
}
.event-badge--soon {
  border-color: rgb(251 191 36 / 0.8);
  background-color: rgb(120 53 15 / 0.6);
  color: rgb(253 230 138);
}
.event-badge--critical {
  border-color: rgb(248 113 113 / 0.9);
  background-color: rgb(127 29 29 / 0.7);
  color: rgb(254 202 202);
  animation: pulse-threat 1.6s ease-in-out infinite;
}
@keyframes pulse-threat {
  0%,
  100% {
    box-shadow: 0 0 0 0 rgb(248 113 113 / 0.5);
  }
  50% {
    box-shadow: 0 0 0 8px rgb(248 113 113 / 0);
  }
}
</style>
