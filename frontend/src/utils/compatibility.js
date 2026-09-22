const SEVERITY = { skipped: -1, compatible: 0, warning: 1, incompatible: 2 }

/**
 * Warning/incompatible results that concern one Builder slot (API `by_category` → rule keys).
 */
export function problemsFor(category, compatibility) {
  if (!compatibility) return []
  const rules = compatibility.by_category?.[category] ?? []
  return compatibility.results.filter((result) => rules.includes(result.rule))
}

/**
 * The worst status in a list of results; compatible when the list is empty.
 */
export function worstStatus(results) {
  return results.reduce(
    (worst, result) => (SEVERITY[result.status] > SEVERITY[worst] ? result.status : worst),
    'compatible',
  )
}
