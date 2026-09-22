import { useCallback, useMemo, useReducer } from 'react'
import { BUILDER_ACTIONS as A, builderReducer } from '../reducers/builderReducer'
import { api } from '../services/api'
import { toApiSelection } from '../utils/builderUrl'
import { useApi } from './useApi'
import { useDebouncedValue } from './useDebouncedValue'

/**
 * Editing a configuration with live analysis: the reducer, a debounced /builder/analyze call
 * and the slot actions. Shared by the public Builder (useBuilder adds URL, template and draft)
 * and the admin template editor.
 *
 * @param {Record<string, object>} slots  slot rules per category, from GET /categories
 * @param {object} initialState  initial reducer state
 * @param {string} profile  scoring profile for the analysis
 */
export function useConfigurationEditor(slots, initialState, profile) {
  const [state, dispatch] = useReducer(builderReducer, initialState)

  const selected = useMemo(() => toApiSelection(state.selection), [state.selection])
  const requestKey = useDebouncedValue(JSON.stringify({ selected, profile }), 250)
  const analysis = useApi((signal) => api.builderAnalyze(JSON.parse(requestKey), signal), requestKey)

  // Product details: from the analysis response, or from the picker right after a selection.
  const productsById = useMemo(() => {
    const map = {}
    Object.values(state.selection).flat().forEach((item) => item.product && (map[item.id] = item.product))
    analysis.data?.items?.forEach((item) => (map[item.product.id] = item.product))
    return map
  }, [state.selection, analysis.data])

  const missingIds = useMemo(
    () => new Set((analysis.meta.missing ?? []).map((item) => `${item.category}:${item.id}`)),
    [analysis.meta],
  )

  const selectPart = useCallback((category, product) => dispatch({
    type: A.SELECT_PART, payload: { category, product, slot: slots[category] },
  }), [slots])

  const removePart = useCallback((category, id) => dispatch({ type: A.REMOVE_PART, payload: { category, id } }), [])

  const setQuantity = useCallback((category, id, quantity) => dispatch({
    type: A.SET_QUANTITY, payload: { category, id, quantity, max: slots[category]?.max_quantity ?? 1 },
  }), [slots])

  return {
    state,
    dispatch,
    selection: state.selection,
    selected,
    productsById,
    missingIds,
    analysis,
    selectPart,
    removePart,
    setQuantity,
  }
}

/** Reducer selection from build items ({ category, quantity, product }). */
export function selectionFromItems(items) {
  const selection = {}
  items.forEach((item) => {
    selection[item.category] = [...(selection[item.category] ?? []),
      { id: item.product.id, quantity: item.quantity, product: item.product }]
  })
  return selection
}
