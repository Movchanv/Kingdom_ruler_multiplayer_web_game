<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'

const layouts = {
  default: DefaultLayout,
  auth: AuthLayout,
  blank: BlankLayout,
}

const route = useRoute()
const layout = computed(() => layouts[route.meta.layout] ?? DefaultLayout)
</script>

<template>
  <component :is="layout">
    <RouterView />
  </component>
</template>
