<?php
class CalculatorConfig {
    public static $regions = [
        1 => ['name' => 'Ленинградская область', 'maxTonns' => 500],
        2 => ['name' => 'Московская область', 'maxTonns' => 500],
        3 => ['name' => 'Свердловская область', 'maxTonns' => 500]
    ];
    
    public static $fuelTypes = [
        'benzin' => [
            'name' => 'Бензин',
            'price' => 500200,
            'brands' => ['rosneft', 'tatneft', 'lukoil'],
            'tariffs' => ['econom' => 100, 'selected' => 300, 'premium' => 300]
        ],
        'gas' => [
            'name' => 'Газ',
            'price' => 200100,
            'brands' => ['shell', 'gazprom', 'bashneft'],
            'tariffs' => ['econom' => 200, 'selected' => 700, 'premium' => 700]
        ],
        'dt' => [
            'name' => 'ДТ',
            'price' => 320700,
            'brands' => ['tatneft', 'lukoil'],
            'tariffs' => ['econom' => 150, 'selected' => 350, 'premium' => 350]
        ]
    ];
    
    public static $brands = [
        'shell' => ['name' => 'Shell', 'icon' => 'S'],
        'gazprom' => ['name' => 'Газпром', 'icon' => 'Г'],
        'rosneft' => ['name' => 'Роснефть', 'icon' => 'Р'],
        'tatneft' => ['name' => 'Татнефть', 'icon' => 'Т'],
        'lukoil' => ['name' => 'Лукойл', 'icon' => 'Л'],
        'bashneft' => ['name' => 'Башнефть', 'icon' => 'Б']
    ];
    
    public static $services = [
        'shlangi' => 'Шланги',
        'parking' => 'Парковки',
        'edo' => 'ЭДО',
        'wash' => 'Мойки',
        'otgruzka' => 'Отсрочка',
        'telematika' => 'Телематика',
        'pripay' => 'PRIPAY',
        'sms' => 'СМС',
        'strakhovka' => 'Страховка'
    ];
    
    public static $tariffDiscounts = [
        'econom' => 3,
        'selected' => 5,
        'premium' => 7
    ];
    
    public static $promoActions = [
        'econom' => [2, 5],
        'selected' => [5, 20],
        'premium' => [20, 50]
    ];
} 