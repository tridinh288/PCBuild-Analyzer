/**
 * Small labelled form controls used by filter panels.
 */

const control = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'

export function Select({ label, value, onChange, options, placeholder = 'Tất cả' }) {
  return (
    <label className="block text-sm">
      <span className="mb-1 block font-medium text-slate-700">{label}</span>
      <select value={value ?? ''} onChange={(event) => onChange(event.target.value)} className={control}>
        {placeholder !== null && <option value="">{placeholder}</option>}
        {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
      </select>
    </label>
  )
}

export function NumberInput({ label, value, onChange, placeholder, unit, min = 0 }) {
  return (
    <label className="block text-sm">
      <span className="mb-1 block font-medium text-slate-700">{label}{unit && <span className="font-normal text-slate-500"> ({unit})</span>}</span>
      <input type="number" inputMode="numeric" min={min} value={value ?? ''} placeholder={placeholder}
        onChange={(event) => onChange(event.target.value)} className={control} />
    </label>
  )
}

export function Checkbox({ label, checked, onChange }) {
  return (
    <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
      <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)}
        className="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
      {label}
    </label>
  )
}
