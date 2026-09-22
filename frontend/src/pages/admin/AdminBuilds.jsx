import { Link } from 'react-router'
import { Select } from '../../components/ui/Field'
import Pagination from '../../components/ui/Pagination'
import SearchInput from '../../components/ui/SearchInput'
import { EmptyState, ErrorState, LoadingState } from '../../components/ui/States'
import { useApi } from '../../hooks/useApi'
import { useFilters } from '../../hooks/useFilters'
import { adminApi } from '../../services/api'
import { formatPrice, PURPOSES } from '../../utils/format'

export default function AdminBuilds() {
  const { filters, setFilter } = useFilters()
  const { data, meta, error, loading, reload } = useApi((signal) => adminApi.builds(filters, signal), JSON.stringify(filters))

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold">Cấu hình mẫu</h1>
        <Link to="/admin/builds/new" className="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">
          + Thêm cấu hình mẫu
        </Link>
      </div>

      <div className="mt-4 grid gap-3 rounded-xl bg-white p-4 shadow-sm sm:grid-cols-2">
        <div>
          <span className="mb-1 block text-sm font-medium text-slate-700">Tìm kiếm</span>
          <SearchInput value={filters.search ?? ''} onChange={(value) => setFilter('search', value)} placeholder="Tên cấu hình…" />
        </div>
        <Select label="Mục đích" value={filters.purpose} options={PURPOSES} onChange={(value) => setFilter('purpose', value)} />
      </div>

      <div className="mt-4 overflow-x-auto rounded-xl bg-white shadow-sm">
        {error && <ErrorState error={error} onRetry={reload} />}
        {!data && !error && <LoadingState />}
        {data?.length === 0 && <EmptyState title="Chưa có cấu hình mẫu phù hợp" />}
        {data?.length > 0 && (
          <table className={`w-full min-w-[640px] text-sm ${loading ? 'opacity-60' : ''}`}>
            <thead className="border-b border-slate-200 text-left text-slate-500">
              <tr>
                <th scope="col" className="px-4 py-2 font-medium">Tên</th>
                <th scope="col" className="px-4 py-2 font-medium">Mục đích</th>
                <th scope="col" className="px-4 py-2 text-right font-medium">Tổng giá</th>
                <th scope="col" className="px-4 py-2 font-medium">Nổi bật</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.map((build) => (
                <tr key={build.id} className="hover:bg-slate-50">
                  <td className="px-4 py-2.5">
                    <Link to={`/admin/builds/${build.id}`} className="font-medium text-brand-700 hover:underline">{build.name}</Link>
                  </td>
                  <td className="px-4 py-2.5 text-slate-600">{build.purpose_label}</td>
                  <td className="px-4 py-2.5 text-right">{formatPrice(build.total_price)}</td>
                  <td className="px-4 py-2.5">{build.is_featured ? '★' : ''}</td>
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
