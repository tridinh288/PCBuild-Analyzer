import { useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router'
import ImageUploader from '../../components/admin/ImageUploader'
import CompatibilitySummary from '../../components/analysis/CompatibilitySummary'
import PowerCard from '../../components/analysis/PowerCard'
import PriceBreakdown from '../../components/analysis/PriceBreakdown'
import ComponentPicker from '../../components/builder/ComponentPicker'
import SlotList from '../../components/builder/SlotList'
import { ErrorState, LoadingState } from '../../components/ui/States'
import { useApi } from '../../hooks/useApi'
import { selectionFromItems, useConfigurationEditor } from '../../hooks/useConfigurationEditor'
import { adminApi, api } from '../../services/api'
import { PURPOSES } from '../../utils/format'

const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'

/**
 * Create (/admin/builds/new) or edit (/admin/builds/:id) a template.
 */
export default function BuildForm() {
  const { id } = useParams()
  const build = useApi((signal) => (id ? adminApi.build(id, signal) : Promise.resolve({ data: null, meta: {} })), id ?? 'new')
  const categories = useApi((signal) => api.categories(signal), 'categories')

  if (build.error || categories.error) {
    return <ErrorState error={build.error ?? categories.error} onRetry={build.error ? build.reload : categories.reload} />
  }

  if (build.loading || !categories.data) {
    return <LoadingState />
  }

  return (
    <div className="space-y-6">
      <nav className="text-sm text-slate-500"><Link to="/admin/builds" className="hover:text-brand-700">← Cấu hình mẫu</Link></nav>
      <DetailsForm key={id ?? 'new'} data={build.data} />
      {build.data && <ComponentsEditor key={build.data.build.id} data={build.data} categories={categories.data} />}
    </div>
  )
}

function DetailsForm({ data }) {
  const navigate = useNavigate()
  const current = data?.build
  const [form, setForm] = useState({
    name: current?.name ?? '',
    slug: current?.slug ?? '',
    description: current?.description ?? '',
    purpose: current?.purpose ?? 'gaming',
    is_featured: current?.is_featured ?? false,
  })
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState(null)
  const [image, setImage] = useState(current?.image ?? null)
  const [confirmDelete, setConfirmDelete] = useState(false)

  const submit = async (event) => {
    event.preventDefault()
    setErrors({})
    setMessage(null)
    try {
      const body = { ...form, slug: form.slug || undefined }
      if (current) {
        await adminApi.updateBuild(current.id, body)
        setMessage('Đã lưu thông tin.')
      } else {
        const { data: created } = await adminApi.createBuild(body)
        navigate(`/admin/builds/${created.build.id}`, { replace: true })
      }
    } catch (error) {
      setErrors(error.errors ?? {})
      setMessage(error.message)
    }
  }

  const remove = async () => {
    if (!confirmDelete) {
      setConfirmDelete(true)
      return
    }
    await adminApi.deleteBuild(current.id)
    navigate('/admin/builds', { replace: true })
  }

  return (
    <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-5 shadow-sm" noValidate>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold">{current ? current.name : 'Thêm cấu hình mẫu'}</h1>
        {current && <Link to={`/builds/${current.slug}`} className="text-sm text-brand-700 hover:underline">Xem trang công khai</Link>}
      </div>
      {message && <p className="rounded-lg bg-slate-100 px-4 py-2 text-sm" role="status">{message}</p>}

      <div className="grid gap-4 md:grid-cols-2">
        <label className="block text-sm font-medium text-slate-700">
          Tên <span className="text-red-500">*</span>
          <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={input} />
          {errors.name && <span className="text-xs text-red-600">{errors.name[0]}</span>}
        </label>
        <label className="block text-sm font-medium text-slate-700">
          Slug <span className="font-normal text-slate-400">(để trống sẽ tạo từ tên)</span>
          <input value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} className={input} />
          {errors.slug && <span className="text-xs text-red-600">{errors.slug[0]}</span>}
        </label>
        <label className="block text-sm font-medium text-slate-700">
          Mục đích <span className="text-red-500">*</span>
          <select value={form.purpose} onChange={(e) => setForm({ ...form, purpose: e.target.value })} className={input}>
            {PURPOSES.map((purpose) => <option key={purpose.value} value={purpose.value}>{purpose.label}</option>)}
          </select>
        </label>
        <label className="flex items-center gap-2 self-end text-sm font-medium text-slate-700">
          <input type="checkbox" checked={form.is_featured} onChange={(e) => setForm({ ...form, is_featured: e.target.checked })}
            className="size-4 rounded border-slate-300 text-brand-600" />
          Nổi bật (hiện ở trang chủ)
        </label>
        <label className="block text-sm font-medium text-slate-700 md:col-span-2">
          Mô tả
          <textarea rows={2} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={input} />
        </label>
      </div>

      {current && (
        <div>
          <span className="text-sm font-medium text-slate-700">Hình ảnh</span>
          <div className="mt-1">
            <ImageUploader image={image}
              onUpload={async (file) => setImage((await adminApi.uploadBuildImage(current.id, file)).data.image)}
              onRemove={async () => setImage((await adminApi.deleteBuildImage(current.id)).data.image)} />
          </div>
        </div>
      )}

      <div className="flex flex-wrap items-center justify-between gap-3">
        <button type="submit" className="rounded-lg bg-brand-600 px-5 py-2.5 font-semibold text-white hover:bg-brand-700">
          {current ? 'Lưu thông tin' : 'Tạo cấu hình mẫu'}
        </button>
        {current && (
          <button type="button" onClick={remove}
            className={`rounded-lg px-4 py-2.5 text-sm font-medium ${confirmDelete ? 'bg-red-600 text-white' : 'text-red-700 ring-1 ring-red-200 hover:bg-red-50'}`}>
            {confirmDelete ? 'Bấm lần nữa để xóa vĩnh viễn' : 'Xóa cấu hình mẫu'}
          </button>
        )}
      </div>
    </form>
  )
}

/**
 * The same slot list, picker and live analysis as the public Builder, saved to the template.
 */
function ComponentsEditor({ data, categories }) {
  const slots = useMemo(() => Object.fromEntries(categories.map((c) => [c.slug, c.slot])), [categories])
  const editor = useConfigurationEditor(slots, { selection: selectionFromItems(data.build.items), template: null }, data.build.purpose)
  const [picking, setPicking] = useState(null)
  const [saved, setSaved] = useState(() => JSON.stringify(editor.selected))
  const [message, setMessage] = useState(null)
  const pickingCategory = categories.find((category) => category.slug === picking)
  const dirty = JSON.stringify(editor.selected) !== saved

  const save = async () => {
    setMessage(null)
    const items = Object.values(editor.selection).flat().map((item) => ({ product_id: item.id, quantity: item.quantity }))
    try {
      await adminApi.updateBuildItems(data.build.id, items)
      setSaved(JSON.stringify(editor.selected))
      setMessage('Đã lưu linh kiện.')
    } catch (error) {
      setMessage(error.errors?.items?.[0] ?? error.message)
    }
  }

  const analysis = editor.analysis.data

  return (
    <section className="rounded-xl bg-white p-5 shadow-sm" aria-labelledby="components">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 id="components" className="text-lg font-semibold">Linh kiện</h2>
        <div className="flex items-center gap-3">
          {message && <span className="text-sm text-slate-600" role="status">{message}</span>}
          <button type="button" onClick={save} disabled={!dirty}
            className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-40">
            {dirty ? 'Lưu linh kiện' : 'Đã lưu'}
          </button>
        </div>
      </div>
      <p className="mt-1 text-sm text-slate-500">Tương thích được kiểm tra ngay khi thay đổi; linh kiện không tương thích vẫn lưu được nhưng sẽ được báo lỗi.</p>

      <div className="mt-4 grid gap-6 lg:grid-cols-5">
        <div className="lg:col-span-3">
          <SlotList categories={categories} builder={editor} onPick={setPicking} />
        </div>
        <div className="space-y-4 lg:col-span-2">
          {analysis && (
            <>
              <CompatibilitySummary compatibility={analysis.compatibility} missingSlots={analysis.missing_slots} />
              <PowerCard power={analysis.power} />
              <PriceBreakdown price={analysis.price} />
            </>
          )}
        </div>
      </div>

      {pickingCategory && (
        <ComponentPicker category={pickingCategory.slug} categoryName={pickingCategory.name} selected={editor.selected}
          onClose={() => setPicking(null)}
          onSelect={(product) => {
            editor.selectPart(pickingCategory.slug, product)
            setPicking(null)
          }} />
      )}
    </section>
  )
}
