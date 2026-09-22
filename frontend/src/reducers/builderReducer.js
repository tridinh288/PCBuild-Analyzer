/**
 * Builder state (D-020). Pure: no API calls, no URL or storage access — those live in
 * useBuilder(). Slot behaviour comes from the API categories (`slot` in the action payload).
 *
 * state = {
 *   selection: { [category]: [{ id, quantity, product? }] },  // product = picker data, optional
 *   template: { slug, name, purpose } | null                    // set when customizing a template
 * }
 */

export const initialBuilderState = { selection: {}, template: null }

export const BUILDER_ACTIONS = {
  SELECT_PART: 'SELECT_PART',
  REMOVE_PART: 'REMOVE_PART',
  SET_QUANTITY: 'SET_QUANTITY',
  LOAD_TEMPLATE: 'LOAD_TEMPLATE',
  LOAD_FROM_URL: 'LOAD_FROM_URL',
  RESET: 'RESET',
}

function clamp(quantity, max) {
  return Math.min(Math.max(1, Math.trunc(quantity) || 1), max ?? 1)
}

function withSlot(state, category, items) {
  const selection = { ...state.selection }

  if (items.length === 0) {
    delete selection[category]
  } else {
    selection[category] = items
  }

  return { ...state, selection }
}

export function builderReducer(state, action) {
  switch (action.type) {
    // payload: { category, product, slot: { multiple, max_quantity } }
    case BUILDER_ACTIONS.SELECT_PART: {
      const { category, product, slot } = action.payload
      const current = state.selection[category] ?? []

      if (slot?.multiple) {
        if (current.some((item) => item.id === product.id)) {
          return state
        }
        return withSlot(state, category, [...current, { id: product.id, quantity: 1, product }])
      }

      // Replacing a RAM kit keeps the number of kits already chosen.
      const quantity = clamp(current[0]?.quantity ?? 1, slot?.accepts_quantity ? slot.max_quantity : 1)
      return withSlot(state, category, [{ id: product.id, quantity, product }])
    }

    // payload: { category, id? } — without id the whole slot is cleared
    case BUILDER_ACTIONS.REMOVE_PART: {
      const { category, id } = action.payload
      const current = state.selection[category] ?? []
      return withSlot(state, category, id === undefined ? [] : current.filter((item) => item.id !== id))
    }

    // payload: { category, id, quantity, max }
    case BUILDER_ACTIONS.SET_QUANTITY: {
      const { category, id, quantity, max } = action.payload
      const current = state.selection[category] ?? []
      return withSlot(state, category, current.map((item) => (item.id === id ? { ...item, quantity: clamp(quantity, max) } : item)))
    }

    // payload: { selection, template }
    case BUILDER_ACTIONS.LOAD_TEMPLATE:
      return { selection: action.payload.selection, template: action.payload.template }

    // payload: { selection }
    case BUILDER_ACTIONS.LOAD_FROM_URL:
      return { selection: action.payload.selection, template: null }

    case BUILDER_ACTIONS.RESET:
      return initialBuilderState

    default:
      throw new Error(`Unknown builder action: ${action.type}`)
  }
}
