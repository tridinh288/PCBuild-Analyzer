import { isFieldRequired } from '../../utils/specForm'

const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'

/**
 * One spec input rendered from the schema type (spec section 25):
 * enum → select, enum_list → checkbox group, integer → number with unit, boolean → switch.
 */
export default function SpecField({ field, value, onChange, error }) {
  const id = `spec-${field.key}`
  const label = (
    <span className="text-sm font-medium text-slate-700">
      {field.label}{isFieldRequired(field) && <span className="text-red-500"> *</span>}
      {field.unit && <span className="font-normal text-slate-500"> ({field.unit})</span>}
    </span>
  )

  let control
  switch (field.type) {
    case 'enum':
      control = (
        <select id={id} value={value} onChange={(e) => onChange(e.target.value)} className={input}>
          <option value="">— Chọn —</option>
          {field.options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
        </select>
      )
      break
    case 'enum_list':
      control = (
        <div className="mt-1 flex flex-wrap gap-3">
          {field.options.map((option) => (
            <label key={option.value} className="flex items-center gap-1.5 text-sm">
              <input type="checkbox" checked={value.includes(option.value)} className="size-4 rounded border-slate-300 text-brand-600"
                onChange={(e) => onChange(e.target.checked ? [...value, option.value] : value.filter((v) => v !== option.value))} />
              {option.label}
            </label>
          ))}
        </div>
      )
      break
    case 'boolean':
      control = (
        <button id={id} type="button" role="switch" aria-checked={value} onClick={() => onChange(!value)}
          className={`mt-1 inline-flex h-6 w-11 items-center rounded-full transition ${value ? 'bg-brand-600' : 'bg-slate-300'}`}>
          <span className={`size-5 rounded-full bg-white shadow transition ${value ? 'translate-x-5' : 'translate-x-0.5'}`} />
          <span className="sr-only">{value ? 'Có' : 'Không'}</span>
        </button>
      )
      break
    default:
      control = (
        <input id={id} type="number" inputMode="numeric" min={field.min ?? undefined} max={field.max ?? undefined}
          value={value} onChange={(e) => onChange(e.target.value)} className={input} />
      )
  }

  return (
    <div>
      {field.type === 'enum_list' ? <fieldset><legend>{label}</legend>{control}</fieldset> : <label htmlFor={id}>{label}{control}</label>}
      {field.min !== null && field.type === 'integer' && (
        <p className="mt-0.5 text-xs text-slate-400">Từ {field.min} đến {field.max}</p>
      )}
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  )
}
