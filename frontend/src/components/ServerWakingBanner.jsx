import { useServerWaking } from '../hooks/useServerWaking'

/**
 * Shown while a request has been pending for a while: the free Render instance is waking up.
 */
export default function ServerWakingBanner() {
  const waking = useServerWaking()

  if (!waking) {
    return null
  }

  return (
    <div className="bg-amber-100 px-4 py-2 text-center text-sm text-amber-900" role="status">
      Máy chủ đang khởi động, vui lòng chờ khoảng 1 phút…
    </div>
  )
}
