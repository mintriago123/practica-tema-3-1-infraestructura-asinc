<?php

declare(strict_types=1);

namespace App\Practica31\Servicios;

use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use UnexpectedValueException;

final class ProcesarReportePedidos
{
    /** @param array<string, mixed> $mensaje
     *  @return array<string, mixed>
     *  @throws JsonException
     */
    public function procesar(array $mensaje, string $canal): array
    {
        $this->validarMensaje($mensaje);

        $demoraMilisegundos = (int) config('practica31.processing_delay_ms', 500);
        if ($demoraMilisegundos > 0) {
            usleep($demoraMilisegundos * 1000);
        }

        $fixture = file_get_contents((string) config('practica31.fixture_path'));
        if ($fixture === false) {
            throw new RuntimeException('No se pudo leer el conjunto ficticio de pedidos.');
        }
        $pedidos = json_decode($fixture, true, 512, JSON_THROW_ON_ERROR);
        $payload = $mensaje['payload'];
        $coincidentes = array_values(array_filter(
            $pedidos,
            static fn (array $pedido): bool =>
                $pedido['fecha'] >= $payload['periodo_desde'] && $pedido['fecha'] <= $payload['periodo_hasta'],
        ));
        $totalCentavos = array_sum(array_column($coincidentes, 'total_centavos'));

        $reporte = [
            'reporte_id' => $payload['reporte_id'],
            'solicitado_por' => $payload['solicitado_por'],
            'periodo' => [
                'desde' => $payload['periodo_desde'],
                'hasta' => $payload['periodo_hasta'],
            ],
            'cantidad_pedidos' => count($coincidentes),
            'total_usd' => sprintf('%d.%02d', intdiv($totalCentavos, 100), $totalCentavos % 100),
            'correlation_id' => $mensaje['correlation_id'],
            'message_id' => $mensaje['message_id'],
            'estado' => 'PROCESADO',
            'canal' => $canal,
            'procesado_en' => now('UTC')->toIso8601String(),
        ];

        $directorio = storage_path('app/practica31/reportes');
        if (! is_dir($directorio) && ! mkdir($directorio, 0775, true) && ! is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear el directorio de reportes.');
        }

        $ruta = $directorio.'/'.$reporte['reporte_id'].'.json';
        $bytes = file_put_contents($ruta, json_encode($reporte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX);
        if ($bytes === false) {
            throw new RuntimeException('No se pudo guardar el reporte generado.');
        }

        Log::info('Reporte de pedidos procesado.', [
            'correlation_id' => $mensaje['correlation_id'],
            'message_id' => $mensaje['message_id'],
            'reporte_id' => $reporte['reporte_id'],
            'estado' => 'PROCESADO',
            'canal' => $canal,
        ]);

        return $reporte;
    }

    /** @param array<string, mixed> $mensaje */
    private function validarMensaje(array $mensaje): void
    {
        if (($mensaje['tipo'] ?? null) !== 'reporte.pedidos.solicitado') {
            throw new UnexpectedValueException('Tipo de mensaje no soportado.');
        }
        foreach (['message_id', 'correlation_id', 'ocurrido_en'] as $campo) {
            if (! is_string($mensaje[$campo] ?? null) || $mensaje[$campo] === '') {
                throw new UnexpectedValueException("Falta el campo {$campo} en el mensaje.");
            }
        }
        $payload = $mensaje['payload'] ?? null;
        if (! is_array($payload) || ! isset($payload['reporte_id'], $payload['solicitado_por'], $payload['periodo_desde'], $payload['periodo_hasta'])) {
            throw new UnexpectedValueException('Payload incompleto para generar el reporte.');
        }
    }
}
