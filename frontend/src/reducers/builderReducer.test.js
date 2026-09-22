import { describe, expect, it } from 'vitest'
import { BUILDER_ACTIONS as A, builderReducer, initialBuilderState } from './builderReducer'

const SINGLE = { multiple: false, accepts_quantity: false, max_quantity: 1 }
const RAM = { multiple: false, accepts_quantity: true, max_quantity: 4 }
const STORAGE = { multiple: true, accepts_quantity: true, max_quantity: 6 }

const product = (id) => ({ id, name: `Product ${id}` })
const select = (state, category, id, slot) => builderReducer(state, { type: A.SELECT_PART, payload: { category, product: product(id), slot } })

describe('builderReducer', () => {
  it('does not mutate the previous state', () => {
    const before = structuredClone(initialBuilderState)
    select(initialBuilderState, 'cpu', 3, SINGLE)
    expect(initialBuilderState).toEqual(before)
  })

  it('replaces a single slot', () => {
    const state = select(select(initialBuilderState, 'cpu', 3, SINGLE), 'cpu', 5, SINGLE)
    expect(state.selection.cpu.map((item) => item.id)).toEqual([5])
  })

  it('keeps the RAM kit count when the kit is replaced', () => {
    let state = select(initialBuilderState, 'ram', 12, RAM)
    state = builderReducer(state, { type: A.SET_QUANTITY, payload: { category: 'ram', id: 12, quantity: 2, max: 4 } })
    state = select(state, 'ram', 13, RAM)
    expect(state.selection.ram).toMatchObject([{ id: 13, quantity: 2 }])
  })

  it('appends to multi-product slots without duplicates', () => {
    let state = select(initialBuilderState, 'storage', 4, STORAGE)
    state = select(state, 'storage', 9, STORAGE)
    const same = select(state, 'storage', 9, STORAGE)
    expect(state.selection.storage.map((item) => item.id)).toEqual([4, 9])
    expect(same).toBe(state)
  })

  it('clamps quantities to the slot limit', () => {
    const state = select(initialBuilderState, 'ram', 12, RAM)
    const tooMany = builderReducer(state, { type: A.SET_QUANTITY, payload: { category: 'ram', id: 12, quantity: 9, max: 4 } })
    const tooFew = builderReducer(state, { type: A.SET_QUANTITY, payload: { category: 'ram', id: 12, quantity: 0, max: 4 } })
    expect(tooMany.selection.ram[0].quantity).toBe(4)
    expect(tooFew.selection.ram[0].quantity).toBe(1)
  })

  it('removes one product or a whole slot', () => {
    let state = select(select(initialBuilderState, 'storage', 4, STORAGE), 'storage', 9, STORAGE)
    state = builderReducer(state, { type: A.REMOVE_PART, payload: { category: 'storage', id: 4 } })
    expect(state.selection.storage.map((item) => item.id)).toEqual([9])

    state = builderReducer(state, { type: A.REMOVE_PART, payload: { category: 'storage' } })
    expect(state.selection).not.toHaveProperty('storage')
  })

  it('loads a template and a URL selection', () => {
    const selection = { cpu: [{ id: 3, quantity: 1 }] }
    const template = { slug: 'gaming-1080p-am5', name: 'Gaming 1080p AM5', purpose: 'gaming' }

    expect(builderReducer(initialBuilderState, { type: A.LOAD_TEMPLATE, payload: { selection, template } }))
      .toEqual({ selection, template })
    expect(builderReducer({ selection: {}, template }, { type: A.LOAD_FROM_URL, payload: { selection } }))
      .toEqual({ selection, template: null })
  })

  it('resets and rejects unknown actions', () => {
    const state = select(initialBuilderState, 'cpu', 3, SINGLE)
    expect(builderReducer(state, { type: A.RESET })).toEqual(initialBuilderState)
    expect(() => builderReducer(state, { type: 'NOPE' })).toThrow()
  })
})
