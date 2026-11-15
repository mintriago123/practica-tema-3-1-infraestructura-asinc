<?php

declare(strict_types=1);

namespace App\Practica31\Mensajeria;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
use RuntimeException;

final class ConexionRabbitMQ
{
    public function conectar(): AMQPStreamConnection
    {
        $usuario = (string) config('practica31.rabbitmq.user');
        $contrasena = (string) config('practica31.rabbitmq.password');

        if ($usuario === '' || $contrasena === '') {
            throw new RuntimeException('Faltan variables locales de conexión a RabbitMQ.');
        }

        return new AMQPStreamConnection(
            (string) config('practica31.rabbitmq.host'),
            (int) config('practica31.rabbitmq.port'),
            $usuario,
            $contrasena,
            (string) config('practica31.rabbitmq.vhost'),
        );
    }

    public function declararColas(AMQPChannel $canal): void
    {
        $colaMuertos = (string) config('practica31.rabbitmq.dead_letter_queue');
        $colaTrabajo = (string) config('practica31.rabbitmq.queue');

        $canal->queue_declare($colaMuertos, false, true, false, false);
        $argumentos = new AMQPTable([
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => $colaMuertos,
        ]);
        $canal->queue_declare($colaTrabajo, false, true, false, false, false, $argumentos);
    }
}
