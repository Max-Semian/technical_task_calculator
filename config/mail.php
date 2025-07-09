<?php
return [
    'smtp' => [
        // Сервер Gmail SMTP
        'host' => 'smtp.gmail.com',
        
        // Порт Gmail SMTP с TLS
        'port' => 587,
        
        // Ваш Gmail адрес
        'username' => 'semianmax@gmail.com',
        
        // Пароль приложения, полученный в настройках Google (16 символов)
        'password' => 'ufam pwhh qawi zpha',  // Замените на ваш пароль приложения
        
        // Тип шифрования для Gmail
        'encryption' => 'tls',
        
        // Email отправителя (должен совпадать с username)
        'from_email' => 'semianmax@gmail.com',
        
        // Имя отправителя, которое будет отображаться у получателя
        'from_name' => 'Tarrifs Calculator'
    ]
]; 