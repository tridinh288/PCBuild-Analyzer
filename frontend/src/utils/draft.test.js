import { afterEach, describe, expect, it, vi } from 'vitest'
import { clearDraft, loadDraft, saveDraft } from './draft'

function memoryStorage() {
  const data = new Map()
  return {
    getItem: (key) => (data.has(key) ? data.get(key) : null),
    setItem: (key, value) => data.set(key, String(value)),
    removeItem: (key) => data.delete(key),
  }
}

describe('draft', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('saves, loads and clears the builder query', () => {
    vi.stubGlobal('localStorage', memoryStorage())

    saveDraft('cpu=3&ram=12x2')
    expect(loadDraft()).toBe('cpu=3&ram=12x2')

    clearDraft()
    expect(loadDraft()).toBeNull()
  })

  it('ignores corrupt data', () => {
    const storage = memoryStorage()
    storage.setItem('pcbuild:builder-draft', '{not json')
    vi.stubGlobal('localStorage', storage)

    expect(loadDraft()).toBeNull()
  })

  it('never throws when storage is blocked', () => {
    vi.stubGlobal('localStorage', {
      getItem: () => { throw new Error('SecurityError') },
      setItem: () => { throw new Error('QuotaExceededError') },
      removeItem: () => { throw new Error('SecurityError') },
    })

    expect(() => saveDraft('cpu=3')).not.toThrow()
    expect(loadDraft()).toBeNull()
  })
})
