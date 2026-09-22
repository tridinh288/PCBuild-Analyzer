import { useState } from 'react'
import ComponentCard from '../components/ComponentCard'
import FilterPanel from '../components/FilterPanel'
import Pagination from '../components/ui/Pagination'
import { EmptyState, ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { useDebouncedValue } from '../hooks/useDebouncedValue'
import { useFilters } from '../hooks/useFilters'
import { api } from '../services/api'

export default function Components() {
  const { filters, setFilter, reset } = useFilters()
  const [filtersOpen, setFiltersOpen] = useState(false)
  const category = filters.category ?? 'cpu'

  const categories = useApi((signal) => api.categories(signal), 'categories')
  const definitions = useApi((signal) => api.categoryFilters(category, signal), category)

  const query = useDebouncedValue({ ...filters, category }, 300)
  const products = useApi((signal) => api.components({ ...query, per_page: 12 }, signal), JSON.stringify(query))

  // Switching category drops the previous category's spec filters.
  const selectCategory = (slug) => reset([], { category: slug })

  return (
    <div>
      <h1 className="text-2xl font-bold">Linh kiện</h1>

      <div className="mt-4 flex gap-2 overflow-x-auto pb-2" role="tablist" aria-label="Loại linh kiện">
        {categories.data?.map((item) => (
          <button key={item.slug} type="button" role="tab" aria-selected={item.slug === category}
            onClick={() => selectCategory(item.slug)}
            className={`shrink-0 rounded-full px-4 py-2 text-sm font-medium ${
              item.slug === category ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100'
            }`}>
            {item.name}
          </button>
        ))}
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-4">
        <aside className="lg:col-span-1">
          <button type="button" onClick={() => setFiltersOpen((open) => !open)} aria-expanded={filtersOpen}
            className="mb-3 w-full rounded-lg bg-white px-4 py-2 text-sm font-medium ring-1 ring-slate-200 lg:hidden">
            {filtersOpen ? 'Ẩn bộ lọc' : 'Hiện bộ lọc'}
          </button>
          <div className={`rounded-xl border border-slate-200 bg-white p-4 ${filtersOpen ? '' : 'hidden'} lg:block`}>
            {definitions.error && <ErrorState error={definitions.error} onRetry={definitions.reload} />}
            {definitions.data && definitions.data.category === category && (
              <FilterPanel definitions={definitions.data} values={filters} onChange={setFilter}
                onReset={() => reset(['category'])} />
            )}
          </div>
        </aside>

        <section className="lg:col-span-3">
          {products.error && <ErrorState error={products.error} onRetry={products.reload} />}
          {!products.error && products.loading && !products.data && <LoadingState />}
          {!products.error && products.data?.length === 0 && (
            <EmptyState title="Không có linh kiện phù hợp">Thử bỏ bớt bộ lọc.</EmptyState>
          )}
          {!products.error && products.data?.length > 0 && (
            <div className={`grid gap-4 sm:grid-cols-2 xl:grid-cols-3 ${products.loading ? 'opacity-60' : ''}`}>
              {products.data.map((product) => <ComponentCard key={product.id} product={product} />)}
            </div>
          )}
          <Pagination meta={products.meta} onPageChange={(page) => setFilter('page', page)} />
        </section>
      </div>
    </div>
  )
}
