/**
 * Display-ready specs from the API (label + display value). `highlightOnly` for compact cards.
 */
export default function SpecTable({ specs, highlightOnly = false }) {
  const rows = highlightOnly ? specs.filter((spec) => spec.highlight) : specs

  if (highlightOnly) {
    return (
      <ul className="flex flex-wrap gap-1.5">
        {rows.map((spec) => (
          <li key={spec.key} className="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">
            {spec.display}
          </li>
        ))}
      </ul>
    )
  }

  return (
    <dl className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
      {rows.map((spec) => (
        <div key={spec.key} className="grid grid-cols-2 gap-4 px-4 py-2.5 text-sm">
          <dt className="text-slate-500">{spec.label}</dt>
          <dd className="font-medium text-slate-800">{spec.display}</dd>
        </div>
      ))}
    </dl>
  )
}
