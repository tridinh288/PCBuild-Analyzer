import { useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router'
import SpecField from '../../components/admin/SpecField'
import { ErrorState, LoadingState } from '../../components/ui/States'
import { useApi } from '../../hooks/useApi'
import { adminApi, api } from '../../services/api'
import { initialSpecValues, isFieldVisible, specsForSubmit } from '../../utils/specForm'

const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'

/**
 * Create (/admin/products/new?category=cpu) or edit (/admin/products/:id).
 * Loads what the form needs, then renders the form with complete initial values.
 */
export default function ProductForm() {
  const { id } = useParams()
  const [searchParams] = useSearchParams()
  const product = useApi((signal) => (id ? adminApi.product(id, signal) : Promise.resolve({ data: null, meta: {} })), id ?? 'new')
  const categories = useApi((signal) => api.categories(signal), 'categories')

  if (product.error || categories.error) {
    return <ErrorState error={product.error ?? categories.error} onRetry={product.error ? product.reload : categories.reload} />
  }

  if (product.loading || !categories.data) {
    return <LoadingState />
  }

  const category = product.data?.category ?? searchParams.get('category') ?? categories.data[0].slug

  return <ProductEditor key={`${id ?? 'new'}-${category}`} product={product.data} category={category} categories={categories.data} />
}

function ProductEditor({ product, category: initialCategory, categories }) {
  const navigate = useNavigate()
  const [category, setCategory] = useState(initialCategory)
  const schema = useApi((signal) => adminApi.specSchema(category, signal), category)

  const [form, setForm] = useState({
    name: product?.name ?? '',
    slug: product?.slug ?? '',
    brand: product?.brand ?? '',
    model: product?.model ?? '',
    price: product ? String(product.price) : '',
    description: product?.description ?? '',
    is_active: product?.is_active ?? true,
  })
  const [specs, setSpecs] = useState(null)
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState(null)
  const [saving, setSaving] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)

  // Spec values are initialised once the schema for the current category is known.
  const fields = schema.data?.category === category ? schema.data.fields : null
  const specValues = specs ?? (fields ? initialSpecValues(fields, product?.raw_specs) : null)

  const set = (key) => (event) => setForm({ ...form, [key]: event.target.value })

  const submit = async (event) => {
    event.preventDefault()
    setSaving(true)
    setErrors({})
    setMessage(null)

    const body = {
      ...form,
      slug: form.slug || undefined,
      price: form.price === '' ? null : Number(form.price),
      specs: specsForSubmit(fields, specValues),
      ...(product ? {} : { category }),
    }

    try {
      const { data } = product ? await adminApi.updateProduct(product.id, body) : await adminApi.createProduct(body)
      setMessage('Đã lưu.')
      if (!product) navigate(`/admin/products/${data.id}`, { replace: true })
    } catch (error) {
      setErrors(error.errors ?? {})
      setMessage(error.message)
    } finally {
      setSaving(false)
    }
  }

  const remove = async () => {
    if (!confirmDelete) {
      setConfirmDelete(true)
      return
    }
    try {
      await adminApi.deleteProduct(product.id)
      navigate('/admin/products', { replace: true })
    } catch (error) {
      setMessage(error.message)
      setConfirmDelete(false)
    }
  }

  return (
    <form onSubmit={submit} noValidate>
      <nav className="text-sm text-slate-500"><Link to="/admin/products" className="hover:text-brand-700">← Linh kiện</Link></nav>
      <div className="mt-2 flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold">{product ? product.name : 'Thêm linh kiện'}</h1>
        {product && <Link to={`/components/${product.slug}`} className="text-sm text-brand-700 hover:underline">Xem trang công khai</Link>}
      </div>

      {message && (
        <p className={`mt-4 rounded-lg px-4 py-2 text-sm ${Object.keys(errors).length || message !== 'Đã lưu.' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`}
          role="status">{message}</p>
      )}

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <section className="space-y-4 rounded-xl bg-white p-5 shadow-sm" aria-labelledby="general">
          <h2 id="general" className="font-semibold">Thông tin chung</h2>
          <label className="block text-sm font-medium text-slate-700">
            Loại linh kiện
            <select value={category} disabled={Boolean(product)} className={input}
              onChange={(e) => { setCategory(e.target.value); setSpecs(null) }}>
              {categories.map((item) => <option key={item.slug} value={item.slug}>{item.name}</option>)}
            </select>
            {product && <span className="mt-1 block text-xs text-slate-400">Không đổi được loại vì thông số phụ thuộc vào loại.</span>}
            {errors.category && <span className="text-xs text-red-600">{errors.category[0]}</span>}
          </label>
          {[['name', 'Tên'], ['brand', 'Hãng'], ['model', 'Model']].map(([key, label]) => (
            <label key={key} className="block text-sm font-medium text-slate-700">
              {label} <span className="text-red-500">*</span>
              <input value={form[key]} onChange={set(key)} className={input} />
              {errors[key] && <span className="text-xs text-red-600">{errors[key][0]}</span>}
            </label>
          ))}
          <label className="block text-sm font-medium text-slate-700">
            Slug <span className="font-normal text-slate-400">(để trống sẽ tạo từ tên)</span>
            <input value={form.slug} onChange={set('slug')} className={input} />
            {errors.slug && <span className="text-xs text-red-600">{errors.slug[0]}</span>}
          </label>
          <label className="block text-sm font-medium text-slate-700">
            Giá (VND) <span className="text-red-500">*</span>
            <input type="number" min={0} value={form.price} onChange={set('price')} className={input} />
            {errors.price && <span className="text-xs text-red-600">{errors.price[0]}</span>}
          </label>
          <label className="block text-sm font-medium text-slate-700">
            Mô tả
            <textarea rows={3} value={form.description} onChange={set('description')} className={input} />
          </label>
          <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
              className="size-4 rounded border-slate-300 text-brand-600" />
            Đang kinh doanh (bỏ chọn để ẩn khỏi catalog và Builder)
          </label>
        </section>

        <section className="space-y-4 rounded-xl bg-white p-5 shadow-sm" aria-labelledby="specs">
          <h2 id="specs" className="font-semibold">Thông số kỹ thuật</h2>
          <p className="text-xs text-slate-500">Các trường được tạo từ cấu hình phần cứng của hệ thống. Nhập số không kèm đơn vị.</p>
          {errors.specs && <p className="text-xs text-red-600">{errors.specs[0]}</p>}
          {schema.error && <ErrorState error={schema.error} onRetry={schema.reload} />}
          {!specValues && !schema.error && <LoadingState />}
          {specValues && fields.filter((field) => isFieldVisible(field, specValues)).map((field) => (
            <SpecField key={field.key} field={field} value={specValues[field.key]} error={errors[`specs.${field.key}`]?.[0]}
              onChange={(value) => setSpecs({ ...specValues, [field.key]: value })} />
          ))}
        </section>
      </div>

      <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
        <button type="submit" disabled={saving || !specValues}
          className="rounded-lg bg-brand-600 px-5 py-2.5 font-semibold text-white hover:bg-brand-700 disabled:opacity-50">
          {saving ? 'Đang lưu…' : product ? 'Lưu thay đổi' : 'Tạo linh kiện'}
        </button>
        {product && (
          <button type="button" onClick={remove}
            className={`rounded-lg px-4 py-2.5 text-sm font-medium ${confirmDelete ? 'bg-red-600 text-white' : 'text-red-700 ring-1 ring-red-200 hover:bg-red-50'}`}>
            {confirmDelete ? 'Bấm lần nữa để xóa vĩnh viễn' : 'Xóa linh kiện'}
          </button>
        )}
      </div>
    </form>
  )
}
