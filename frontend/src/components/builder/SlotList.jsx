import { problemsFor, worstStatus } from '../../utils/compatibility'
import { CATEGORY_NAMES, formatPrice } from '../../utils/format'
import ImagePlaceholder from '../ImagePlaceholder'

const BORDERS = {
  compatible: 'border-slate-200',
  warning: 'border-amber-300 bg-amber-50/40',
  incompatible: 'border-red-300 bg-red-50/40',
}

function requirementLabel(required) {
  if (required === true) return 'Bắt buộc'
  if (required === 'unless_cpu_has_integrated_graphics') return 'Cần nếu CPU không có đồ họa tích hợp'
  if (required === 'unless_cpu_includes_cooler') return 'Cần nếu CPU không kèm tản'
  return 'Tùy chọn'
}

function QuantityStepper({ value, max, onChange, label }) {
  const button = 'size-7 rounded-md ring-1 ring-slate-300 disabled:opacity-40 enabled:hover:bg-slate-100'
  return (
    <div className="flex items-center gap-1.5" aria-label={label}>
      <button type="button" className={button} disabled={value <= 1} onClick={() => onChange(value - 1)} aria-label="Giảm">−</button>
      <span className="w-6 text-center text-sm font-medium" aria-live="polite">{value}</span>
      <button type="button" className={button} disabled={value >= max} onClick={() => onChange(value + 1)} aria-label="Tăng">+</button>
    </div>
  )
}

/**
 * The 8 Builder slots in order. Slots with problems turn yellow or red with the reasons (D-018).
 */
export default function SlotList({ categories, builder, onPick }) {
  const compatibility = builder.analysis.data?.compatibility
  const missingSlots = builder.analysis.data?.missing_slots ?? []

  return (
    <ol className="space-y-3">
      {categories.map((category) => {
        const items = builder.selection[category.slug] ?? []
        const problems = problemsFor(category.slug, compatibility)
        const status = worstStatus(problems)
        const isMissing = missingSlots.includes(category.slug)

        return (
          <li key={category.slug} className={`rounded-xl border bg-white p-4 ${BORDERS[status]}`}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <h2 className="font-semibold">{category.name}</h2>
                <p className={`text-xs ${isMissing ? 'font-medium text-red-600' : 'text-slate-400'}`}>
                  {isMissing ? 'Chưa chọn (bắt buộc)' : requirementLabel(category.slot.required)}
                </p>
              </div>
              {(items.length === 0 || category.slot.multiple) ? (
                <button type="button" onClick={() => onPick(category.slug)}
                  className="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                  {items.length === 0 ? 'Chọn' : 'Thêm'}
                </button>
              ) : (
                <button type="button" onClick={() => onPick(category.slug)}
                  className="rounded-lg px-3 py-1.5 text-sm font-medium text-brand-700 ring-1 ring-brand-200 hover:bg-brand-50">
                  Đổi
                </button>
              )}
            </div>

            {items.length > 0 && (
              <ul className="mt-3 space-y-2">
                {items.map((item) => {
                  const product = builder.productsById[item.id]
                  const gone = builder.missingIds.has(`${category.slug}:${item.id}`)

                  return (
                    <li key={item.id} className="flex items-center gap-3">
                      <ImagePlaceholder category={category.slug} className="size-12 shrink-0 text-[9px]" />
                      <div className="min-w-0 flex-1">
                        {gone
                          ? <p className="text-sm font-medium text-amber-700">Linh kiện #{item.id} không còn bán</p>
                          : <p className="truncate text-sm font-medium">{product?.name ?? 'Đang tải…'}</p>}
                        {product && !gone && <p className="text-sm text-slate-500">{formatPrice(product.price)}</p>}
                      </div>
                      {category.slot.accepts_quantity && !gone && (
                        <QuantityStepper value={item.quantity} max={category.slot.max_quantity}
                          label={`Số lượng ${category.name}`}
                          onChange={(quantity) => builder.setQuantity(category.slug, item.id, quantity)} />
                      )}
                      <button type="button" onClick={() => builder.removePart(category.slug, item.id)}
                        className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-red-600"
                        aria-label={`Bỏ ${product?.name ?? CATEGORY_NAMES[category.slug]}`}>
                        ✕
                      </button>
                    </li>
                  )
                })}
              </ul>
            )}

            {problems.length > 0 && (
              <ul className="mt-3 space-y-1 text-sm">
                {problems.map((problem) => (
                  <li key={problem.rule} className={problem.status === 'incompatible' ? 'text-red-700' : 'text-amber-800'}>
                    {problem.message}
                  </li>
                ))}
              </ul>
            )}
          </li>
        )
      })}
    </ol>
  )
}
