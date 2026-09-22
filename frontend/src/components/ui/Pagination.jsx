/**
 * Previous / next pagination driven by the API `meta` (current_page, last_page, total).
 */
export default function Pagination({ meta, onPageChange }) {
  if (!meta?.last_page || meta.last_page <= 1) {
    return null
  }

  const button = 'rounded-lg px-3 py-2 text-sm font-medium ring-1 ring-slate-300 disabled:opacity-40 enabled:hover:bg-slate-100'

  return (
    <nav className="mt-8 flex items-center justify-center gap-3" aria-label="Phân trang">
      <button type="button" className={button} disabled={meta.current_page <= 1}
        onClick={() => onPageChange(meta.current_page - 1)}>
        ← Trước
      </button>
      <span className="text-sm text-slate-600">
        Trang {meta.current_page} / {meta.last_page} · {meta.total} kết quả
      </span>
      <button type="button" className={button} disabled={meta.current_page >= meta.last_page}
        onClick={() => onPageChange(meta.current_page + 1)}>
        Sau →
      </button>
    </nav>
  )
}
