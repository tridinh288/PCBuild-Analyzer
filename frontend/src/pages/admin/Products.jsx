import { Link } from 'react-router'
import { Select } from '../../components/ui/Field'
import Pagination from '../../components/ui/Pagination'
import SearchInput from '../../components/ui/SearchInput'
import { EmptyState, ErrorState, LoadingState } from '../../components/ui/States'
import { useApi } from '../../hooks/useApi'
import { useFilters } from '../../hooks/useFilters'
import { adminApi, api } from '../../services/api'
import { formatPrice } from '../../utils/format'

const STATUSES = [{ value: '1', label: 'Đang kinh doanh' }, { value: '0', label: 'Ngừng kinh doanh' }]

export default function Products() {
  const { filters, setFilter } = useFilters()
  const categories = useApi((signal) => api.categories(signal), 'categories')
  const { data, meta, error, loading, reload } = useApi((signal) => adminApi.products(filters, signal), JSON.stringify(filters))

  const categoryOptions = (categories.data ?? []).map((category) => ({ value: category.slug, label: category.name }))

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold">Linh kiện</h1>
        <Link to={`/admin/products/new${filters.category ? `?category=${filters.category}` : ''}`}
          className="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">
          + Thêm linh kiện
        </Link>
      </div>

      <div className="mt-4 grid gap-3 rounded-xl bg-white p-4 shadow-sm sm:grid-cols-3">
        <div>
          <span className="mb-1 block text-sm font-medium text-slate-700">Tìm kiếm</span>
          <SearchInput value={filters.search ?? ''} onChange={(value) => setFilter('search', value)} placeholder="Tên, hãng, model…" />
        </div>
        <Select label="Loại" value={filters.category} options={categoryOptions} onChange={(value) => setFilter('category', value)} />
        <Select label="Trạng thái" value={filters.is_active} options={STATUSES} onChange={(value) => setFilter('is_active', value)} />
      </div>

      <div className="mt-4 overflow-x-auto rounded-xl bg-white shadow-sm">
        {error && <ErrorState error={error} onRetry={reload} />}
        {!data && !error && <LoadingState />}
        {data?.length === 0 && <EmptyState title="Không có linh kiện phù hợp" />}
        {data?.length > 0 && (
          <table className={`w-full min-w-[720px] text-sm ${loading ? 'opacity-60' : ''}`}>
            <thead className="border-b border-slate-200 text-left text-slate-500">
              <tr>
                <th scope="col" className="px-4 py-2 font-medium">Tên</th>
                <th scope="col" className="px-4 py-2 font-medium">Loại</th>
                <th scope="col" className="px-4 py-2 font-medium">Hãng</th>
                <th scope="col" className="px-4 py-2 text-right font-medium">Giá</th>
                <th scope="col" className="px-4 py-2 font-medium">Trạng thái</th>
                <th scope="col" className="px-4 py-2 text-right font-medium">Dùng trong</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.map((product) => (
                <tr key={product.id} className="hover:bg-slate-50">
                  <td className="px-4 py-2.5">
                    <Link to={`/admin/products/${product.id}`} className="font-medium text-brand-700 hover:underline">{product.name}</Link>
                  </td>
                  <td className="px-4 py-2.5 text-slate-600">{product.category_name}</td>
                  <td className="px-4 py-2.5 text-slate-600">{product.brand}</td>
                  <td className="px-4 py-2.5 text-right">{formatPrice(product.price)}</td>
                  <td className="px-4 py-2.5">
                    {product.is_active
                      ? <span className="text-emerald-700">Đang kinh doanh</span>
                      : <span className="text-slate-400">Ngừng kinh doanh</span>}
                  </td>
                  <td className="px-4 py-2.5 text-right text-slate-600">{product.used_in_builds} cấu hình</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
      <Pagination meta={meta} onPageChange={(page) => setFilter('page', page)} />
    </div>
  )
}
