import api from '@/services/api'

export const gameService = {
  getCountries() {
    return api.get('/game/countries')
  },

  join(countryId) {
    return api.post('/game/join', { country_id: countryId })
  },

  getState() {
    return api.get('/game/state', { skipToast: true })
  },

  getTowns() {
    return api.get('/game/towns')
  },

  enterTown(townId) {
    return api.post(`/game/towns/${townId}/enter`)
  },

  performAction(actionKey) {
    return api.post('/game/actions', { action: actionKey })
  },

  build(townBuildingId) {
    return api.post(`/game/buildings/${townBuildingId}/build`)
  },

  adventure() {
    return api.post('/game/adventure')
  },

  getSeasonResults(gameId) {
    return api.get(`/game/seasons/${gameId}/results`)
  },

  getLastSeason() {
    return api.get('/game/seasons/last')
  },

  getChatMessages() {
    return api.get('/game/chat')
  },

  sendChatMessage(body) {
    return api.post('/game/chat', { body })
  },

  getCurrentVote() {
    return api.get('/game/votes/current')
  },

  castBallot(voteId, optionId) {
    return api.post(`/game/votes/${voteId}/ballot`, { option_id: optionId })
  },
}
