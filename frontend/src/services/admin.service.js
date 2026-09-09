import api from '@/services/api'

export const adminService = {
  getGames() {
    return api.get('/admin/games')
  },

  getLaws() {
    return api.get('/admin/laws')
  },

  createLaw(payload) {
    return api.post('/admin/laws', payload)
  },

  getEvents() {
    return api.get('/admin/events')
  },

  createEvent(payload) {
    return api.post('/admin/events', payload)
  },

  triggerEvent(eventId, townId) {
    return api.post(`/admin/events/${eventId}/trigger`, { town_id: townId })
  },

  openVote({ gameId, countryId, lawIds, hours }) {
    return api.post('/admin/votes', {
      game_id: gameId,
      country_id: countryId,
      law_ids: lawIds,
      hours,
    })
  },
}
