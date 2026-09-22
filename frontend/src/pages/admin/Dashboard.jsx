import { Link } from 'react-router'
import { useAuth } from '../../hooks/useAuth'

const SECTIONS = [
  { to: '/admin/products', title: 'Linh kiện', text: 'Thêm, sửa, ngừng bán linh kiện; thông số theo từng loại; hình ảnh.' },
  { to: '/admin/builds', title: 'Cấu hình mẫu', text: 'Tạo cấu hình mẫu, chọn linh kiện, kiểm tra tương thích khi sửa.' },
  { to: '/admin/categories', title: 'Danh mục', text: 'Đổi tên, mô tả và thứ tự hiển thị của 8 loại linh kiện.' },
]

export default function Dashboard() {
  const { user } = useAuth()

  return (
    <div>
      <h1 className="text-2xl font-bold">Xin chào{user ? `, ${user.name}` : ''}</h1>
      <p className="mt-1 text-slate-600">Khu vực quản trị chỉ dùng để quản lý dữ liệu cho bộ phân tích.</p>
      <div className="mt-6 grid gap-4 md:grid-cols-3">
        {SECTIONS.map((section) => (
          <Link key={section.to} to={section.to} className="rounded-xl bg-white p-5 shadow-sm hover:ring-2 hover:ring-brand-200">
            <h2 className="font-semibold">{section.title}</h2>
            <p className="mt-1 text-sm text-slate-600">{section.text}</p>
          </Link>
        ))}
      </div>
    </div>
  )
}
