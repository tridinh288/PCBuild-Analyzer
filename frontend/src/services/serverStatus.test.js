import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { isWaking, SLOW_REQUEST_MS, subscribe, trackRequest } from './serverStatus'

describe('serverStatus', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => vi.useRealTimers())

  it('is not waking for fast requests', () => {
    const done = trackRequest()
    vi.advanceTimersByTime(SLOW_REQUEST_MS - 1)
    done()
    vi.advanceTimersByTime(SLOW_REQUEST_MS)

    expect(isWaking()).toBe(false)
  })

  it('reports waking while a slow request is pending and notifies subscribers', () => {
    const listener = vi.fn()
    const unsubscribe = subscribe(listener)

    const done = trackRequest()
    vi.advanceTimersByTime(SLOW_REQUEST_MS)
    expect(isWaking()).toBe(true)

    done()
    expect(isWaking()).toBe(false)
    expect(listener).toHaveBeenCalledTimes(2)

    unsubscribe()
  })

  it('stays waking until every slow request has finished', () => {
    const first = trackRequest()
    const second = trackRequest()
    vi.advanceTimersByTime(SLOW_REQUEST_MS)

    first()
    expect(isWaking()).toBe(true)
    second()
    expect(isWaking()).toBe(false)
  })
})
