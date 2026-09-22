import { useEffect, useState } from 'react'

/**
 * The value, updated only after it stopped changing for `delay` ms (search inputs).
 */
export function useDebouncedValue(value, delay = 400) {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(timer)
  }, [value, delay])

  return debounced
}
