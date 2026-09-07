import api from '@/services/api'

export const supportService = {
  report(payload) {
    return api.post('/support/reports', payload)
  },

  getReports(status) {
    return api.get('/admin/bug-reports', { params: status ? { status } : {} })
  },

  getMonitoring() {
    return api.get('/admin/monitoring')
  },
}
