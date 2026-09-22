import { useSyncExternalStore } from 'react'
import { isWaking, subscribe } from '../services/serverStatus'

export function useServerWaking() {
  return useSyncExternalStore(subscribe, isWaking)
}
