<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function to send JSON response
function sendJsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Check dependencies
$requiredFiles = [
    __DIR__ . '/vendor/autoload.php' => 'Composer autoload file not found. Run: composer install',
    __DIR__ . '/classes/Mailer.php' => 'Mailer class file not found',
    __DIR__ . '/config/mail.php' => 'Mail configuration file not found',
    __DIR__ . '/config/calculator.php' => 'Calculator configuration file not found'
];

foreach ($requiredFiles as $file => $errorMessage) {
    if (!file_exists($file)) {
        error_log("Missing required file: $file");
        sendJsonResponse(false, $errorMessage, null, 500);
    }
}

try {
    // Load dependencies
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/classes/Mailer.php';
    require_once __DIR__ . '/config/calculator.php';
    require_once __DIR__ . '/config/mail.php';

    // Debug request data
    error_log("=== New Request ===");
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Метод не поддерживается', null, 405);
    }
    
    // Get raw input
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    if (empty($rawInput)) {
        sendJsonResponse(false, 'Пустой запрос', null, 400);
    }
    
    // Decode JSON
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Ошибка обработки JSON: ' . json_last_error_msg(), null, 400);
    }
    
    error_log("Decoded input: " . print_r($input, true));
    
    $action = $input['action'] ?? '';
    if (empty($action)) {
        sendJsonResponse(false, 'Не указано действие', null, 400);
    }
    
    // Calculator class
    class TariffCalculator {
        
        /**
         * Validate input data
         */
        public function validateInput($data) {
            $errors = [];
            
            // Region validation
            if (!isset($data['region']) || !array_key_exists($data['region'], CalculatorConfig::$regions)) {
                $errors[] = 'Неверный регион';
            }
            
            // Pumping validation
            if (!isset($data['pumping']) || !is_numeric($data['pumping']) || $data['pumping'] < 0) {
                $errors[] = 'Неверное значение прокачки';
            }
            
            // Fuel type validation
            if (!isset($data['fuelType']) || !array_key_exists($data['fuelType'], CalculatorConfig::$fuelTypes)) {
                $errors[] = 'Неверный тип топлива';
            }
            
            // Brand validation
            if (!isset($data['brand']) || !array_key_exists($data['brand'], CalculatorConfig::$brands)) {
                $errors[] = 'Неверный бренд';
            }
            
            // Brand-fuel compatibility validation
            if (isset($data['brand']) && isset($data['fuelType'])) {
                $fuel = CalculatorConfig::$fuelTypes[$data['fuelType']];
                if (!in_array($data['brand'], $fuel['brands'])) {
                    $errors[] = 'Бренд не подходит для выбранного типа топлива';
                }
            }
            
            // Services validation
            if (isset($data['services']) && is_array($data['services'])) {
                foreach ($data['services'] as $service) {
                    if (!array_key_exists($service, CalculatorConfig::$services)) {
                        $errors[] = 'Неверная услуга: ' . $service;
                    }
                }
            }
            
            return $errors;
        }
        
        /**
         * Determine tariff based on fuel type and pumping volume
         */
        public function determineTariff($fuelType, $pumping) {
            $fuel = CalculatorConfig::$fuelTypes[$fuelType];
            
            if ($pumping < $fuel['tariffs']['econom']) {
                return 'econom';
            } elseif ($pumping < $fuel['tariffs']['selected']) {
                return 'selected';
            } else {
                return 'premium';
            }
        }
        
        /**
         * Calculate costs and savings
         */
        public function calculate($data) {
            $fuel = CalculatorConfig::$fuelTypes[$data['fuelType']];
            $region = CalculatorConfig::$regions[$data['region']];
            $brand = CalculatorConfig::$brands[$data['brand']];
            $tariff = $this->determineTariff($data['fuelType'], $data['pumping']);
            
            // Base cost calculation
            $baseCost = $fuel['price'] * $data['pumping'];
            
            // Discounts
            $tariffDiscount = CalculatorConfig::$tariffDiscounts[$tariff];
            $promoDiscount = isset($data['promoAction']) ? $data['promoAction'] : max(CalculatorConfig::$promoActions[$tariff]);
            
            // Discount amounts
            $tariffDiscountAmount = ($baseCost * $tariffDiscount) / 100;
            $promoDiscountAmount = ($baseCost * $promoDiscount) / 100;
            
            // Final calculations
            $monthlyCost = $baseCost - $tariffDiscountAmount - $promoDiscountAmount;
            $monthlyEconomy = $tariffDiscountAmount + $promoDiscountAmount;
            $yearlyEconomy = $monthlyEconomy * 12;
            
            return [
                'baseCost' => $baseCost,
                'tariff' => $tariff,
                'tariffDiscount' => $tariffDiscount,
                'promoDiscount' => $promoDiscount,
                'monthlyCost' => $monthlyCost,
                'monthlyEconomy' => $monthlyEconomy,
                'yearlyEconomy' => $yearlyEconomy,
                'totalDiscount' => $tariffDiscount + $promoDiscount,
                'regionName' => $region['name'],
                'fuelName' => $fuel['name'],
                'brandName' => $brand['name'],
                'pumping' => $data['pumping'],
                'services' => $data['services'] ?? [],
                'promoAction' => $promoDiscount
            ];
        }
    }

    // Main processing
    $calculator = new TariffCalculator();
    
    switch ($action) {
        case 'calculate':
            // Validate input
            $errors = $calculator->validateInput($input);
            if (!empty($errors)) {
                sendJsonResponse(false, implode(', ', $errors), null, 400);
            }
            
            // Calculate
            $calculations = $calculator->calculate($input);
            
            sendJsonResponse(true, 'Расчет успешно выполнен', $calculations);
            break;
            
        case 'submit_order':
            // Validate required fields
            $requiredFields = ['inn', 'phone', 'email', 'agreement', 'calculationData'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field])) {
                    sendJsonResponse(false, "Не заполнено поле: $field", null, 400);
                }
            }
            
            // Validate agreement
            if (!$input['agreement']) {
                sendJsonResponse(false, 'Необходимо согласие с условиями', null, 400);
            }
            
            try {
                // Initialize mailer
                $mailer = new Mailer();
                
                // Send email
                $success = $mailer->sendCalculationResults(
                    $input['email'],
                    [
                        'inn' => $input['inn'],
                        'phone' => $input['phone'],
                        'email' => $input['email']
                    ],
                    $input['calculationData']
                );
                
                if ($success) {
                    sendJsonResponse(true, 'Расчет успешно отправлен', null);
                } else {
                    error_log("Failed to send email to: " . $input['email']);
                    sendJsonResponse(false, 'Не удалось отправить расчет', null, 500);
                }
            } catch (Exception $e) {
                error_log("Email sending error: " . $e->getMessage());
                sendJsonResponse(false, 'Ошибка при отправке расчета', null, 500);
            }
            break;
            
        default:
            sendJsonResponse(false, 'Неверное действие', null, 400);
    }
    
} catch (Throwable $e) {
    error_log("Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, 'Внутренняя ошибка сервера: ' . $e->getMessage(), null, 500);
}
?>