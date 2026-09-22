import { CATEGORY_NAMES, formatWatts } from '../../utils/format'

const SOURCE_NAMES = { ...CATEGORY_NAMES, fans: 'Quạt' }

/**
 * Estimated power, recommended PSU and the selected PSU. Labelled as an estimate (D-015).
 */
export default function PowerCard({ power, detailed = false }) {
  const selected = power.selected_psu_watts
  const scale = Math.max(power.recommended_psu_watts, selected ?? 0, 1)
  const tone = selected === null ? 'text-slate-500'
    : selected < power.estimated_watts ? 'text-red-700'
      : selected < power.recommended_psu_watts ? 'text-amber-700' : 'text-emerald-700'

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4">
      <h3 className="text-sm font-semibold text-slate-500">Công suất ước tính</h3>
      <p className="mt-1 text-2xl font-bold">{formatWatts(power.estimated_watts)}</p>

      <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
        <div className="h-full rounded-full bg-brand-500" style={{ width: `${(power.estimated_watts / scale) * 100}%` }} />
      </div>

      <dl className="mt-3 grid grid-cols-2 gap-2 text-sm">
        <dt className="text-slate-500">Nguồn khuyến nghị</dt>
        <dd className="text-right font-medium">{formatWatts(power.recommended_psu_watts)}</dd>
        <dt className="text-slate-500">Nguồn đã chọn</dt>
        <dd className={`text-right font-medium ${tone}`}>{selected === null ? 'Chưa chọn' : formatWatts(selected)}</dd>
      </dl>

      {detailed && Object.keys(power.breakdown).length > 0 && (
        <dl className="mt-4 grid grid-cols-2 gap-1 border-t border-slate-100 pt-3 text-sm">
          {Object.entries(power.breakdown).map(([source, watts]) => (
            <div key={source} className="contents">
              <dt className="text-slate-500">{SOURCE_NAMES[source] ?? source}</dt>
              <dd className="text-right">{formatWatts(watts)}</dd>
            </div>
          ))}
        </dl>
      )}

      <p className="mt-3 text-xs text-slate-400">Ước tính từ TDP linh kiện × 1,25, không phải số đo thực tế.</p>
    </div>
  )
}
