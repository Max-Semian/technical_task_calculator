<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/calculator.php';
require_once __DIR__ . '/classes/Mailer.php';

try {
    $mailer = new Mailer();
    
    // Тестовые данные
    $testEmail = 'semianmax@gmail.com'; // Email для тестирования
    $formData = [
        'inn' => '123456789012',
        'phone' => '79123456789',
        'email' => $testEmail
    ];
    $calculationData = [
        'regionName' => 'Ленинградская область',
        'pumping' => 200,
        'fuelName' => 'Бензин',
        'brandName' => 'Роснефть',
        'services' => ['parking', 'wash'],
        'tariff' => 'selected',
        'promoAction' => 20,
        'monthlyCost' => 1000000,
        'monthlyEconomy' => 100000,
        'yearlyEconomy' => 1200000,
        'totalDiscount' => 25
    ];
    
    // Отправка тестового email
    $result = $mailer->sendCalculationResults($testEmail, $formData, $calculationData);
    
    if ($result) {
        echo "✅ Email успешно отправлен на адрес $testEmail\n";
    } else {
        echo "❌ Ошибка при отправке email\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
} 