<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'
import BugReportWidget from '@/components/game/BugReportWidget.vue'
import { useAuthStore } from '@/stores/auth' 

const layouts = {
  default: DefaultLayout,
  auth: AuthLayout,
  blank: BlankLayout,
}

const route = useRoute()
const auth = useAuthStore()
const layout = computed(() => layouts[route.meta.layout] ?? DefaultLayout)

const showBugReport = computed(() => auth.isAuthenticated && route.name !== 'home')
</script>

<template>
  <component :is="layout">
    <RouterView />
  </component>

  <BugReportWidget v-if="showBugReport" />
</template>
