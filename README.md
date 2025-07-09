# Калькулятор тарифов

Веб-приложение для расчета тарифов на топливо с учетом различных параметров и отправкой результатов на email.

## Функциональность

- Расчет стоимости топлива с учетом:
  - Региона
  - Объема прокачки
  - Типа топлива
  - Бренда
  - Дополнительных услуг
  - Тарифа
  - Промо-акций
- Отправка результатов расчета на email
- Адаптивный интерфейс
- Валидация данных на клиенте и сервере

## Технологии

- PHP 7.4+
- JavaScript (jQuery)
- HTML5/CSS3
- PHPMailer для отправки email
- Composer для управления зависимостями

## Установка

1. Клонируйте репозиторий:
```bash
git clone https://github.com/Max-Semian/technical_task_calculator.git
cd technical_task_calculator
```

2. Установите зависимости через Composer:
```bash
composer install
```

3. Настройте конфигурацию SMTP в файле `config/mail.php`:
```php
return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'encryption' => 'tls',
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'Tariffs Calculator'
    ]
];
```

4. Убедитесь, что у PHP есть права на запись для создания лог-файлов.

## Использование

1. Откройте приложение в браузере
2. Заполните форму с параметрами расчета
3. Нажмите кнопку "Рассчитать"
4. Просмотрите результаты расчета
5. При необходимости отправьте результаты на email

## Структура проекта

```
/
├── classes/           # PHP классы
│   └── Mailer.php    # Класс для отправки email
├── config/           # Конфигурационные файлы
│   ├── calculator.php # Конфигурация калькулятора
│   └── mail.php      # Конфигурация SMTP
├── vendor/           # Зависимости Composer
├── calculator_backend.php # Серверная логика
└── index.html        # Главная страница
```

## Лицензия

MIT 