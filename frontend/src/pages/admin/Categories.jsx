import { useState } from 'react'
import { ErrorState, LoadingState } from '../../components/ui/States'
import { useApi } from '../../hooks/useApi'
import { adminApi } from '../../services/api'

const input = 'w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'

function CategoryRow({ category, onSaved }) {
  const [form, setForm] = useState({ name: category.name, description: category.description ?? '', sort_order: category.sort_order })
  const [status, setStatus] = useState(null)
  const [errors, setErrors] = useState({})

  const changed = form.name !== category.name || form.description !== (category.description ?? '')
    || Number(form.sort_order) !== category.sort_order

  const save = async (event) => {
    event.preventDefault()
    setStatus('saving')
    setErrors({})
    try {
      await adminApi.updateCategory(category.id, { ...form, sort_order: Number(form.sort_order) })
      setStatus('saved')
      onSaved()
    } catch (error) {
      setErrors(error.errors ?? {})
      setStatus(error.message)
    }
  }

  return (
    <tr className="align-top">
      <td className="px-4 py-3">
        <p className="font-mono text-xs text-slate-500">{category.slug}</p>
        <p className="text-xs text-slate-400">{category.products_count} linh kiện</p>
      </td>
      <td className="px-4 py-3" colSpan={4}>
        <form onSubmit={save} className="grid gap-2 md:grid-cols-[1fr_2fr_6rem_auto] md:items-start">
          <label>
            <span className="sr-only">Tên</span>
            <input className={input} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            {errors.name && <span className="text-xs text-red-600">{errors.name[0]}</span>}
          </label>
          <label>
            <span className="sr-only">Mô tả</span>
            <input className={input} value={form.description} placeholder="Mô tả (không bắt buộc)"
              onChange={(e) => setForm({ ...form, description: e.target.value })} />
          </label>
          <label>
            <span className="sr-only">Thứ tự</span>
            <input type="number" min={1} className={input} value={form.sort_order}
              onChange={(e) => setForm({ ...form, sort_order: e.target.value })} />
            {errors.sort_order && <span className="text-xs text-red-600">{errors.sort_order[0]}</span>}
          </label>
          <div className="flex items-center gap-2">
            <button type="submit" disabled={!changed || status === 'saving'}
              className="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-40">
              Lưu
            </button>
            <span className="text-xs text-slate-500" aria-live="polite">
              {status === 'saved' ? 'Đã lưu' : status === 'saving' ? 'Đang lưu…' : status}
            </span>
          </div>
        </form>
      </td>
    </tr>
  )
}

/**
 * Categories are a fixed set (D-006): only name, description and order are editable.
 */
export default function Categories() {
  const { data, error, reload } = useApi((signal) => adminApi.categories(signal), 'admin-categories')

  return (
    <div>
      <h1 className="text-2xl font-bold">Danh mục</h1>
      <p className="mt-1 text-sm text-slate-600">
        8 loại linh kiện được cố định trong mã nguồn vì mỗi loại gắn với thông số và quy tắc tương thích riêng.
        Ở đây chỉ sửa được tên, mô tả và thứ tự hiển thị.
      </p>

      <div className="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm">
        {error && <ErrorState error={error} onRetry={reload} />}
        {!data && !error && <LoadingState />}
        {data && (
          <table className="w-full min-w-[720px] text-sm">
            <thead className="border-b border-slate-200 text-left text-slate-500">
              <tr>
                <th scope="col" className="px-4 py-2 font-medium">Mã</th>
                <th scope="col" className="px-4 py-2 font-medium">Tên</th>
                <th scope="col" className="px-4 py-2 font-medium">Mô tả</th>
                <th scope="col" className="px-4 py-2 font-medium">Thứ tự</th>
                <th scope="col" className="px-4 py-2" />
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.map((category) => <CategoryRow key={category.id} category={category} onSaved={reload} />)}
            </tbody>
          </table>
        )}
      </div>
    </div>
  )
}
