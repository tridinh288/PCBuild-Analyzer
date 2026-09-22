const STYLES = {
  compatible: { label: 'Tương thích', className: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  warning: { label: 'Cảnh báo', className: 'bg-amber-50 text-amber-800 ring-amber-200' },
  incompatible: { label: 'Không tương thích', className: 'bg-red-50 text-red-700 ring-red-200' },
  skipped: { label: 'Chưa kiểm tra', className: 'bg-slate-100 text-slate-600 ring-slate-200' },
}

export default function CompatibilityBadge({ status, label }) {
  const style = STYLES[status] ?? STYLES.skipped

  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${style.className}`}>
      {label ?? style.label}
    </span>
  )
}
