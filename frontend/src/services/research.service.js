import api from '@/services/api'

export const researchService = {
  getOverview() {
    return api.get('/research/overview')
  },

  getActions() {
    return api.get('/research/actions')
  },

  getSeasons() {
    return api.get('/research/seasons')
  },

  async download(endpoint, fallbackName) {
    const response = await api.get(endpoint, { responseType: 'blob' })

    const disposition = response.headers['content-disposition'] ?? ''
    const match = disposition.match(/filename="?([^";]+)"?/)
    const filename = match?.[1] ?? fallbackName

    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  },

  downloadActions() {
    return this.download('/research/exports/actions', 'medieval-realm-actions.csv')
  },

  downloadSeasons() {
    return this.download('/research/exports/seasons', 'medieval-realm-seasons.csv')
  },
}
