<?php

declare(strict_types=1);

namespace App\Practica31\Mensajeria;

use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

final class PublicadorReportePedidos
{
    public function __construct(private readonly ConexionRabbitMQ $conexion)
    {
    }

    /** @param array<string, mixed> $mensaje */
    public function publicar(array $mensaje): void
    {
        $conexion = $this->conexion->conectar();
        $canal = null;

        try {
            $canal = $conexion->channel();
            $this->conexion->declararColas($canal);
            $canal->confirm_select();

            $amqp = new AMQPMessage(
                json_encode($mensaje, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $mensaje['message_id'],
                    'correlation_id' => $mensaje['correlation_id'],
                    'type' => $mensaje['tipo'],
                ],
            );

            $canal->basic_publish($amqp, '', (string) config('practica31.rabbitmq.queue'));
            $canal->wait_for_pending_acks_returns(5.0);
        } finally {
            try {
                $canal?->close();
            } catch (Throwable) {
            }
            try {
                $conexion->close();
            } catch (Throwable) {
            }
        }
    }
}
