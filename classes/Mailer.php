<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/calculator.php';

class Mailer {
    private $mailer;
    private $config;
    
    // Названия тарифов для отображения
    private $tariffNames = [
        'econom' => 'Эконом',
        'selected' => 'Избранный',
        'premium' => 'Премиум'
    ];
    
    public function __construct() {
        // Load config
        $configFile = __DIR__ . '/../config/mail.php';
        if (!file_exists($configFile)) {
            throw new Exception('Mail configuration file not found');
        }
        $this->config = require $configFile;
        
        if (!isset($this->config['smtp'])) {
            throw new Exception('Invalid mail configuration');
        }
        
        try {
            $this->mailer = new PHPMailer(true);
            
            // Debug settings
            $this->mailer->SMTPDebug = 2; // Enable verbose debug output
            $this->mailer->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp']['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp']['username'];
            $this->mailer->Password = $this->config['smtp']['password'];
            $this->mailer->SMTPSecure = $this->config['smtp']['encryption'];
            $this->mailer->Port = $this->config['smtp']['port'];
            $this->mailer->CharSet = 'UTF-8';
            
            // Default sender
            $this->mailer->setFrom(
                $this->config['smtp']['from_email'],
                $this->config['smtp']['from_name']
            );
        } catch (Exception $e) {
            error_log("Mailer initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function sendCalculationResults($toEmail, $formData, $calculationData) {
        try {
            // Recipient
            $this->mailer->addAddress($toEmail);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Результаты расчета тарифа - Калькулятор тарифов';
            
            // Build message body
            $body = $this->buildEmailBody($formData, $calculationData);
            $this->mailer->Body = $body;
            
            // Plain text version
            $this->mailer->AltBody = $this->buildPlainTextBody($formData, $calculationData);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function buildEmailBody($formData, $calculationData) {
        $serviceNames = [];
        if (!empty($calculationData['services'])) {
            foreach ($calculationData['services'] as $service) {
                if (isset(CalculatorConfig::$services[$service])) {
                    $serviceNames[] = CalculatorConfig::$services[$service];
                }
            }
        }
        
        // Получаем читаемое название тарифа
        $tariffDisplay = isset($this->tariffNames[$calculationData['tariff']]) 
            ? $this->tariffNames[$calculationData['tariff']] 
            : $calculationData['tariff'];
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: #4ECDC4; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .section { margin-bottom: 20px; }
                .data-row { margin: 10px 0; }
                .label { font-weight: bold; }
                .highlight { background: #FFE66D; padding: 10px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Результаты расчета тарифа</h1>
                </div>
                <div class='content'>
                    <div class='section'>
                        <h2>Параметры расчета:</h2>
                        <div class='data-row'>
                            <span class='label'>Регион:</span> {$calculationData['regionName']}
                        </div>
                        <div class='data-row'>
                            <span class='label'>Прокачка:</span> {$calculationData['pumping']} тонн
                        </div>
                        <div class='data-row'>
                            <span class='label'>Тип топлива:</span> {$calculationData['fuelName']}
                        </div>
                        <div class='data-row'>
                            <span class='label'>Бренд:</span> {$calculationData['brandName']}
                        </div>
                        <div class='data-row'>
                            <span class='label'>Дополнительные услуги:</span> " . 
                            (empty($serviceNames) ? 'Не выбраны' : implode(', ', $serviceNames)) . "
                        </div>
                        <div class='data-row'>
                            <span class='label'>Тариф:</span> {$tariffDisplay}
                        </div>
                        <div class='data-row'>
                            <span class='label'>Промо-акция:</span> {$calculationData['promoAction']}%
                        </div>
                    </div>
                    
                    <div class='section highlight'>
                        <h2>Результаты:</h2>
                        <div class='data-row'>
                            <span class='label'>Стоимость топлива в месяц:</span> " . 
                            number_format($calculationData['monthlyCost'], 0, ',', ' ') . " ₽
                        </div>
                        <div class='data-row'>
                            <span class='label'>Суммарная скидка:</span> {$calculationData['totalDiscount']}%
                        </div>
                        <div class='data-row'>
                            <span class='label'>Экономия в месяц:</span> " . 
                            number_format($calculationData['monthlyEconomy'], 0, ',', ' ') . " ₽
                        </div>
                        <div class='data-row'>
                            <span class='label'>Экономия в год:</span> " . 
                            number_format($calculationData['yearlyEconomy'], 0, ',', ' ') . " ₽
                        </div>
                    </div>
                    
                    <div class='section'>
                        <h2>Контактные данные:</h2>
                        <div class='data-row'>
                            <span class='label'>ИНН:</span> {$formData['inn']}
                        </div>
                        <div class='data-row'>
                            <span class='label'>Телефон:</span> {$formData['phone']}
                        </div>
                        <div class='data-row'>
                            <span class='label'>Email:</span> {$formData['email']}
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function buildPlainTextBody($formData, $calculationData) {
        $serviceNames = [];
        if (!empty($calculationData['services'])) {
            foreach ($calculationData['services'] as $service) {
                if (isset(CalculatorConfig::$services[$service])) {
                    $serviceNames[] = CalculatorConfig::$services[$service];
                }
            }
        }
        
        // Получаем читаемое название тарифа
        $tariffDisplay = isset($this->tariffNames[$calculationData['tariff']]) 
            ? $this->tariffNames[$calculationData['tariff']] 
            : $calculationData['tariff'];
        
        return "
Результаты расчета тарифа

Параметры расчета:
- Регион: {$calculationData['regionName']}
- Прокачка: {$calculationData['pumping']} тонн
- Тип топлива: {$calculationData['fuelName']}
- Бренд: {$calculationData['brandName']}
- Дополнительные услуги: " . (empty($serviceNames) ? 'Не выбраны' : implode(', ', $serviceNames)) . "
- Тариф: {$tariffDisplay}
- Промо-акция: {$calculationData['promoAction']}%

Результаты:
- Стоимость топлива в месяц: " . number_format($calculationData['monthlyCost'], 0, ',', ' ') . " ₽
- Суммарная скидка: {$calculationData['totalDiscount']}%
- Экономия в месяц: " . number_format($calculationData['monthlyEconomy'], 0, ',', ' ') . " ₽
- Экономия в год: " . number_format($calculationData['yearlyEconomy'], 0, ',', ' ') . " ₽

Контактные данные:
- ИНН: {$formData['inn']}
- Телефон: {$formData['phone']}
- Email: {$formData['email']}";
    }
} 