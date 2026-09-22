import { useMemo, useState } from 'react'
import { useSearchParams } from 'react-router'
import ComparisonTable from '../components/ComparisonTable'
import { Select } from '../components/ui/Field'
import { EmptyState, ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { api } from '../services/api'
import { isEmptySelection, parseSelection } from '../utils/builderUrl'
import { MAX_CONFIGURATIONS, parseComparison, serializeComparison, toApiConfiguration } from '../utils/compareUrl'
import { loadDraft } from '../utils/draft'
import { PURPOSES } from '../utils/format'

const DRAFT_OPTION = '__draft__'

export default function Compare() {
  const [searchParams, setSearchParams] = useSearchParams()
  const entries = useMemo(() => parseComparison(searchParams), [searchParams])
  const [profile, setProfile] = useState('general_use')
  const [adding, setAdding] = useState('')

  const templates = useApi((signal) => api.builds({ per_page: 50, sort: 'price_asc' }, signal), 'templates')
  const [draft] = useState(() => {
    const query = loadDraft()
    const selection = query ? parseSelection(query) : {}
    return isEmptySelection(selection) ? null : selection
  })

  const body = { configurations: entries.map(toApiConfiguration), profile }
  const canCompare = entries.length >= 2
  const comparison = useApi((signal) => (canCompare ? api.compare(body, signal) : Promise.resolve({ data: null, meta: {} })),
    JSON.stringify(body))

  const setEntries = (next) => setSearchParams(serializeComparison(next), { replace: true })

  const addEntry = (value) => {
    setAdding('')
    if (!value) return
    const entry = value === DRAFT_OPTION ? { type: 'custom', selection: draft } : { type: 'template', slug: value }
    setEntries([...entries, entry])
  }

  const label = (entry, index) => (entry.type === 'template'
    ? templates.data?.find((build) => build.slug === entry.slug)?.name ?? entry.slug
    : `Cấu hình tùy chỉnh ${index + 1}`)

  const options = [
    ...(draft ? [{ value: DRAFT_OPTION, label: 'Cấu hình đang build (bản nháp)' }] : []),
    ...(templates.data ?? []).map((build) => ({ value: build.slug, label: build.name })),
  ]

  return (
    <div>
      <h1 className="text-2xl font-bold">So sánh cấu hình</h1>
      <p className="mt-1 text-slate-600">Chọn 2–3 cấu hình mẫu hoặc cấu hình bạn đang build để xem khác biệt.</p>

      <section className="mt-6 rounded-xl border border-slate-200 bg-white p-4" aria-label="Chọn cấu hình">
        <ul className="flex flex-wrap gap-2">
          {entries.map((entry, index) => (
            <li key={`${index}-${entry.slug ?? 'custom'}`}
              className="flex items-center gap-2 rounded-full bg-brand-50 py-1 pl-3 pr-1 text-sm font-medium text-brand-800">
              {label(entry, index)}
              <button type="button" onClick={() => setEntries(entries.filter((_, i) => i !== index))}
                className="rounded-full px-2 py-0.5 hover:bg-brand-100" aria-label={`Bỏ ${label(entry, index)}`}>✕</button>
            </li>
          ))}
        </ul>

        <div className="mt-4 grid gap-3 sm:grid-cols-2">
          {entries.length < MAX_CONFIGURATIONS && (
            <Select label="Thêm cấu hình" value={adding} options={options} placeholder="Chọn…" onChange={addEntry} />
          )}
          <Select label="Chấm điểm theo mục đích (chung cho tất cả)" value={profile} options={PURPOSES} placeholder={null}
            onChange={setProfile} />
        </div>
      </section>

      <section className="mt-8">
        {!canCompare && <EmptyState title="Cần ít nhất 2 cấu hình để so sánh" />}
        {canCompare && comparison.error && <ErrorState error={comparison.error} onRetry={comparison.reload} />}
        {canCompare && !comparison.error && !comparison.data && <LoadingState label="Đang so sánh…" />}
        {canCompare && comparison.data && (
          <div className={comparison.loading ? 'opacity-60' : ''}>
            <ComparisonTable comparison={comparison.data} />
          </div>
        )}
      </section>
    </div>
  )
}
