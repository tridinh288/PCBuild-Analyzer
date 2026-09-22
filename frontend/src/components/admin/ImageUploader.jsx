import { useState } from 'react'
import ImagePlaceholder from '../ImagePlaceholder'

const TYPES = ['image/jpeg', 'image/png', 'image/webp']
const MAX_BYTES = 2 * 1024 * 1024

/**
 * Upload / replace / remove one image. The same limits are enforced by the API; checking them
 * here only avoids a pointless upload.
 */
export default function ImageUploader({ image, category = 'build', onUpload, onRemove }) {
  const [status, setStatus] = useState(null)
  const [busy, setBusy] = useState(false)

  const run = async (action, success) => {
    setBusy(true)
    setStatus(null)
    try {
      await action()
      setStatus(success)
    } catch (error) {
      setStatus(error.errors?.image?.[0] ?? error.message)
    } finally {
      setBusy(false)
    }
  }

  const choose = (event) => {
    const file = event.target.files?.[0]
    event.target.value = ''
    if (!file) return

    if (!TYPES.includes(file.type)) {
      setStatus('Chỉ nhận ảnh JPG, PNG hoặc WebP.')
      return
    }
    if (file.size > MAX_BYTES) {
      setStatus('Ảnh tối đa 2 MB.')
      return
    }

    run(() => onUpload(file), 'Đã cập nhật ảnh.')
  }

  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
      {image?.thumb
        ? <img src={image.thumb} alt="" className="aspect-[4/3] w-40 rounded-lg object-cover" />
        : <ImagePlaceholder category={category} className="aspect-[4/3] w-40" />}
      <div className="space-y-2">
        <div className="flex flex-wrap gap-2">
          <label className={`cursor-pointer rounded-lg px-3 py-2 text-sm font-medium ring-1 ring-slate-300 hover:bg-slate-100 ${busy ? 'pointer-events-none opacity-50' : ''}`}>
            {image ? 'Thay ảnh' : 'Tải ảnh lên'}
            <input type="file" accept={TYPES.join(',')} className="sr-only" onChange={choose} disabled={busy} />
          </label>
          {image && (
            <button type="button" disabled={busy} onClick={() => run(onRemove, 'Đã xóa ảnh.')}
              className="rounded-lg px-3 py-2 text-sm font-medium text-red-700 ring-1 ring-red-200 hover:bg-red-50 disabled:opacity-50">
              Xóa ảnh
            </button>
          )}
        </div>
        <p className="text-xs text-slate-500">JPG, PNG hoặc WebP, tối đa 2 MB. Không dùng ảnh có bản quyền của nhà sản xuất.</p>
        {busy && <p className="text-xs text-slate-500">Đang xử lý…</p>}
        {status && <p className="text-xs text-slate-700" role="status">{status}</p>}
      </div>
    </div>
  )
}
