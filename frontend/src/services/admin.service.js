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

  openVote({ gameId, countryId, lawIds, hours }) {
    return api.post('/admin/votes', {
      game_id: gameId,
      country_id: countryId,
      law_ids: lawIds,
      hours,
    })
  },
}
