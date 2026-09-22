<?php

/*
|--------------------------------------------------------------------------
| Hardware definitions — single source of truth (D-004, D-005)
|--------------------------------------------------------------------------
|
| Spec keys, labels, units, validation, filters, highlights, Builder slot
| rules, power constants and scoring constants. Read it through
| App\Support\Hardware\SpecSchema, not with config() calls scattered in code.
|
| Value conventions: numbers without units, enums as lowercase codes shared
| between categories, real booleans, arrays for multi-value specs.
|
| Spec definition keys:
|   label        Vietnamese display label
|   type         integer | boolean | enum | enum_list
|   enum         enum name from 'enums' (enum / enum_list)
|   unit         display unit
|   min, max     integer bounds
|   required     true | ['when' => [otherKey => value]]
|   highlight    shown on product cards
|   filter       exact | min | max | boolean | contains (omit = not filterable)
|   filter_param query parameter name (default: key; min/max: key_min / key_max)
|
| The order of keys is the display order.
|
*/

return [

    'enums' => [
        'socket' => [
            'am4' => 'AM4',
            'am5' => 'AM5',
            'lga1700' => 'LGA1700',
            'lga1851' => 'LGA1851',
        ],
        'ram_type' => [
            'ddr4' => 'DDR4',
            'ddr5' => 'DDR5',
        ],
        'board_form_factor' => [
            'atx' => 'ATX',
            'matx' => 'Micro-ATX',
            'itx' => 'Mini-ITX',
        ],
        'psu_form_factor' => [
            'atx' => 'ATX',
            'sfx' => 'SFX',
        ],
        'storage_interface' => [
            'nvme' => 'NVMe',
            'sata' => 'SATA',
        ],
        'storage_form' => [
            'm2' => 'M.2',
            '2_5' => '2.5"',
            '3_5' => '3.5"',
        ],
        'efficiency_rating' => [
            'white' => '80 Plus',
            'bronze' => '80 Plus Bronze',
            'silver' => '80 Plus Silver',
            'gold' => '80 Plus Gold',
            'platinum' => '80 Plus Platinum',
            'titanium' => '80 Plus Titanium',
        ],
        'cooler_type' => [
            'air' => 'Tản khí',
            'aio' => 'Tản nước AIO',
        ],
    ],

    // Default name and order are seeded into the categories table; the admin may edit them there.
    'categories' => [

        'cpu' => [
            'name' => 'CPU',
            'sort_order' => 1,
            'specs' => [
                'socket' => ['label' => 'Socket', 'type' => 'enum', 'enum' => 'socket',
                    'required' => true, 'filter' => 'exact', 'highlight' => true],
                'cores' => ['label' => 'Số nhân', 'type' => 'integer', 'min' => 1, 'max' => 128,
                    'required' => true, 'filter' => 'min', 'highlight' => true],
                'threads' => ['label' => 'Số luồng', 'type' => 'integer', 'min' => 1, 'max' => 256,
                    'required' => true],
                'tdp' => ['label' => 'TDP', 'type' => 'integer', 'unit' => 'W', 'min' => 1, 'max' => 500,
                    'required' => true, 'highlight' => true],
                'has_integrated_graphics' => ['label' => 'Đồ họa tích hợp', 'type' => 'boolean',
                    'required' => true, 'filter' => 'boolean'],
                'includes_cooler' => ['label' => 'Kèm tản nhiệt', 'type' => 'boolean', 'required' => true],
                'performance_tier' => ['label' => 'Hạng hiệu năng (dự án)', 'type' => 'integer',
                    'min' => 1, 'max' => 100, 'required' => true],
            ],
        ],

        'motherboard' => [
            'name' => 'Bo mạch chủ',
            'sort_order' => 2,
            'specs' => [
                'socket' => ['label' => 'Socket', 'type' => 'enum', 'enum' => 'socket',
                    'required' => true, 'filter' => 'exact', 'highlight' => true],
                'ram_type' => ['label' => 'Loại RAM', 'type' => 'enum', 'enum' => 'ram_type',
                    'required' => true, 'filter' => 'exact', 'highlight' => true],
                'ram_slots' => ['label' => 'Số khe RAM', 'type' => 'integer', 'min' => 1, 'max' => 8,
                    'required' => true],
                'max_ram_gb' => ['label' => 'RAM tối đa', 'type' => 'integer', 'unit' => 'GB',
                    'min' => 8, 'max' => 512, 'required' => true],
                'form_factor' => ['label' => 'Kích thước', 'type' => 'enum', 'enum' => 'board_form_factor',
                    'required' => true, 'filter' => 'exact', 'highlight' => true],
                'm2_slots' => ['label' => 'Khe M.2', 'type' => 'integer', 'min' => 0, 'max' => 8,
                    'required' => true],
                'sata_ports' => ['label' => 'Cổng SATA', 'type' => 'integer', 'min' => 0, 'max' => 12,
                    'required' => true],
            ],
        ],

        'ram' => [
            'name' => 'RAM',
            'sort_order' => 3,
            'specs' => [
                'ram_type' => ['label' => 'Loại RAM', 'type' => 'enum', 'enum' => 'ram_type',
                    'required' => true, 'filter' => 'exact', 'highlight' => true],
                'capacity_gb' => ['label' => 'Dung lượng (bộ kit)', 'type' => 'integer', 'unit' => 'GB',
                    'min' => 4, 'max' => 256, 'required' => true, 'filter' => 'exact', 'highlight' => true],
                'modules' => ['label' => 'Số thanh (bộ kit)', 'type' => 'integer', 'min' => 1, 'max' => 8,
                    'required' => true],
                'speed_mhz' => ['label' => 'Tốc độ', 'type' => 'integer', 'unit' => 'MHz',
                    'min' => 1600, 'max' => 10000, 'required' => true, 'filter' => 'min', 'highlight' => true],
            ],
        ],

        'gpu' => [
            'name' => 'Card đồ họa',
            'sort_order' => 4,
            'specs' => [
                'vram_gb' => ['label' => 'VRAM', 'type' => 'integer', 'unit' => 'GB', 'min' => 1, 'max' => 48,
                    'required' => true, 'filter' => 'min', 'highlight' => true],
                'length_mm' => ['label' => 'Chiều dài', 'type' => 'integer', 'unit' => 'mm',
                    'min' => 100, 'max' => 450, 'required' => true, 'filter' => 'max'],
                'tdp' => ['label' => 'TDP', 'type' => 'integer', 'unit' => 'W', 'min' => 10, 'max' => 700,
                    'required' => true, 'highlight' => true],
                'performance_tier' => ['label' => 'Hạng hiệu năng (dự án)', 'type' => 'integer',
                    'min' => 1, 'max' => 100, 'required' => true],
            ],
        ],

        'storage' => [
            'name' => 'Ổ cứng',
            'sort_order' => 5,
            'specs' => [
                'interface' => ['label' => 'Chuẩn giao tiếp', 'type' => 'enum', 'enum' => 'storage_interface',
                    'required' => true, 'filter' => 'exact', 'highlight' => true],
                'form' => ['label' => 'Kiểu dáng', 'type' => 'enum', 'enum' => 'storage_form',
                    'required' => true],
                'capacity_gb' => ['label' => 'Dung lượng', 'type' => 'integer', 'unit' => 'GB',
                    'min' => 64, 'max' => 32000, 'required' => true, 'filter' => 'min', 'highlight' => true],
            ],
        ],

        'psu' => [
            'name' => 'Nguồn',
            'sort_order' => 6,
            'specs' => [
                'wattage' => ['label' => 'Công suất', 'type' => 'integer', 'unit' => 'W',
                    'min' => 200, 'max' => 2000, 'required' => true, 'filter' => 'min', 'highlight' => true],
                'form_factor' => ['label' => 'Kích thước', 'type' => 'enum', 'enum' => 'psu_form_factor',
                    'required' => true, 'filter' => 'exact'],
                'efficiency_rating' => ['label' => 'Chứng nhận hiệu suất', 'type' => 'enum',
                    'enum' => 'efficiency_rating', 'required' => true, 'highlight' => true],
            ],
        ],

        'case' => [
            'name' => 'Vỏ case',
            'sort_order' => 7,
            'specs' => [
                'supported_form_factors' => ['label' => 'Bo mạch hỗ trợ', 'type' => 'enum_list',
                    'enum' => 'board_form_factor', 'required' => true, 'filter' => 'contains',
                    'filter_param' => 'form_factor', 'highlight' => true],
                'max_gpu_length_mm' => ['label' => 'Chiều dài GPU tối đa', 'type' => 'integer', 'unit' => 'mm',
                    'min' => 100, 'max' => 500, 'required' => true, 'filter' => 'min'],
                'max_cooler_height_mm' => ['label' => 'Chiều cao tản tối đa', 'type' => 'integer',
                    'unit' => 'mm', 'min' => 30, 'max' => 200, 'required' => true],
                'supported_psu_form_factors' => ['label' => 'Nguồn hỗ trợ', 'type' => 'enum_list',
                    'enum' => 'psu_form_factor', 'required' => true],
            ],
        ],

        'cooler' => [
            'name' => 'Tản nhiệt CPU',
            'sort_order' => 8,
            'specs' => [
                'type' => ['label' => 'Loại tản', 'type' => 'enum', 'enum' => 'cooler_type',
                    'required' => true, 'filter' => 'exact', 'highlight' => true],
                'supported_sockets' => ['label' => 'Socket hỗ trợ', 'type' => 'enum_list', 'enum' => 'socket',
                    'required' => true, 'filter' => 'contains', 'filter_param' => 'socket'],
                'max_tdp' => ['label' => 'TDP tối đa', 'type' => 'integer', 'unit' => 'W',
                    'min' => 30, 'max' => 400, 'required' => true, 'highlight' => true],
                'height_mm' => ['label' => 'Chiều cao', 'type' => 'integer', 'unit' => 'mm',
                    'min' => 20, 'max' => 200, 'required' => ['when' => ['type' => 'air']]],
                'radiator_mm' => ['label' => 'Kích thước radiator', 'type' => 'integer', 'unit' => 'mm',
                    'min' => 120, 'max' => 420, 'required' => ['when' => ['type' => 'aio']]],
            ],
        ],
    ],

    /*
    | Builder slots. `required` is true or a named condition interpreted by
    | App\Domain\Configuration\SlotRules. `multiple`: false | 'quantity' (one
    | product, several units) | 'items' (several products).
    */
    'slots' => [
        'cpu' => ['required' => true, 'multiple' => false],
        'motherboard' => ['required' => true, 'multiple' => false],
        'ram' => ['required' => true, 'multiple' => 'quantity', 'max_quantity' => 4],
        'gpu' => ['required' => 'unless_cpu_has_integrated_graphics', 'multiple' => false],
        'storage' => ['required' => true, 'multiple' => 'items', 'max_quantity' => 6],
        'psu' => ['required' => true, 'multiple' => false],
        'case' => ['required' => true, 'multiple' => false],
        'cooler' => ['required' => 'unless_cpu_includes_cooler', 'multiple' => false],
    ],

    // Power estimate constants in watts (D-015). CPU and GPU use their own `tdp` spec.
    'power' => [
        'ram_per_module' => 5,
        'storage' => ['nvme' => 7, 'sata' => 5],
        'motherboard' => 50,
        'fans' => 15,
        'safety_factor' => 1.25,
        'psu_steps' => [450, 550, 650, 750, 850, 1000, 1200, 1600],
    ],

    // Project-defined scoring rules, not benchmarks (D-015, D-031). Tuned in Phase 3.
    'scoring' => [
        'integrated_graphics_score' => 15,
        'ram_capacity_scores' => [8 => 30, 16 => 60, 32 => 85, 64 => 100], // total GB => score
        'ram_type_bonus' => ['ddr4' => 0, 'ddr5' => 10],
        'storage_interface_scores' => ['sata' => 50, 'nvme' => 80],
        'storage_capacity_bonus' => [1000 => 10, 2000 => 20], // total GB => bonus
        'weights' => [
            'gaming' => ['cpu' => 0.30, 'gpu' => 0.45, 'ram' => 0.15, 'storage' => 0.10],
            'programming' => ['cpu' => 0.40, 'gpu' => 0.10, 'ram' => 0.30, 'storage' => 0.20],
            'workstation' => ['cpu' => 0.40, 'gpu' => 0.20, 'ram' => 0.30, 'storage' => 0.10],
            'general_use' => ['cpu' => 0.25, 'gpu' => 0.25, 'ram' => 0.25, 'storage' => 0.25],
        ],
        'adjustments' => [
            'gaming' => ['max_cpu_gpu_tier_gap' => 30, 'penalty' => 10],
            'programming' => ['min_ram_gb' => 16, 'penalty' => 10],
            'workstation' => ['min_ram_gb' => 32, 'penalty' => 10],
        ],
    ],

];
