import { useEffect, useState } from 'react'
import { useApi } from '../../hooks/useApi'
import { useDebouncedValue } from '../../hooks/useDebouncedValue'
import { api } from '../../services/api'
import { formatPrice } from '../../utils/format'
import CompatibilityBadge from '../CompatibilityBadge'
import FilterPanel from '../FilterPanel'
import SpecTable from '../SpecTable'
import { Checkbox } from '../ui/Field'
import { EmptyState, ErrorState, LoadingState } from '../ui/States'

/**
 * Candidate list for one slot. Every product is shown with its status against the current
 * selection; incompatible ones stay selectable (D-018) unless "compatible only" is on.
 */
export default function ComponentPicker({ category, categoryName, selected, onSelect, onClose }) {
  const [values, setValues] = useState({})
  const [compatibleOnly, setCompatibleOnly] = useState(false)

  const definitions = useApi((signal) => api.categoryFilters(category, signal), category)

  const { sort, ...filters } = values
  const requestKey = useDebouncedValue(JSON.stringify({
    category, selected, filters, sort: sort || undefined, compatible_only: compatibleOnly,
  }), 300)
  const options = useApi((signal) => api.builderOptions(JSON.parse(requestKey), signal), requestKey)

  // Close with Escape.
  useEffect(() => {
    const onKey = (event) => event.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div className="fixed inset-0 z-40 flex justify-end bg-slate-900/40" role="dialog" aria-modal="true"
      aria-labelledby="picker-title" onClick={onClose}>
      <div className="flex h-full w-full max-w-4xl flex-col bg-slate-50 shadow-xl" onClick={(event) => event.stopPropagation()}>
        <div className="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
          <h2 id="picker-title" className="text-lg font-semibold">Chọn {categoryName}</h2>
          <button type="button" onClick={onClose} className="rounded-md p-2 text-slate-500 hover:bg-slate-100" aria-label="Đóng">✕</button>
        </div>

        <div className="grid flex-1 gap-4 overflow-hidden md:grid-cols-3">
          <div className="overflow-y-auto border-b border-slate-200 bg-white p-4 md:border-b-0 md:border-r">
            <Checkbox label="Chỉ hiện linh kiện tương thích" checked={compatibleOnly} onChange={setCompatibleOnly} />
            <div className="mt-4">
              {definitions.data && (
                <FilterPanel definitions={definitions.data} values={values}
                  onChange={(name, value) => setValues((previous) => ({ ...previous, [name]: value }))}
                  onReset={() => setValues({})} />
              )}
            </div>
          </div>

          <div className="overflow-y-auto p-4 md:col-span-2">
            {options.error && <ErrorState error={options.error} onRetry={options.reload} />}
            {!options.error && !options.data && <LoadingState />}
            {options.data?.length === 0 && <EmptyState title="Không có linh kiện phù hợp">Thử bỏ bớt bộ lọc.</EmptyState>}

            <ul className={`space-y-2 ${options.loading ? 'opacity-60' : ''}`}>
              {options.data?.map((option) => (
                <li key={option.product.id}>
                  <button type="button" onClick={() => onSelect(option.product)}
                    className={`w-full rounded-xl border bg-white p-3 text-left transition hover:border-brand-400 hover:shadow-sm ${
                      option.selected ? 'border-brand-500 ring-2 ring-brand-100' : 'border-slate-200'}`}>
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <p className="font-medium">{option.product.name}</p>
                        <div className="mt-1.5"><SpecTable specs={option.product.specs} highlightOnly /></div>
                      </div>
                      <div className="shrink-0 text-right">
                        <p className="font-semibold">{formatPrice(option.product.price)}</p>
                        <div className="mt-1">
                          {option.selected ? <CompatibilityBadge status="compatible" label="Đã chọn" /> : <CompatibilityBadge status={option.status} />}
                        </div>
                      </div>
                    </div>
                    {option.issues.length > 0 && (
                      <ul className="mt-2 space-y-0.5 text-sm">
                        {option.issues.map((issue) => (
                          <li key={issue.rule} className={issue.status === 'incompatible' ? 'text-red-700' : 'text-amber-800'}>
                            {issue.message}
                          </li>
                        ))}
                      </ul>
                    )}
                  </button>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </div>
  )
}
