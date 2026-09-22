import { CATEGORY_NAMES } from '../utils/format'

const COLORS = {
  cpu: 'from-sky-100 to-sky-200 text-sky-700',
  motherboard: 'from-emerald-100 to-emerald-200 text-emerald-700',
  ram: 'from-violet-100 to-violet-200 text-violet-700',
  gpu: 'from-rose-100 to-rose-200 text-rose-700',
  storage: 'from-amber-100 to-amber-200 text-amber-700',
  psu: 'from-lime-100 to-lime-200 text-lime-700',
  case: 'from-slate-200 to-slate-300 text-slate-700',
  cooler: 'from-cyan-100 to-cyan-200 text-cyan-700',
  build: 'from-brand-100 to-brand-50 text-brand-700',
}

/**
 * Shown when there is no uploaded image: a self-made per-category placeholder,
 * never a manufacturer photo (spec section 25).
 */
export default function ImagePlaceholder({ category = 'build', className = 'aspect-[4/3]' }) {
  const label = category === 'build' ? 'PC Build' : (CATEGORY_NAMES[category] ?? category)

  return (
    <div className={`grid place-items-center rounded-lg bg-gradient-to-br ${COLORS[category] ?? COLORS.build} ${className}`}
      aria-hidden="true">
      <span className="text-sm font-semibold tracking-wide">{label}</span>
    </div>
  )
}
