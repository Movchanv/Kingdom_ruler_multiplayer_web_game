<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { resourceIcon, resourceLabel } from '@/config/townBuildings'

const props = defineProps({
  pendingEvents: { type: Array, default: () => [] },
  history: { type: Array, default: () => [] },
  resources: { type: Array, default: () => [] },
  loyalty: { type: Number, default: 0 },
})

const HISTORY_SHOWN = 4
const DISMISSED_KEY = 'town-events-dismissed'

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

function readDismissed() {
  try {
    const raw = localStorage.getItem(DISMISSED_KEY)
    return Array.isArray(JSON.parse(raw)) ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

const dismissed = ref(readDismissed())

function dismiss(entry) {
  if (!dismissed.value.includes(entry.id)) {
    dismissed.value = [...dismissed.value, entry.id]
  }

  if (openedId.value === entry.id) {
    openedId.value = null
  }

  try {
    localStorage.setItem(DISMISSED_KEY, JSON.stringify(dismissed.value.slice(-200)))
  } catch {
    // Le stockage local peut être indisponible : la fermeture reste valable
    // pour la session en cours.
  }
}

const resolvedEvents = computed(() =>
  props.history.filter((entry) => !dismissed.value.includes(entry.id)).slice(0, HISTORY_SHOWN),
)

const amounts = computed(() => {
  const map = { loyalty: props.loyalty }
  props.resources.forEach((resource) => {
    map[resource.key] = resource.amount
  })
  return map
})

const resourceNames = computed(() =>
  Object.fromEntries(props.resources.map((resource) => [resource.key, resource.name])),
)

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
  if (ms > 0) return Math.floor(ms / 1000) + ' s'

  return 'Résolution…'
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

function resolvedAgo(entry) {
  if (!entry.resolved_at) return ''

  const minutes = Math.floor((now.value - new Date(entry.resolved_at).getTime()) / 60000)

  if (minutes < 1) return "à l'instant"
  if (minutes < 60) return 'il y a ' + minutes + ' min'

  const hours = Math.floor(minutes / 60)
  if (hours < 24) return 'il y a ' + hours + ' h'

  return 'il y a ' + Math.floor(hours / 24) + ' j'
}

const openedPending = computed(
  () => props.pendingEvents.find((event) => event.id === openedId.value) ?? null,
)

const openedResolved = computed(
  () => resolvedEvents.value.find((entry) => entry.id === openedId.value) ?? null,
)

function toggle(entry) {
  openedId.value = openedId.value === entry.id ? null : entry.id
}
</script>

<template>
  <div class="absolute right-3 top-20 z-20 flex flex-col items-end gap-2">
    <button
      v-for="event in pendingEvents"
      :key="'pending-' + event.id"
      type="button"
      class="event-badge"
      :class="['event-badge--' + urgency(event), { 'event-badge--open': openedId === event.id }]"
      :title="event.name"
      @click="toggle(event)"
    >
      <span class="text-2xl leading-none">{{ event.icon }}</span>
      <span class="event-badge__time">{{ countdown(event) }}</span>
    </button>

    <div
      v-for="entry in resolvedEvents"
      :key="'resolved-' + entry.id"
      class="relative"
    >
      <button
        type="button"
        class="event-badge"
        :class="[
          entry.status === 'succeeded' ? 'event-badge--won' : 'event-badge--lost',
          { 'event-badge--open': openedId === entry.id },
        ]"
        :title="entry.name"
        @click="toggle(entry)"
      >
        <span class="text-2xl leading-none">{{ entry.icon }}</span>
        <span class="event-badge__time">
          {{ entry.status === 'succeeded' ? '✅' : '❌' }}
        </span>
      </button>

      <button
        type="button"
        class="event-badge__close"
        title="Retirer de la liste"
        @click.stop="dismiss(entry)"
      >
        ✕
      </button>
    </div>

    <div
      v-if="openedPending"
      class="w-72 rounded-lg border border-iron-700 bg-iron-900/95 p-4 text-sm shadow-xl backdrop-blur"
    >
      <div class="flex items-start gap-2">
        <span class="text-2xl leading-none">{{ openedPending.icon }}</span>
        <div>
          <h3 class="font-heading text-base text-gold-400">{{ openedPending.name }}</h3>
          <p class="text-xs text-parchment-100/60">Dans {{ countdown(openedPending) }}</p>
        </div>
      </div>

      <p v-if="openedPending.description" class="mt-2 text-xs text-parchment-100/80">
        {{ openedPending.description }}
      </p>

      <div v-if="Object.keys(openedPending.requirement ?? {}).length" class="mt-3">
        <p class="text-xs uppercase tracking-wide text-parchment-100/50">Il vous faut</p>
        <ul class="mt-1 space-y-1">
          <li
            v-for="(needed, key) in openedPending.requirement"
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
          {{ effectsText(openedPending.success_effects) || 'rien' }}
        </p>
        <p class="text-red-300">
          <span class="text-parchment-100/50">Défaite :</span>
          {{ effectsText(openedPending.failure_effects) || 'rien' }}
        </p>
      </div>

      <p
        class="mt-3 rounded px-2 py-1 text-center text-xs"
        :class="
          requirementIsMet(openedPending)
            ? 'bg-green-400/10 text-green-300'
            : 'bg-red-400/10 text-red-300'
        "
      >
        {{ requirementIsMet(openedPending) ? 'La ville est prête' : "La ville n'est pas prête" }}
      </p>
    </div>

    <div
      v-if="openedResolved"
      class="w-72 rounded-lg border p-4 text-sm shadow-xl backdrop-blur"
      :class="
        openedResolved.status === 'succeeded'
          ? 'border-green-400/40 bg-iron-900/95'
          : 'border-red-400/40 bg-iron-900/95'
      "
    >
      <div class="flex items-start gap-2">
        <span class="text-2xl leading-none">{{ openedResolved.icon }}</span>
        <div class="flex-1">
          <h3 class="font-heading text-base text-gold-400">{{ openedResolved.name }}</h3>
          <p class="text-xs text-parchment-100/60">{{ resolvedAgo(openedResolved) }}</p>
        </div>
        <button
          type="button"
          class="text-parchment-100/50 hover:text-parchment-100"
          title="Retirer de la liste"
          @click="dismiss(openedResolved)"
        >
          ✕
        </button>
      </div>

      <p
        class="mt-3 rounded px-2 py-1 text-center text-sm font-semibold"
        :class="
          openedResolved.status === 'succeeded'
            ? 'bg-green-400/10 text-green-300'
            : 'bg-red-400/10 text-red-300'
        "
      >
        {{ openedResolved.status === 'succeeded' ? '🏆 Victoire' : '💥 Défaite' }}
      </p>

      <div v-if="Object.keys(openedResolved.requirement ?? {}).length" class="mt-3">
        <p class="text-xs uppercase tracking-wide text-parchment-100/50">Il fallait</p>
        <ul class="mt-1 space-y-1">
          <li
            v-for="(needed, key) in openedResolved.requirement"
            :key="key"
            class="flex items-center justify-between text-xs text-parchment-100/70"
          >
            <span>{{ icon(key) }} {{ label(key) }}</span>
            <span>{{ needed }}</span>
          </li>
        </ul>
      </div>

      <div class="mt-3">
        <p class="text-xs uppercase tracking-wide text-parchment-100/50">Conséquences</p>
        <p class="mt-1 text-xs text-parchment-100">{{ outcomeText(openedResolved) }}</p>
      </div>
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
.event-badge__close {
  position: absolute;
  top: -0.25rem;
  right: -0.25rem;
  display: flex;
  height: 1.15rem;
  width: 1.15rem;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border: 1px solid rgb(120 113 108 / 0.8);
  background-color: rgb(28 25 23 / 0.95);
  font-size: 0.6rem;
  line-height: 1;
  color: rgb(214 211 209);
}
.event-badge__close:hover {
  color: rgb(254 202 202);
  border-color: rgb(248 113 113 / 0.9);
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
.event-badge--won {
  border-color: rgb(74 222 128 / 0.7);
  background-color: rgb(20 83 45 / 0.6);
  color: rgb(187 247 208);
}
.event-badge--lost {
  border-color: rgb(248 113 113 / 0.7);
  background-color: rgb(69 26 26 / 0.7);
  color: rgb(254 202 202);
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
