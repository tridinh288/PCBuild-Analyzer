import { CATEGORY_NAMES, formatPrice } from '../../utils/format'

// One color per category, kept in slot order so the bar and the list match.
const COLORS = {
  cpu: 'bg-sky-500',
  motherboard: 'bg-emerald-500',
  ram: 'bg-violet-500',
  gpu: 'bg-rose-500',
  storage: 'bg-amber-500',
  psu: 'bg-lime-500',
  case: 'bg-slate-500',
  cooler: 'bg-cyan-500',
}

/**
 * Where the money goes: a stacked bar plus the amount and share per category.
 */
export default function PriceBreakdown({ price }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4">
      <h3 className="text-sm font-semibold text-slate-500">Tổng chi phí</h3>
      <p className="mt-1 text-2xl font-bold">{formatPrice(price.total)}</p>

      {price.by_category.length > 0 && (
        <>
          <div className="mt-3 flex h-3 overflow-hidden rounded-full bg-slate-100" role="img"
            aria-label={price.by_category.map((row) => `${CATEGORY_NAMES[row.category]} ${row.percent}%`).join(', ')}>
            {price.by_category.map((row) => (
              <div key={row.category} className={COLORS[row.category]} style={{ width: `${row.percent}%` }} />
            ))}
          </div>

          <ul className="mt-3 space-y-1 text-sm">
            {price.by_category.map((row) => (
              <li key={row.category} className="flex items-center gap-2">
                <span className={`size-2.5 rounded-sm ${COLORS[row.category]}`} aria-hidden="true" />
                <span className="flex-1 text-slate-600">{CATEGORY_NAMES[row.category] ?? row.category}</span>
                <span className="text-slate-400">{row.percent}%</span>
                <span className="w-32 text-right font-medium">{formatPrice(row.amount)}</span>
              </li>
            ))}
          </ul>
        </>
      )}
    </div>
  )
}
