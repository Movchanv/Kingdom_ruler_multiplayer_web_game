import { onUnmounted } from 'vue'
import echo from '@/plugins/echo'


export function useEcho() {
  const connection = echo.connector?.pusher?.connection ?? null

  function onReconnect(callback) {
    if (!connection) return

    let wasDisconnected = false

    const onStateChange = ({ current }) => {
      if (['unavailable', 'disconnected', 'connecting'].includes(current)) {
        wasDisconnected = true
        return
      }

      if (current === 'connected' && wasDisconnected) {
        wasDisconnected = false
        callback()
      }
    }

    connection.bind('state_change', onStateChange)

    onUnmounted(() => connection.unbind('state_change', onStateChange))
  }

  return { echo, onReconnect }
}
