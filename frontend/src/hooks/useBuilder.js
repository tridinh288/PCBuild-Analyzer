import { useCallback, useEffect, useState } from 'react'
import { useSearchParams } from 'react-router'
import { BUILDER_ACTIONS as A, initialBuilderState } from '../reducers/builderReducer'
import { api } from '../services/api'
import { isEmptySelection, parseSelection, serializeSelection } from '../utils/builderUrl'
import { clearDraft, loadDraft, saveDraft } from '../utils/draft'
import { selectionFromItems, useConfigurationEditor } from './useConfigurationEditor'

/**
 * The public Builder (D-020): configuration editing plus template loading, URL sync and draft
 * autosave. Pages only render what it returns.
 *
 * @param {Record<string, object>} slots  slot rules per category, from GET /categories
 */
export function useBuilder(slots) {
  const [searchParams, setSearchParams] = useSearchParams()
  const templateSlug = searchParams.get('template')

  // Modes 2 and 3 (D-002): start from a template (loaded below), from the IDs in the URL, or —
  // only when the URL has neither — from the saved draft.
  const [initial] = useState(() => {
    const fromUrl = parseSelection(searchParams)
    if (templateSlug || !isEmptySelection(fromUrl)) {
      return { selection: fromUrl, fromDraft: false }
    }
    const draft = loadDraft()
    const fromDraft = draft ? parseSelection(draft) : {}
    return { selection: fromDraft, fromDraft: !isEmptySelection(fromDraft) }
  })
  const [draftRestored, setDraftRestored] = useState(initial.fromDraft)
  const [templateError, setTemplateError] = useState(null)
  const [profile, setProfile] = useState(null)
  const [templatePurpose, setTemplatePurpose] = useState(null)

  const activeProfile = profile ?? templatePurpose ?? 'general_use'
  const editor = useConfigurationEditor(slots, { ...initialBuilderState, selection: initial.selection }, activeProfile)
  const { state, dispatch } = editor

  useEffect(() => {
    if (!templateSlug) return undefined

    const controller = new AbortController()
    api.build(templateSlug, controller.signal)
      .then(({ data }) => {
        setTemplatePurpose(data.purpose)
        dispatch({
          type: A.LOAD_TEMPLATE,
          payload: { selection: selectionFromItems(data.items), template: { slug: data.slug, name: data.name, purpose: data.purpose } },
        })
      })
      .catch((error) => !controller.signal.aborted && setTemplateError(error))

    return () => controller.abort()
  }, [templateSlug, dispatch])

  const loadingTemplate = Boolean(templateSlug) && !state.template && !templateError

  // Keep the URL in sync so the configuration survives a refresh and can be shared; the same
  // query string is autosaved as the draft. `?template=` is replaced by explicit IDs once loaded.
  useEffect(() => {
    if (loadingTemplate || templateError) return
    const query = serializeSelection(state.selection)
    setSearchParams(query, { replace: true })
    saveDraft(query.toString())
  }, [state.selection, loadingTemplate, templateError, setSearchParams])

  // Also clears the URL: a failed `?template=` must not be loaded again.
  const reset = useCallback(() => {
    setSearchParams(new URLSearchParams(), { replace: true })
    clearDraft()
    setDraftRestored(false)
    setTemplateError(null)
    setProfile(null)
    setTemplatePurpose(null)
    dispatch({ type: A.RESET })
  }, [setSearchParams, dispatch])

  const dismissDraftNotice = useCallback(() => setDraftRestored(false), [])

  return {
    ...editor,
    template: state.template,
    profile: activeProfile,
    setProfile,
    loadingTemplate,
    templateError,
    draftRestored,
    dismissDraftNotice,
    reset,
  }
}
