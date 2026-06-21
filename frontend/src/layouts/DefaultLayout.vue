<script setup>
import { computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const isAuthenticated = computed(() => auth.isAuthenticated)

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-iron-800 bg-iron-900/80 backdrop-blur">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <RouterLink to="/" class="text-xl font-heading text-gold-400">
          Medieval Realm
        </RouterLink>

        <nav class="flex items-center gap-4 text-sm">
          <RouterLink to="/" class="hover:text-gold-400">Accueil</RouterLink>
          <RouterLink v-if="isAuthenticated" to="/play" class="hover:text-gold-400">
            Jouer
          </RouterLink>
          <RouterLink v-else to="/login" class="hover:text-gold-400">Connexion</RouterLink>
          <button v-if="isAuthenticated" class="hover:text-gold-400" @click="handleLogout">
            Déconnexion
          </button>
        </nav>
      </div>
    </header>

    <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
      <slot />
    </main>

    <footer class="border-t border-iron-800 py-4 text-center text-xs text-iron-700">
      Medieval Realm — base technique
    </footer>
  </div>
</template>
