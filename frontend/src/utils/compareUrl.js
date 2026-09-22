import { isEmptySelection, parseSelection, serializeSelection, toApiSelection } from './builderUrl'

/**
 * Comparison state in the URL, one `c` parameter per configuration (max 3):
 *
 *   /compare?c=gaming-1080p-am5&c=~cpu=3&ram=12x2
 *
 * A plain value is a template slug; a value starting with "~" is a custom configuration in
 * the Builder URL format.
 */

export const MAX_CONFIGURATIONS = 3

const SLUG = /^[a-z0-9-]+$/

export function parseComparison(searchParams) {
  return searchParams.getAll('c').slice(0, MAX_CONFIGURATIONS).map((value) => {
    if (value.startsWith('~')) {
      const selection = parseSelection(value.slice(1))
      return isEmptySelection(selection) ? null : { type: 'custom', selection }
    }
    return SLUG.test(value) ? { type: 'template', slug: value } : null
  }).filter(Boolean)
}

export function serializeComparison(entries) {
  const params = new URLSearchParams()
  entries.forEach((entry) => {
    params.append('c', entry.type === 'template' ? entry.slug : `~${serializeSelection(entry.selection)}`)
  })
  return params
}

/** Request body item for POST /compare */
export function toApiConfiguration(entry, index) {
  return entry.type === 'template'
    ? { type: 'template', slug: entry.slug }
    : { type: 'custom', selected: toApiSelection(entry.selection), label: `Cấu hình tùy chỉnh ${index + 1}` }
}
