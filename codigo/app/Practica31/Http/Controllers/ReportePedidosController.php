<?php

declare(strict_types=1);

namespace App\Practica31\Http\Controllers;

use App\Practica31\Http\Requests\CrearReportePedidosRequest;
use App\Practica31\Mensajeria\CrearMensajeReportePedidos;
use App\Practica31\Mensajeria\PublicadorReportePedidos;
use App\Practica31\Servicios\ProcesarReportePedidos;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ReportePedidosController
{
    public function __construct(
        private readonly CrearMensajeReportePedidos $crearMensaje,
        private readonly PublicadorReportePedidos $publicador,
        private readonly ProcesarReportePedidos $procesador,
    ) {
    }

    public function encolar(CrearReportePedidosRequest $request): JsonResponse
    {
        $mensaje = $this->crearMensaje->crear($request->validated());

        try {
            $this->publicador->publicar($mensaje);
        } catch (Throwable $exception) {
            Log::error('No se pudo publicar la solicitud de reporte.', [
                'correlation_id' => $mensaje['correlation_id'],
                'message_id' => $mensaje['message_id'],
                'exception' => $exception::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El broker no confirmó la recepción del mensaje.',
                'data' => ['correlation_id' => $mensaje['correlation_id']],
            ], 503);
        }

        Log::info('Solicitud de reporte encolada.', [
            'correlation_id' => $mensaje['correlation_id'],
            'message_id' => $mensaje['message_id'],
            'estado' => 'ENCOLADO',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud recibida; el reporte se procesará en segundo plano.',
            'data' => [
                'reporte_id' => $mensaje['payload']['reporte_id'],
                'message_id' => $mensaje['message_id'],
                'correlation_id' => $mensaje['correlation_id'],
                'estado' => 'ENCOLADO',
            ],
        ], 202)->header('X-Correlation-ID', $mensaje['correlation_id']);
    }

    public function sincronico(CrearReportePedidosRequest $request): JsonResponse
    {
        $mensaje = $this->crearMensaje->crear($request->validated());
        $inicio = hrtime(true);
        $reporte = $this->procesador->procesar($mensaje, 'SINCRONICO');
        $duracionMs = round((hrtime(true) - $inicio) / 1_000_000, 2);

        return response()->json([
            'success' => true,
            'message' => 'Reporte generado antes de responder.',
            'data' => [
                'message_id' => $mensaje['message_id'],
                'correlation_id' => $mensaje['correlation_id'],
                'estado' => 'PROCESADO',
                'duracion_procesamiento_ms' => $duracionMs,
                'reporte' => $reporte,
            ],
        ])->header('X-Correlation-ID', $mensaje['correlation_id']);
    }
}
