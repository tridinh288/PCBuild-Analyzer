import { useEffect, useRef, useState } from 'react'
import { useDebouncedValue } from '../../hooks/useDebouncedValue'

/**
 * Text input that reports its value after the user stops typing.
 * Follows `value` when it changes from outside (e.g. "clear filters") without losing focus.
 */
export default function SearchInput({ value = '', onChange, placeholder = 'Tìm kiếm…', label = 'Tìm kiếm' }) {
  const [text, setText] = useState(value)
  const [previousValue, setPreviousValue] = useState(value)
  const debounced = useDebouncedValue(text)
  const lastDebounced = useRef(debounced)

  // Adjust local state during render when the outside value changes (React-recommended pattern).
  if (value !== previousValue) {
    setPreviousValue(value)
    setText(value)
  }

  useEffect(() => {
    if (debounced === lastDebounced.current) {
      return
    }
    lastDebounced.current = debounced

    if (debounced !== value) {
      onChange(debounced)
    }
  }, [debounced, value, onChange])

  return (
    <label className="block">
      <span className="sr-only">{label}</span>
      <input type="search" value={text} onChange={(event) => setText(event.target.value)} placeholder={placeholder}
        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" />
    </label>
  )
}
