import { useState } from 'react'
import { Link, useSearchParams } from 'react-router'
import CompatibilitySummary from '../components/analysis/CompatibilitySummary'
import PowerCard from '../components/analysis/PowerCard'
import PriceBreakdown from '../components/analysis/PriceBreakdown'
import ScoreCard from '../components/analysis/ScoreCard'
import { Select } from '../components/ui/Field'
import { EmptyState, ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { api } from '../services/api'
import { isEmptySelection, parseSelection, serializeSelection, toApiSelection } from '../utils/builderUrl'
import { PURPOSES } from '../utils/format'
import { recommendationsFor } from '../utils/recommendations'

const TONES = {
  error: 'border-red-200 bg-red-50 text-red-800',
  warning: 'border-amber-200 bg-amber-50 text-amber-900',
  info: 'border-brand-200 bg-brand-50 text-brand-800',
  ok: 'border-emerald-200 bg-emerald-50 text-emerald-800',
}

/**
 * Full analysis of a template (?build=slug) or of a custom configuration (Builder URL format).
 */
export default function Analysis() {
  const [searchParams] = useSearchParams()
  const slug = searchParams.get('build')
  const selection = parseSelection(searchParams)
  const [profile, setProfile] = useState(null)

  const key = JSON.stringify({ slug, selection: slug ? null : toApiSelection(selection), profile })
  const { data, meta, error, loading, reload } = useApi(
    (signal) => (slug
      ? api.buildAnalysis(slug, profile, signal)
      : api.builderAnalyze({ selected: toApiSelection(selection), profile: profile ?? undefined }, signal)),
    key,
  )

  if (!slug && isEmptySelection(selection)) {
    return (
      <EmptyState title="Chưa có cấu hình để phân tích">
        Mở một <Link to="/builds" className="text-brand-700 underline">cấu hình mẫu</Link> hoặc{' '}
        <Link to="/builder" className="text-brand-700 underline">tự build</Link>, rồi chọn “Phân tích chi tiết”.
      </EmptyState>
    )
  }

  if (error) {
    return <ErrorState error={error} onRetry={reload} />
  }

  if (!data) {
    return <LoadingState label="Đang phân tích…" />
  }

  const backLink = slug ? `/builds/${slug}` : `/builder?${serializeSelection(selection)}`

  return (
    <div className={loading ? 'opacity-70' : ''}>
      <nav className="text-sm text-slate-500">
        <Link to={backLink} className="hover:text-brand-700">← {slug ? 'Về cấu hình mẫu' : 'Về Builder'}</Link>
      </nav>

      <header className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Phân tích cấu hình</h1>
          <p className="mt-1 text-slate-600">
            Công suất và điểm là <strong>ước tính</strong> theo quy tắc của dự án, không phải kết quả đo hay benchmark.
          </p>
        </div>
        <div className="w-full sm:w-64">
          <Select label="Chấm điểm theo mục đích" value={meta.profile} options={PURPOSES} placeholder={null} onChange={setProfile} />
        </div>
      </header>

      <section className="mt-6" aria-labelledby="recommendations">
        <h2 id="recommendations" className="mb-3 text-lg font-semibold">Khuyến nghị</h2>
        <ul className="space-y-2">
          {recommendationsFor(data).map((item) => (
            <li key={item.text} className={`rounded-lg border px-4 py-2.5 text-sm ${TONES[item.tone]}`}>{item.text}</li>
          ))}
        </ul>
      </section>

      <div className="mt-8 grid gap-6 lg:grid-cols-3">
        <section className="lg:col-span-2" aria-labelledby="compatibility">
          <h2 id="compatibility" className="mb-3 text-lg font-semibold">Tương thích ({data.compatibility.results.length} quy tắc)</h2>
          <CompatibilitySummary compatibility={data.compatibility} missingSlots={data.missing_slots} showAll />
        </section>
        <aside className="space-y-4">
          <ScoreCard performance={data.performance} detailed />
          <PowerCard power={data.power} detailed />
          <PriceBreakdown price={data.price} />
        </aside>
      </div>
    </div>
  )
}
