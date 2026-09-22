/**
 * The Builder configuration in the URL (D-019):
 *
 *   /builder?cpu=3&motherboard=7&ram=12x2&storage=4,9x2
 *
 * Each slot value is a comma-separated list of `<id>` or `<id>x<quantity>`.
 * A selection is `{ [category]: [{ id, quantity }] }` — the same shape the reducer uses.
 */

export const SLOT_ORDER = ['cpu', 'motherboard', 'ram', 'gpu', 'storage', 'psu', 'case', 'cooler']

const ITEM = /^(\d+)(?:x(\d+))?$/

/**
 * Reads a selection from URLSearchParams (or a query string). Unknown slots and malformed
 * values are ignored, so a hand-edited link never crashes the page.
 */
export function parseSelection(params) {
  const search = typeof params === 'string' ? new URLSearchParams(params) : params
  const selection = {}

  SLOT_ORDER.forEach((category) => {
    const raw = search.get(category)
    if (!raw) return

    const items = []
    raw.split(',').forEach((token) => {
      const match = ITEM.exec(token.trim())
      if (!match) return

      const id = Number(match[1])
      const quantity = match[2] ? Number(match[2]) : 1
      if (id > 0 && quantity > 0 && !items.some((item) => item.id === id)) {
        items.push({ id, quantity })
      }
    })

    if (items.length > 0) {
      selection[category] = items
    }
  })

  return selection
}

/**
 * Selection → URLSearchParams in slot order (stable, readable links).
 */
export function serializeSelection(selection) {
  const params = new URLSearchParams()

  SLOT_ORDER.forEach((category) => {
    const items = selection[category]
    if (items?.length) {
      params.set(category, items.map((item) => (item.quantity > 1 ? `${item.id}x${item.quantity}` : `${item.id}`)).join(','))
    }
  })

  return params
}

/**
 * Selection → request body for POST /builder/* and /compare (docs/API.md § Selection format).
 */
export function toApiSelection(selection) {
  const body = {}

  Object.entries(selection).forEach(([category, items]) => {
    if (!items?.length) return

    if (category === 'storage') {
      body.storage = items.map((item) => ({ id: item.id, quantity: item.quantity }))
    } else {
      const [item] = items
      body[category] = item.quantity > 1 ? { id: item.id, quantity: item.quantity } : item.id
    }
  })

  return body
}

export function isEmptySelection(selection) {
  return Object.values(selection).every((items) => !items?.length)
}
