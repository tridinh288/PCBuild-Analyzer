import { useCallback, useEffect, useRef, useState } from 'react'

/**
 * Loads data from an api.* function and tracks loading / error state.
 *
 * `key` identifies the request (e.g. JSON of the query params): when it changes, the previous
 * request is cancelled and a new one starts, so a slow old response can never overwrite a
 * newer one. `loading` is derived during render instead of being set inside the effect.
 *
 * @param {(signal: AbortSignal) => Promise<{data: any, meta: object}>} load
 * @param {string} key
 */
export function useApi(load, key) {
  const [result, setResult] = useState({ key: null, data: null, meta: {}, error: null })
  const [attempt, setAttempt] = useState(0)
  const loadRef = useRef(load)

  // Always call the latest `load` without making it an effect dependency.
  useEffect(() => {
    loadRef.current = load
  })

  const requestKey = `${key}#${attempt}`

  useEffect(() => {
    const controller = new AbortController()

    loadRef.current(controller.signal)
      .then(({ data, meta }) => setResult({ key: requestKey, data, meta, error: null }))
      .catch((error) => {
        if (!controller.signal.aborted) {
          setResult((previous) => ({ ...previous, key: requestKey, error }))
        }
      })

    return () => controller.abort()
  }, [requestKey])

  const reload = useCallback(() => setAttempt((n) => n + 1), [])
  const loading = result.key !== requestKey

  return {
    data: result.data, // the previous data stays visible while the next request loads
    meta: result.meta,
    error: loading ? null : result.error,
    loading,
    reload,
  }
}
