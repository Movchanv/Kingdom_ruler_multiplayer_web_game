import { defineStore } from 'pinia'
import { authService } from '@/services/auth.service'
import { getToken, setToken } from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: getToken(),
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token),
  },

  actions: {
    async login(credentials) {
      this.loading = true
      try {
        const { data } = await authService.login(credentials)
        this.token = data.data.token
        this.user = data.data.user
        setToken(this.token)
        return this.user
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
      if (!this.token) {
        return null
      }
      const { data } = await authService.me()
      this.user = data.data
      return this.user
    },

    async logout() {
      try {
        if (this.token) {
          await authService.logout()
        }
      } finally {
        this.user = null
        this.token = null
        setToken(null)
      }
    },
  },
})
