import { NumberInput, Select } from './ui/Field'
import SearchInput from './ui/SearchInput'

const BOOLEAN_OPTIONS = [{ value: '1', label: 'Có' }, { value: '0', label: 'Không' }]

const SPEC_LABEL_SUFFIX = { min: ' tối thiểu', max: ' tối đa' }

/**
 * Filter UI rendered from GET /categories/{slug}/filters, not hardcoded per category
 * (spec section 22). Used by the catalog and the Builder picker.
 *
 * @param {object} definitions  API filter definitions (common, specs, sorts)
 * @param {Record<string,string>} values
 * @param {(name: string, value: string) => void} onChange
 */
export default function FilterPanel({ definitions, values, onChange, onReset, showSort = true }) {
  const [search, brand, price] = definitions.common

  return (
    <div className="space-y-4">
      <div>
        <span className="mb-1 block text-sm font-medium text-slate-700">{search.label}</span>
        <SearchInput value={values.search ?? ''} onChange={(value) => onChange('search', value)} placeholder="Tên, hãng, model…" />
      </div>

      {showSort && (
        <Select label="Sắp xếp" value={values.sort ?? 'name'} options={definitions.sorts} placeholder={null}
          onChange={(value) => onChange('sort', value === 'name' ? '' : value)} />
      )}

      <Select label={brand.label} value={values.brand} options={brand.options} onChange={(value) => onChange('brand', value)} />

      <div className="grid grid-cols-2 gap-2">
        <NumberInput label="Giá từ" value={values.price_min} placeholder={String(price.min)}
          onChange={(value) => onChange('price_min', value)} />
        <NumberInput label="Giá đến" value={values.price_max} placeholder={String(price.max)}
          onChange={(value) => onChange('price_max', value)} />
      </div>

      {definitions.specs.map((spec) => {
        const label = spec.label + (SPEC_LABEL_SUFFIX[spec.filter] ?? '')

        if (spec.filter === 'boolean') {
          return <Select key={spec.param} label={label} value={values[spec.param]} options={BOOLEAN_OPTIONS}
            onChange={(value) => onChange(spec.param, value)} />
        }

        if (spec.options) {
          return <Select key={spec.param} label={label} value={values[spec.param]} options={spec.options}
            onChange={(value) => onChange(spec.param, value)} />
        }

        return <NumberInput key={spec.param} label={label} unit={spec.unit} value={values[spec.param]}
          onChange={(value) => onChange(spec.param, value)} />
      })}

      {onReset && (
        <button type="button" onClick={onReset} className="text-sm font-medium text-brand-700 hover:underline">
          Xóa bộ lọc
        </button>
      )}
    </div>
  )
}
