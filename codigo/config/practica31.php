<?php

return [
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER'),
        'password' => env('RABBITMQ_PASSWORD'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'queue' => env('RABBITMQ_QUEUE', 'nexo.reportes.pedidos'),
        'dead_letter_queue' => env('RABBITMQ_DEAD_LETTER_QUEUE', 'nexo.reportes.pedidos.dlq'),
    ],
    'processing_delay_ms' => (int) env('PRACTICA31_PROCESSING_DELAY_MS', 500),
    'fixture_path' => base_path('practice-data/pedidos-demo.json'),
];
