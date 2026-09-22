import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { useSearchParams } from 'react-router'
import { BUILDER_ACTIONS as A, builderReducer, initialBuilderState } from '../reducers/builderReducer'
import { api } from '../services/api'
import { parseSelection, serializeSelection, toApiSelection } from '../utils/builderUrl'
import { useApi } from './useApi'
import { useDebouncedValue } from './useDebouncedValue'

function selectionFromBuild(build) {
  const selection = {}
  build.items.forEach((item) => {
    selection[item.category] = [...(selection[item.category] ?? []),
      { id: item.product.id, quantity: item.quantity, product: item.product }]
  })
  return selection
}

/**
 * All Builder logic in one hook (D-020): reducer state, URL sync, template loading and the
 * debounced analysis request. Pages only render what it returns.
 *
 * @param {Record<string, object>} slots  slot rules per category, from GET /categories
 */
export function useBuilder(slots) {
  const [searchParams, setSearchParams] = useSearchParams()
  const templateSlug = searchParams.get('template')

  // Modes 2 and 3 (D-002): start from a template (loaded below) or from the IDs in the URL.
  const [state, dispatch] = useReducer(builderReducer, searchParams,
    (params) => ({ ...initialBuilderState, selection: parseSelection(params) }))
  const [templateError, setTemplateError] = useState(null)
  const [profile, setProfile] = useState(null)

  useEffect(() => {
    if (!templateSlug) return undefined

    const controller = new AbortController()
    api.build(templateSlug, controller.signal)
      .then(({ data }) => dispatch({
        type: A.LOAD_TEMPLATE,
        payload: { selection: selectionFromBuild(data), template: { slug: data.slug, name: data.name, purpose: data.purpose } },
      }))
      .catch((error) => !controller.signal.aborted && setTemplateError(error))

    return () => controller.abort()
  }, [templateSlug])

  const loadingTemplate = Boolean(templateSlug) && !state.template && !templateError

  // Keep the URL in sync so the configuration survives a refresh and can be shared.
  // The template param is replaced by explicit IDs once the template is loaded.
  useEffect(() => {
    if (loadingTemplate || templateError) return
    setSearchParams(serializeSelection(state.selection), { replace: true })
  }, [state.selection, loadingTemplate, templateError, setSearchParams])

  const selected = useMemo(() => toApiSelection(state.selection), [state.selection])
  const activeProfile = profile ?? state.template?.purpose ?? 'general_use'

  const requestKey = useDebouncedValue(JSON.stringify({ selected, profile: activeProfile }), 250)
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

  // Also clears the URL: a failed `?template=` must not be loaded again.
  const reset = useCallback(() => {
    setSearchParams(new URLSearchParams(), { replace: true })
    setTemplateError(null)
    setProfile(null)
    dispatch({ type: A.RESET })
  }, [setSearchParams])

  return {
    selection: state.selection,
    template: state.template,
    selected,
    productsById,
    missingIds,
    profile: activeProfile,
    setProfile,
    analysis,
    loadingTemplate,
    templateError,
    selectPart,
    removePart,
    setQuantity,
    reset,
  }
}
