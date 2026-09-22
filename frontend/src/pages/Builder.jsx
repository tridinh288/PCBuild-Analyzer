import { useMemo, useState } from 'react'
import { Link } from 'react-router'
import BuilderSummary from '../components/builder/BuilderSummary'
import ComponentPicker from '../components/builder/ComponentPicker'
import ShareActions from '../components/builder/ShareActions'
import SlotList from '../components/builder/SlotList'
import { ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { useBuilder } from '../hooks/useBuilder'
import { api } from '../services/api'

export default function Builder() {
  const categories = useApi((signal) => api.categories(signal), 'categories')

  if (categories.error) {
    return <ErrorState error={categories.error} onRetry={categories.reload} />
  }

  if (!categories.data) {
    return <LoadingState />
  }

  return <BuilderWorkspace categories={categories.data} />
}

function BuilderWorkspace({ categories }) {
  const slots = useMemo(() => Object.fromEntries(categories.map((c) => [c.slug, c.slot])), [categories])
  const builder = useBuilder(slots)
  const [picking, setPicking] = useState(null)
  const pickingCategory = categories.find((category) => category.slug === picking)

  if (builder.templateError) {
    return (
      <div className="space-y-4">
        <ErrorState error={builder.templateError} />
        <button type="button" onClick={builder.reset} className="rounded-lg bg-brand-600 px-4 py-2 text-white">
          Build từ đầu
        </button>
      </div>
    )
  }

  if (builder.loadingTemplate) {
    return <LoadingState label="Đang tải cấu hình mẫu…" />
  }

  return (
    <div>
      <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Tự build cấu hình</h1>
          <p className="mt-1 text-slate-600">
            {builder.template
              ? <>Tùy chỉnh từ <Link to={`/builds/${builder.template.slug}`} className="font-medium text-brand-700 hover:underline">{builder.template.name}</Link>. Cấu hình mẫu gốc không bị thay đổi.</>
              : 'Chọn linh kiện theo thứ tự bất kỳ; tương thích được kiểm tra ngay khi bạn chọn.'}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <ShareActions builder={builder} />
          <button type="button" onClick={builder.reset}
            className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 hover:bg-slate-100">
            Làm lại từ đầu
          </button>
        </div>
      </header>

      {builder.draftRestored && (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm" role="status">
          <span>Đã khôi phục bản nháp lần trước của bạn.</span>
          <span className="flex gap-3">
            <button type="button" onClick={builder.dismissDraftNotice} className="font-medium text-brand-700 hover:underline">Giữ lại</button>
            <button type="button" onClick={builder.reset} className="font-medium text-slate-600 hover:underline">Bỏ bản nháp</button>
          </span>
        </div>
      )}

      {builder.missingIds.size > 0 && (
        <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
          Một số linh kiện trong liên kết không còn bán hoặc không tồn tại. Hãy bỏ chúng ra và chọn linh kiện thay thế.
        </div>
      )}

      <div className="mt-6 grid gap-6 lg:grid-cols-5">
        <section className="lg:col-span-3" aria-label="Linh kiện đã chọn">
          <SlotList categories={categories} builder={builder} onPick={setPicking} />
        </section>
        <aside className="lg:col-span-2 lg:sticky lg:top-20 lg:self-start" aria-label="Phân tích">
          <BuilderSummary builder={builder} />
        </aside>
      </div>

      {pickingCategory && (
        <ComponentPicker
          category={pickingCategory.slug}
          categoryName={pickingCategory.name}
          selected={builder.selected}
          onClose={() => setPicking(null)}
          onSelect={(product) => {
            builder.selectPart(pickingCategory.slug, product)
            setPicking(null)
          }}
        />
      )}
    </div>
  )
}
