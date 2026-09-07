import api from '@/services/api'

export const authService = {
  register(payload) {
    return api.post('/auth/register', payload)
  },

  login(credentials) {
    return api.post('/auth/login', credentials)
  },

  logout() {
    return api.post('/auth/logout')
  },

  forgotPassword(email) {
    return api.post('/auth/forgot-password', { email })
  },

  resetPassword(payload) {
    return api.post('/auth/reset-password', payload)
  },

  me() {
    return api.get('/auth/me')
  },
}
