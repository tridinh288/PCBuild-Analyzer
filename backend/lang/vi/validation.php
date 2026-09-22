<?php

/*
| Vietnamese validation messages for the rules this API uses (D-003).
| Rules not listed here fall back to Laravel's English messages.
*/

return [
    'accepted' => ':attribute phải được chấp nhận.',
    'array' => ':attribute phải là một mảng hoặc đối tượng.',
    'boolean' => ':attribute phải là true hoặc false.',
    'distinct' => ':attribute có giá trị bị trùng.',
    'email' => ':attribute phải là địa chỉ email hợp lệ.',
    'enum' => ':attribute không hợp lệ.',
    'exists' => ':attribute không tồn tại.',
    'file' => ':attribute phải là một tệp tin.',
    'image' => ':attribute phải là hình ảnh.',
    'in' => ':attribute không hợp lệ.',
    'integer' => ':attribute phải là số nguyên.',
    'mimes' => ':attribute phải có định dạng: :values.',
    'numeric' => ':attribute phải là số.',
    'present' => 'Trường :attribute phải có mặt.',
    'required' => 'Trường :attribute là bắt buộc.',
    'required_if' => 'Trường :attribute là bắt buộc khi :other là :value.',
    'string' => ':attribute phải là chuỗi ký tự.',
    'unique' => ':attribute đã tồn tại.',
    'uploaded' => 'Tải lên :attribute thất bại.',

    'min' => [
        'array' => ':attribute phải có ít nhất :min phần tử.',
        'file' => ':attribute phải có dung lượng tối thiểu :min KB.',
        'numeric' => ':attribute phải lớn hơn hoặc bằng :min.',
        'string' => ':attribute phải có ít nhất :min ký tự.',
    ],

    'max' => [
        'array' => ':attribute không được nhiều hơn :max phần tử.',
        'file' => ':attribute không được lớn hơn :max KB.',
        'numeric' => ':attribute không được lớn hơn :max.',
        'string' => ':attribute không được dài quá :max ký tự.',
    ],

    'custom' => [],

    'attributes' => [
        'category' => 'loại linh kiện',
        'selected' => 'cấu hình',
        'profile' => 'mục đích sử dụng',
        'purpose' => 'mục đích sử dụng',
        'search' => 'từ khóa',
        'brand' => 'thương hiệu',
        'price_min' => 'giá tối thiểu',
        'price_max' => 'giá tối đa',
        'sort' => 'kiểu sắp xếp',
        'page' => 'trang',
        'per_page' => 'số mục mỗi trang',
        'featured' => 'nổi bật',
        'compatible_only' => 'chỉ hiện linh kiện tương thích',
        'configurations' => 'danh sách cấu hình',
        'configurations.*.type' => 'loại cấu hình',
        'configurations.*.slug' => 'mã cấu hình mẫu',
        'configurations.*.selected' => 'cấu hình tùy chỉnh',
        'configurations.*.label' => 'tên cấu hình',
        'email' => 'email',
        'password' => 'mật khẩu',
        'name' => 'tên',
        'price' => 'giá',
        'description' => 'mô tả',
        'image' => 'hình ảnh',
    ],
];
