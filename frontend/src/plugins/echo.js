import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { getToken } from '@/services/api'

window.Pusher = Pusher

const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http'
const forceTLS = scheme === 'https'

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  forceTLS,
  enabledTransports: ['ws', 'wss'],
  authorizer: (channel) => ({
    authorize: (socketId, callback) => {
      fetch(`${import.meta.env.VITE_API_URL}/broadcasting/auth`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${getToken()}`,
        },
        body: JSON.stringify({
          socket_id: socketId,
          channel_name: channel.name,
        }),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`Broadcast auth failed: ${response.status}`)
          }
          return response.json()
        })
        .then((data) => callback(null, data))
        .catch((error) => callback(error, null))
    },
  }),
})

export default echo
