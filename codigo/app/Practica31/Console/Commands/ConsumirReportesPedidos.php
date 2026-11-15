<?php

declare(strict_types=1);

namespace App\Practica31\Console\Commands;

use App\Practica31\Mensajeria\ConexionRabbitMQ;
use App\Practica31\Servicios\ProcesarReportePedidos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

final class ConsumirReportesPedidos extends Command
{
    protected $signature = 'practica31:consumir-reportes';

    protected $description = 'Consume solicitudes de reporte de pedidos desde RabbitMQ.';

    public function handle(ConexionRabbitMQ $conexion, ProcesarReportePedidos $procesador): int
    {
        $conexionAmqp = $conexion->conectar();
        $canal = $conexionAmqp->channel();
        $conexion->declararColas($canal);
        $canal->basic_qos(null, 1, null);
        $cola = (string) config('practica31.rabbitmq.queue');

        $canal->basic_consume(
            $cola,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $mensaje) use ($procesador): void {
                $envoltura = null;
                $correlationId = null;
                $messageId = null;

                try {
                    $envoltura = json_decode($mensaje->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    if (! is_array($envoltura)) {
                        throw new \UnexpectedValueException('El body del mensaje debe ser un objeto JSON.');
                    }
                    $correlationId = $envoltura['correlation_id'] ?? null;
                    $messageId = $envoltura['message_id'] ?? null;
                    $reporte = $procesador->procesar($envoltura, 'ASINCRONICO');

                    // El ACK se envía solo cuando el resultado ya quedó guardado.
                    $mensaje->ack();
                    $this->line(json_encode([
                        'estado' => 'PROCESADO',
                        'message_id' => $messageId,
                        'correlation_id' => $correlationId,
                        'reporte_id' => $reporte['reporte_id'],
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                } catch (Throwable $exception) {
                    Log::error('Falló el procesamiento; el mensaje se envía a la DLQ.', [
                        'correlation_id' => $correlationId,
                        'message_id' => $messageId,
                        'exception' => $exception::class,
                    ]);
                    $mensaje->nack(false, false);
                    $this->error('Mensaje rechazado y enviado a la cola de errores.');
                }
            },
        );

        $this->info("Consumidor escuchando {$cola}; máximo un mensaje sin ACK.");

        try {
            while ($canal->is_consuming()) {
                $canal->wait();
            }
        } finally {
            $canal->close();
            $conexionAmqp->close();
        }

        return self::SUCCESS;
    }
}
