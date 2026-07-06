<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { useGameStore } from '@/stores/game'
import { useEcho } from '@/composables/useEcho'
import { gameService } from '@/services/game.service'

const game = useGameStore()
const { echo } = useEcho()

const open = ref(false)
const messages = ref([])
const seen = new Set()
const unread = ref(0)
const body = ref('')
const sending = ref(false)
const listRef = ref(null)

const countryId = computed(() => game.player?.country?.id ?? null)
const countryName = computed(() => game.player?.country?.name ?? 'du royaume')
const myPlayerId = computed(() => game.player?.id ?? null)

let channelName = null

async function scrollToBottom() {
  await nextTick()
  if (listRef.value) {
    listRef.value.scrollTop = listRef.value.scrollHeight
  }
}

function append(message, { silent = false } = {}) {
  if (seen.has(message.id)) return

  seen.add(message.id)
  messages.value.push(message)

  if (!silent && !open.value) {
    unread.value++
  }

  if (open.value) {
    scrollToBottom()
  }
}

async function loadHistory() {
  const { data } = await gameService.getChatMessages()

  for (const message of data.data) {
    append(message, { silent: true })
  }

  // Des messages temps réel ont pu arriver pendant le chargement.
  messages.value.sort((a, b) => a.id - b.id)
  scrollToBottom()
}

function subscribe(id) {
  channelName = `country.${id}`
  echo.private(channelName).listen('.chat.message', (payload) => append(payload))
}

// Dès que le joueur (et donc son pays) est connu : abonnement + historique.
watch(
  countryId,
  (id) => {
    if (id && !channelName) {
      subscribe(id)
      loadHistory()
    }
  },
  { immediate: true },
)

onUnmounted(() => {
  if (channelName) {
    echo.leave(channelName)
  }
})

async function send() {
  const text = body.value.trim()
  if (!text || sending.value) return

  sending.value = true
  try {
    const { data } = await gameService.sendChatMessage(text)
    append(data.data)
    body.value = ''
  } catch {
    // L'erreur est déjà signalée par l'intercepteur API.
  } finally {
    sending.value = false
  }
}

function toggle() {
  open.value = !open.value

  if (open.value) {
    unread.value = 0
    scrollToBottom()
  }
}

function timeOf(message) {
  if (!message.at) return ''

  return new Date(message.at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div v-if="countryId" class="absolute bottom-4 right-4 z-30 flex flex-col items-end gap-2">
    <!-- Panneau -->
    <div
      v-if="open"
      class="flex h-96 w-80 flex-col overflow-hidden rounded-lg border border-gold-400/40 bg-iron-900/95 shadow-2xl backdrop-blur"
    >
      <div class="flex items-center justify-between border-b border-iron-700 px-3 py-2">
        <h3 class="font-heading text-sm text-gold-400">💬 Chat {{ countryName }}</h3>
        <button
          type="button"
          class="text-parchment-100/50 transition hover:text-parchment-100"
          aria-label="Fermer le chat"
          @click="toggle"
        >
          ✕
        </button>
      </div>

      <!-- Messages -->
      <div ref="listRef" class="flex-1 space-y-2 overflow-y-auto p-3">
        <p v-if="messages.length === 0" class="text-center text-xs text-parchment-100/50">
          Aucun message — lancez la conversation !
        </p>

        <div
          v-for="message in messages"
          :key="message.id"
          :class="message.type === 'system' ? 'text-center' : ''"
        >
          <!-- Message système -->
          <p v-if="message.type === 'system'" class="text-xs italic text-gold-400/80">
            📯 {{ message.body }}
          </p>

          <!-- Message joueur -->
          <div
            v-else
            class="max-w-[85%] rounded-md px-2.5 py-1.5"
            :class="
              message.player_id === myPlayerId
                ? 'ml-auto bg-gold-500/15 text-right'
                : 'bg-iron-800/80'
            "
          >
            <p class="text-xs">
              <span
                :class="message.player_id === myPlayerId ? 'text-gold-400' : 'text-parchment-200'"
                class="font-semibold"
              >
                {{ message.player_id === myPlayerId ? 'Vous' : (message.author ?? 'Inconnu') }}
              </span>
              <span class="ml-1 text-parchment-100/40">{{ timeOf(message) }}</span>
            </p>
            <p class="mt-0.5 break-words text-sm text-parchment-100">{{ message.body }}</p>
          </div>
        </div>
      </div>

      <!-- Saisie -->
      <form class="flex gap-2 border-t border-iron-700 p-2" @submit.prevent="send">
        <input
          v-model="body"
          type="text"
          maxlength="500"
          placeholder="Votre message…"
          class="input !py-1.5 text-sm"
        />
        <button type="submit" class="btn-gold !px-3 !py-1.5 text-sm" :disabled="sending || !body.trim()">
          ➤
        </button>
      </form>
    </div>

    <!-- Bouton flottant -->
    <button
      type="button"
      class="relative flex h-12 w-12 items-center justify-center rounded-full border-2 border-gold-400/60 bg-iron-900/90 text-xl shadow-xl backdrop-blur transition hover:border-gold-400 hover:shadow-gold-400/30"
      :title="open ? 'Fermer le chat' : 'Chat du royaume'"
      @click="toggle"
    >
      💬
      <span
        v-if="unread > 0"
        class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white"
      >
        {{ unread > 9 ? '9+' : unread }}
      </span>
    </button>
  </div>
</template>
