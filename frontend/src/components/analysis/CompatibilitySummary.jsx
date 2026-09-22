import CompatibilityBadge from '../CompatibilityBadge'
import { CATEGORY_NAMES } from '../../utils/format'

const BANNERS = {
  compatible: { className: 'border-emerald-200 bg-emerald-50 text-emerald-800', text: 'Các linh kiện đã chọn tương thích với nhau.' },
  warning: { className: 'border-amber-200 bg-amber-50 text-amber-900', text: 'Lắp được, nhưng có điểm cần lưu ý.' },
  incompatible: { className: 'border-red-200 bg-red-50 text-red-800', text: 'Cấu hình hiện chưa thể lắp ráp.' },
}

/**
 * Summary banner with error/warning counts, the problems, and missing slots.
 * `showAll` also lists rules that passed or were skipped (analysis page).
 */
export default function CompatibilitySummary({ compatibility, missingSlots = [], showAll = false }) {
  const banner = BANNERS[compatibility.status] ?? BANNERS.compatible
  const results = showAll ? compatibility.results : compatibility.results.filter((r) => ['warning', 'incompatible'].includes(r.status))

  return (
    <div className="space-y-3">
      <div className={`rounded-xl border p-4 ${banner.className}`} role="status">
        <p className="font-semibold">{banner.text}</p>
        <p className="mt-1 text-sm">
          {compatibility.errors} lỗi · {compatibility.warnings} cảnh báo
        </p>
      </div>

      {missingSlots.length > 0 && (
        <div className="rounded-xl border border-slate-200 bg-white p-4 text-sm">
          <p className="font-semibold text-slate-800">Cấu hình chưa hoàn chỉnh</p>
          <p className="mt-1 text-slate-600">Còn thiếu: {missingSlots.map((slot) => CATEGORY_NAMES[slot] ?? slot).join(', ')}</p>
        </div>
      )}

      {results.length > 0 && (
        <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
          {results.map((result) => (
            <li key={result.rule} className="flex flex-col gap-1 p-3 sm:flex-row sm:items-start sm:gap-3">
              <CompatibilityBadge status={result.status} />
              <div className="text-sm">
                <p className="font-medium text-slate-800">{result.title}</p>
                <p className="text-slate-600">{result.message}</p>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
