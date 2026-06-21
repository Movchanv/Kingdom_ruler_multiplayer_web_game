import api from '@/services/api'

export const authService = {
  login(credentials) {
    return api.post('/auth/login', credentials)
  },

  logout() {
    return api.post('/auth/logout')
  },

  me() {
    return api.get('/auth/me')
  },
}
