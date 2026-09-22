/**
 * Tracks requests that take unusually long. Render's free tier puts the API to sleep; the
 * first request then takes up to about a minute, and the UI shows a "waking up" message.
 *
 * A tiny external store, read in React with useSyncExternalStore (hooks/useServerWaking.js).
 */

export const SLOW_REQUEST_MS = 5000

let slowRequests = 0
const listeners = new Set()

function emit() {
  listeners.forEach((listener) => listener())
}

export function subscribe(listener) {
  listeners.add(listener)
  return () => listeners.delete(listener)
}

export function isWaking() {
  return slowRequests > 0
}

/**
 * Call when a request starts; returns a function to call when it ends (success or error).
 */
export function trackRequest() {
  let slow = false
  const timer = setTimeout(() => {
    slow = true
    slowRequests += 1
    emit()
  }, SLOW_REQUEST_MS)

  return function done() {
    clearTimeout(timer)
    if (slow) {
      slowRequests -= 1
      emit()
    }
  }
}
