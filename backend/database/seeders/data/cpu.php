<?php

// Demo data: approximate specs and prices (VND), entered by hand. performance_tier is project-defined.
return [
    ['name' => 'AMD Ryzen 5 5600', 'brand' => 'AMD', 'model' => '5600', 'price' => 2_890_000,
        'specs' => ['socket' => 'am4', 'cores' => 6, 'threads' => 12, 'tdp' => 65,
            'has_integrated_graphics' => false, 'includes_cooler' => true, 'performance_tier' => 50]],
    ['name' => 'AMD Ryzen 5 7600', 'brand' => 'AMD', 'model' => '7600', 'price' => 5_290_000,
        'specs' => ['socket' => 'am5', 'cores' => 6, 'threads' => 12, 'tdp' => 65,
            'has_integrated_graphics' => true, 'includes_cooler' => true, 'performance_tier' => 62]],
    ['name' => 'AMD Ryzen 7 7700', 'brand' => 'AMD', 'model' => '7700', 'price' => 7_490_000,
        'specs' => ['socket' => 'am5', 'cores' => 8, 'threads' => 16, 'tdp' => 65,
            'has_integrated_graphics' => true, 'includes_cooler' => true, 'performance_tier' => 70]],
    ['name' => 'AMD Ryzen 7 7800X3D', 'brand' => 'AMD', 'model' => '7800X3D', 'price' => 10_990_000,
        'specs' => ['socket' => 'am5', 'cores' => 8, 'threads' => 16, 'tdp' => 120,
            'has_integrated_graphics' => true, 'includes_cooler' => false, 'performance_tier' => 85]],
    ['name' => 'Intel Core i3-12100F', 'brand' => 'Intel', 'model' => 'i3-12100F', 'price' => 2_190_000,
        'specs' => ['socket' => 'lga1700', 'cores' => 4, 'threads' => 8, 'tdp' => 58,
            'has_integrated_graphics' => false, 'includes_cooler' => true, 'performance_tier' => 35]],
    ['name' => 'Intel Core i5-13400', 'brand' => 'Intel', 'model' => 'i5-13400', 'price' => 5_190_000,
        'specs' => ['socket' => 'lga1700', 'cores' => 10, 'threads' => 16, 'tdp' => 65,
            'has_integrated_graphics' => true, 'includes_cooler' => true, 'performance_tier' => 60]],
    ['name' => 'Intel Core i7-14700K', 'brand' => 'Intel', 'model' => 'i7-14700K', 'price' => 9_990_000,
        'specs' => ['socket' => 'lga1700', 'cores' => 20, 'threads' => 28, 'tdp' => 125,
            'has_integrated_graphics' => true, 'includes_cooler' => false, 'performance_tier' => 88]],
    ['name' => 'Intel Core Ultra 5 245K', 'brand' => 'Intel', 'model' => '245K', 'price' => 7_990_000,
        'specs' => ['socket' => 'lga1851', 'cores' => 14, 'threads' => 14, 'tdp' => 125,
            'has_integrated_graphics' => true, 'includes_cooler' => false, 'performance_tier' => 75]],
];
