import { Link, useParams } from 'react-router'
import CompatibilitySummary from '../components/analysis/CompatibilitySummary'
import PowerCard from '../components/analysis/PowerCard'
import PriceBreakdown from '../components/analysis/PriceBreakdown'
import ScoreCard from '../components/analysis/ScoreCard'
import ImagePlaceholder from '../components/ImagePlaceholder'
import SpecTable from '../components/SpecTable'
import { ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { api } from '../services/api'
import { CATEGORY_NAMES, formatPrice } from '../utils/format'

export default function BuildDetail() {
  const { slug } = useParams()
  const build = useApi((signal) => api.build(slug, signal), slug)
  const analysis = useApi((signal) => api.buildAnalysis(slug, null, signal), slug)

  if (build.error) {
    return <ErrorState error={build.error} onRetry={build.reload} />
  }

  if (!build.data) {
    return <LoadingState />
  }

  const data = build.data

  return (
    <div>
      <nav className="text-sm text-slate-500">
        <Link to="/builds" className="hover:text-brand-700">Cấu hình mẫu</Link> / {data.name}
      </nav>

      <header className="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{data.purpose_label}</span>
          <h1 className="mt-2 text-2xl font-bold sm:text-3xl">{data.name}</h1>
          {data.description && <p className="mt-2 max-w-2xl text-slate-600">{data.description}</p>}
          <p className="mt-3 text-2xl font-bold text-brand-700">{formatPrice(data.total_price)}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to={`/builder?template=${data.slug}`}
            className="rounded-lg bg-brand-600 px-4 py-2.5 font-semibold text-white hover:bg-brand-700">
            Tùy chỉnh cấu hình này
          </Link>
          <Link to={`/analysis?build=${data.slug}`}
            className="rounded-lg px-4 py-2.5 font-semibold text-brand-700 ring-1 ring-brand-200 hover:bg-brand-50">
            Phân tích chi tiết
          </Link>
          <Link to={`/compare?c=${data.slug}`}
            className="rounded-lg px-4 py-2.5 font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100">
            So sánh
          </Link>
        </div>
      </header>

      <div className="mt-8 grid gap-6 lg:grid-cols-3">
        <section className="lg:col-span-2" aria-label="Linh kiện">
          <h2 className="mb-3 text-lg font-semibold">Linh kiện</h2>
          <ul className="space-y-3">
            {data.items.map((item) => (
              <li key={item.product.id} className="flex gap-4 rounded-xl border border-slate-200 bg-white p-3">
                <ImagePlaceholder category={item.category} className="size-16 shrink-0 text-[10px] sm:size-20" />
                <div className="min-w-0 flex-1">
                  <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{CATEGORY_NAMES[item.category]}</p>
                  <Link to={`/components/${item.product.slug}`} className="font-medium text-slate-900 hover:text-brand-700">
                    {item.product.name}
                  </Link>
                  {!item.product.is_active && <span className="ml-2 text-xs text-amber-700">(ngừng kinh doanh)</span>}
                  <div className="mt-1.5"><SpecTable specs={item.product.specs} highlightOnly /></div>
                </div>
                <div className="text-right text-sm">
                  <p className="font-semibold">{formatPrice(item.product.price * item.quantity)}</p>
                  {item.quantity > 1 && <p className="text-slate-500">{item.quantity} × {formatPrice(item.product.price)}</p>}
                </div>
              </li>
            ))}
          </ul>
        </section>

        <aside className="space-y-4" aria-label="Phân tích nhanh">
          {analysis.error && <ErrorState error={analysis.error} onRetry={analysis.reload} />}
          {!analysis.data && !analysis.error && <LoadingState label="Đang phân tích…" />}
          {analysis.data && (
            <>
              <CompatibilitySummary compatibility={analysis.data.compatibility} missingSlots={analysis.data.missing_slots} />
              <PowerCard power={analysis.data.power} />
              <ScoreCard performance={analysis.data.performance} />
              <PriceBreakdown price={analysis.data.price} />
            </>
          )}
        </aside>
      </div>
    </div>
  )
}
