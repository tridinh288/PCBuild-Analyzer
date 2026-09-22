import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router'

/**
 * Filter state lives in the query string, so filtered lists can be shared and the Back button
 * works. Changing any filter except `page` goes back to page 1.
 *
 * @returns {{ filters: Record<string, string>, setFilter: Function, setFilters: Function, reset: Function }}
 */
export function useFilters() {
  const [searchParams, setSearchParams] = useSearchParams()

  const filters = useMemo(() => Object.fromEntries(searchParams.entries()), [searchParams])

  const setFilters = useCallback((changes) => {
    setSearchParams((previous) => {
      const next = new URLSearchParams(previous)

      Object.entries(changes).forEach(([name, value]) => {
        if (value === '' || value === null || value === undefined || value === false) {
          next.delete(name)
        } else {
          next.set(name, String(value === true ? 1 : value))
        }
      })

      if (!('page' in changes)) {
        next.delete('page')
      }

      return next
    }, { replace: true })
  }, [setSearchParams])

  const setFilter = useCallback((name, value) => setFilters({ [name]: value }), [setFilters])

  const reset = useCallback((keep = []) => {
    setSearchParams((previous) => {
      const next = new URLSearchParams()
      keep.forEach((name) => previous.has(name) && next.set(name, previous.get(name)))
      return next
    }, { replace: true })
  }, [setSearchParams])

  return { filters, setFilter, setFilters, reset }
}
