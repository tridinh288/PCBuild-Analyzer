import BuildCard from '../components/BuildCard'
import { Checkbox, NumberInput, Select } from '../components/ui/Field'
import Pagination from '../components/ui/Pagination'
import SearchInput from '../components/ui/SearchInput'
import { EmptyState, ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { useDebouncedValue } from '../hooks/useDebouncedValue'
import { useFilters } from '../hooks/useFilters'
import { api } from '../services/api'
import { PURPOSES } from '../utils/format'

const SORTS = [
  { value: 'newest', label: 'Mới nhất' },
  { value: 'price_asc', label: 'Giá tăng dần' },
  { value: 'price_desc', label: 'Giá giảm dần' },
]

export default function Builds() {
  const { filters, setFilter, reset } = useFilters()
  // Typing a price should not fire one request per keystroke.
  const query = useDebouncedValue(filters, 300)
  const { data, meta, error, loading, reload } = useApi(
    (signal) => api.builds({ ...query, per_page: 12 }, signal),
    JSON.stringify(query),
  )

  return (
    <div>
      <h1 className="text-2xl font-bold">Cấu hình mẫu</h1>
      <p className="mt-1 text-slate-600">Các cấu hình dựng sẵn theo mục đích sử dụng. Mở một cấu hình để xem phân tích hoặc tùy chỉnh.</p>

      <section className="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6" aria-label="Bộ lọc">
        <div className="sm:col-span-2">
          <span className="mb-1 block text-sm font-medium text-slate-700">Tìm kiếm</span>
          <SearchInput value={filters.search ?? ''} onChange={(value) => setFilter('search', value)}
            placeholder="Tên cấu hình…" />
        </div>
        <Select label="Mục đích" value={filters.purpose} options={PURPOSES} onChange={(value) => setFilter('purpose', value)} />
        <NumberInput label="Giá từ" unit="VND" value={filters.price_min} onChange={(value) => setFilter('price_min', value)} />
        <NumberInput label="Giá đến" unit="VND" value={filters.price_max} onChange={(value) => setFilter('price_max', value)} />
        <Select label="Sắp xếp" value={filters.sort ?? 'newest'} options={SORTS} placeholder={null}
          onChange={(value) => setFilter('sort', value === 'newest' ? '' : value)} />
        <div className="flex items-center justify-between gap-4 sm:col-span-2 lg:col-span-6">
          <Checkbox label="Chỉ cấu hình nổi bật" checked={filters.featured === '1'} onChange={(checked) => setFilter('featured', checked)} />
          <button type="button" onClick={() => reset()} className="text-sm font-medium text-brand-700 hover:underline">
            Xóa bộ lọc
          </button>
        </div>
      </section>

      <section className="mt-6">
        {error && <ErrorState error={error} onRetry={reload} />}
        {!error && loading && !data && <LoadingState />}
        {!error && data?.length === 0 && (
          <EmptyState title="Không có cấu hình phù hợp">Thử bỏ bớt bộ lọc hoặc tự build cấu hình của bạn.</EmptyState>
        )}
        {!error && data?.length > 0 && (
          <div className={`grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 ${loading ? 'opacity-60' : ''}`}>
            {data.map((build) => <BuildCard key={build.id} build={build} />)}
          </div>
        )}
        <Pagination meta={meta} onPageChange={(page) => setFilter('page', page)} />
      </section>
    </div>
  )
}
