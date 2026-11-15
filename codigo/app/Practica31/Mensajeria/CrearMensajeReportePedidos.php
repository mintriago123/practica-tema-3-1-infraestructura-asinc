<?php

declare(strict_types=1);

namespace App\Practica31\Mensajeria;

use Illuminate\Support\Str;

final class CrearMensajeReportePedidos
{
    /** @param array{solicitado_por:int|string,periodo_desde:string,periodo_hasta:string,correlation_id?:?string} $datos
     *  @return array{message_id:string,correlation_id:string,tipo:string,ocurrido_en:string,payload:array{reporte_id:string,solicitado_por:int,periodo_desde:string,periodo_hasta:string}}
     */
    public function crear(array $datos): array
    {
        $correlationId = ($datos['correlation_id'] ?? null) ?: (string) Str::uuid();

        return [
            'message_id' => (string) Str::uuid(),
            'correlation_id' => $correlationId,
            'tipo' => 'reporte.pedidos.solicitado',
            'ocurrido_en' => now('UTC')->toIso8601String(),
            'payload' => [
                'reporte_id' => (string) Str::uuid(),
                'solicitado_por' => (int) $datos['solicitado_por'],
                'periodo_desde' => $datos['periodo_desde'],
                'periodo_hasta' => $datos['periodo_hasta'],
            ],
        ];
    }
}
