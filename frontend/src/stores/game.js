import { defineStore } from 'pinia'
import { gameService } from '@/services/game.service'

export const useGameStore = defineStore('game', {
  state: () => ({
    countries: [],
    towns: [],
    player: null,
    town: null,
    loading: false,
    joining: false,
    traveling: false,
    acting: false,
    stateLoaded: false,
  }),

  getters: {
    hasJoined: (state) => state.player !== null,
    myCountryId: (state) => state.player?.country?.id ?? null,
  },

  actions: {
    async fetchCountries() {
      const { data } = await gameService.getCountries()
      this.countries = data.data
      return this.countries
    },

    async fetchState() {
      try {
        const { data } = await gameService.getState()
        this.player = data.data.player
        this.town = data.data.town
      } catch (error) {
        if (error.response?.status === 422) {
          this.player = null
          this.town = null
        } else {
          throw error
        }
      } finally {
        this.stateLoaded = true
      }
    },

    async join(countryId) {
      this.joining = true
      try {
        await gameService.join(countryId)
        await Promise.all([this.fetchState(), this.fetchCountries()])
      } finally {
        this.joining = false
      }
    },

    async fetchTowns() {
      const { data } = await gameService.getTowns()
      this.towns = data.data
      return this.towns
    },

    async travelTo(townId) {
      this.traveling = true
      try {
        await gameService.enterTown(townId)
        await Promise.all([this.fetchState(), this.fetchTowns()])
      } finally {
        this.traveling = false
      }
    },

    async performAction(actionKey) {
      this.acting = true
      try {
        const { data } = await gameService.performAction(actionKey)
        await this.fetchState()
        return data.data
      } finally {
        this.acting = false
      }
    },

    async build(townBuildingId) {
      this.acting = true
      try {
        const { data } = await gameService.build(townBuildingId)
        await this.fetchState()
        return data.data
      } finally {
        this.acting = false
      }
    },

    async adventure() {
      this.acting = true
      try {
        const { data } = await gameService.adventure()
        await this.fetchState()
        return data.data
      } finally {
        this.acting = false
      }
    },
  },
})
