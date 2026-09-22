/**
 * Builder draft in localStorage (spec section 21). Storage can be unavailable or throw
 * (private mode, blocked site data, quota), so every access is guarded and failures are silent:
 * the draft is a convenience, the URL is the real state.
 */

const KEY = 'pcbuild:builder-draft'

/** @param {string} query  serialized selection, e.g. "cpu=3&ram=12x2" */
export function saveDraft(query) {
  try {
    if (query) {
      localStorage.setItem(KEY, JSON.stringify({ query, savedAt: Date.now() }))
    } else {
      localStorage.removeItem(KEY)
    }
  } catch {
    // Ignore: drafts are optional.
  }
}

/** @returns {string|null} the saved query string */
export function loadDraft() {
  try {
    const raw = localStorage.getItem(KEY)
    const draft = raw ? JSON.parse(raw) : null
    return typeof draft?.query === 'string' ? draft.query : null
  } catch {
    return null
  }
}

export function clearDraft() {
  saveDraft('')
}
