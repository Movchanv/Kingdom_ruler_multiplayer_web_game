import axios from 'axios'
import { useToast } from 'vue-toastification'

const api = axios.create({
  baseURL: `${import.meta.env.VITE_API_URL}/api/v1`,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

const TOKEN_KEY = 'auth_token'

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const toast = useToast()
    const status = error.response?.status
    const message = error.response?.data?.message ?? 'Une erreur est survenue.'

    if (status === 401) {
      setToken(null)
      if (window.location.pathname !== '/login') {
        window.location.assign('/login')
      }
    } else if (status === 422) {
      const errors = error.response?.data?.errors ?? {}
      Object.values(errors)
        .flat()
        .forEach((msg) => toast.error(msg))
    } else if (status >= 500) {
      toast.error('Erreur serveur. Réessayez plus tard.')
    } else if (message) {
      toast.error(message)
    }

    return Promise.reject(error)
  },
)

export default api
