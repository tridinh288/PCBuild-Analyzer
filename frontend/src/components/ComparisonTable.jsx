import { CATEGORY_NAMES, formatPrice, formatPriceDelta, formatWatts } from '../utils/format'
import CompatibilityBadge from './CompatibilityBadge'

const METRIC_ROWS = [
  { key: 'total_price', label: 'Tổng giá', format: formatPrice },
  { key: 'estimated_watts', label: 'Công suất ước tính', format: formatWatts },
  { key: 'recommended_psu_watts', label: 'Nguồn khuyến nghị', format: formatWatts },
  { key: 'score', label: 'Điểm ước tính', format: (value) => `${value}/100` },
]

const METRIC_CHANGES = {
  estimated_watts: (delta) => `Công suất ước tính: ${delta > 0 ? '+' : ''}${formatWatts(delta)}`,
  total_price: (delta) => `Giá: ${formatPriceDelta(delta)}`,
  score: (delta) => `Điểm ước tính: ${delta > 0 ? '+' : ''}${delta}`,
}

function names(list) {
  return list.length ? list.join(', ') : '(không có)'
}

/**
 * Side-by-side facts and "Có gì khác nhau?" — never a winner (D-017).
 */
export default function ComparisonTable({ comparison }) {
  const { configurations, slots, differences } = comparison

  return (
    <div className="space-y-8">
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table className="w-full min-w-[640px] text-sm">
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50 text-left">
              <th scope="col" className="w-40 px-4 py-3 font-medium text-slate-500">Tiêu chí</th>
              {configurations.map((config) => (
                <th key={config.label} scope="col" className="px-4 py-3 font-semibold text-slate-900">{config.label}</th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {METRIC_ROWS.map((row) => (
              <tr key={row.key}>
                <th scope="row" className="px-4 py-2.5 text-left font-medium text-slate-500">{row.label}</th>
                {configurations.map((config) => <td key={config.label} className="px-4 py-2.5 font-medium">{row.format(config[row.key])}</td>)}
              </tr>
            ))}
            <tr>
              <th scope="row" className="px-4 py-2.5 text-left font-medium text-slate-500">Tương thích</th>
              {configurations.map((config) => (
                <td key={config.label} className="px-4 py-2.5">
                  <CompatibilityBadge status={config.compatibility_status} />
                  {config.missing_slots.length > 0 && <p className="mt-1 text-xs text-slate-500">Chưa hoàn chỉnh</p>}
                </td>
              ))}
            </tr>
            {slots.map((slot) => (
              <tr key={slot.category}>
                <th scope="row" className="px-4 py-2.5 text-left font-medium text-slate-500">{CATEGORY_NAMES[slot.category]}</th>
                {slot.values.map((value, index) => (
                  <td key={configurations[index].label} className="px-4 py-2.5 text-slate-700">{names(value)}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <section aria-labelledby="differences">
        <h2 id="differences" className="text-lg font-semibold">Có gì khác nhau?</h2>
        <div className="mt-3 grid gap-4 md:grid-cols-2">
          {differences.map((difference) => (
            <div key={`${difference.from}-${difference.to}`} className="rounded-xl border border-slate-200 bg-white p-4">
              <p className="text-sm font-medium text-slate-500">
                {configurations[difference.from].label} → {configurations[difference.to].label}
              </p>
              {difference.changes.length === 0
                ? <p className="mt-2 text-sm text-slate-600">Hai cấu hình giống nhau.</p>
                : (
                  <ul className="mt-2 space-y-1 text-sm">
                    {difference.changes.map((change) => (
                      <li key={change.type === 'slot' ? change.category : change.metric}>
                        {change.type === 'slot'
                          ? `${CATEGORY_NAMES[change.category]}: ${names(change.from)} → ${names(change.to)}`
                          : METRIC_CHANGES[change.metric](change.delta)}
                      </li>
                    ))}
                  </ul>
                )}
            </div>
          ))}
        </div>
      </section>

      <p className="text-sm text-slate-500">
        So sánh chỉ đưa ra dữ kiện. Cấu hình phù hợp nhất phụ thuộc vào nhu cầu và ngân sách của bạn.
      </p>
    </div>
  )
}
