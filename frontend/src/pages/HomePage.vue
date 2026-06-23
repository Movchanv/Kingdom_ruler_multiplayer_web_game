<script setup>
import { computed, ref, useTemplateRef } from 'vue'
import { RouterLink } from 'vue-router'
import { useMouse, useWindowSize, onClickOutside } from '@vueuse/core'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const isAuthenticated = computed(() => auth.isAuthenticated)

function logout() {
  auth.logout()
}

const { x, y } = useMouse()
const { width, height } = useWindowSize()

// Effet parallax
const parallaxStyle = computed(() => {
  const dx = width.value ? x.value / width.value - 0.5 : 0
  const dy = height.value ? y.value / height.value - 0.5 : 0
  const max = 24

  return {
    transform: `translate3d(${(-dx * max).toFixed(1)}px, ${(-dy * max).toFixed(1)}px, 0) scale(1.12)`,
  }
})

const languages = [{ code: 'fr', flag: '🇫🇷', label: 'Français' }]
const currentLang = ref('fr')
const langMenuOpen = ref(false)
const langRef = useTemplateRef('langRef')

const currentFlag = computed(() => languages.find((l) => l.code === currentLang.value)?.flag ?? '🌐')

function selectLang(code) {
  currentLang.value = code
  langMenuOpen.value = false
}

onClickOutside(langRef, () => {
  langMenuOpen.value = false
})
</script>

<template>
  <div class="relative h-screen w-screen overflow-hidden bg-iron-900">
    <div
      class="home-bg pointer-events-none absolute -inset-8 bg-cover bg-center transition-transform duration-200 ease-out"
      :style="parallaxStyle"
    />
    <div
      class="pointer-events-none absolute inset-0 bg-gradient-to-b from-iron-900/60 via-iron-900/20 to-iron-900/75"
    />

    <header class="absolute inset-x-0 top-0 z-20 flex items-center justify-end gap-3 p-5">
      <template v-if="!isAuthenticated">
        <RouterLink to="/register" class="btn-ghost">S'inscrire</RouterLink>
        <RouterLink to="/login" class="btn-gold">Se connecter</RouterLink>
      </template>
      <button v-else type="button" class="btn-ghost" @click="logout">Déconnexion</button>

      <div ref="langRef" class="relative">
        <button
          type="button"
          class="flag-btn"
          :aria-expanded="langMenuOpen"
          aria-haspopup="listbox"
          title="Langue"
          @click="langMenuOpen = !langMenuOpen"
        >
          {{ currentFlag }}
        </button>

        <ul
          v-if="langMenuOpen"
          class="absolute right-0 mt-2 w-44 overflow-hidden rounded-md border border-iron-700 bg-iron-900 shadow-xl"
          role="listbox"
        >
          <li v-for="lang in languages" :key="lang.code">
            <button
              type="button"
              class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-iron-800"
              :class="lang.code === currentLang ? 'text-gold-400' : 'text-parchment-50'"
              role="option"
              :aria-selected="lang.code === currentLang"
              @click="selectLang(lang.code)"
            >
              <span>{{ lang.flag }}</span>
              <span>{{ lang.label }}</span>
            </button>
          </li>
        </ul>
      </div>
    </header>

    <main
      class="pointer-events-none relative z-10 flex h-full flex-col items-center justify-center px-4 text-center"
    >
      <h1 class="font-heading text-5xl text-gold-400 drop-shadow-lg sm:text-6xl">Medieval Realm</h1>
      <p class="mt-3 max-w-md text-parchment-100/90">Bâtissez votre royaume, forgez votre légende.</p>

      <RouterLink to="/play" class="btn-play pointer-events-auto mt-10">Jouer</RouterLink>
    </main>
  </div>
</template>

<style scoped>
.home-bg {
  background-color: #1f2937;
  background-image: url('/home-bg.png');
}
</style>
