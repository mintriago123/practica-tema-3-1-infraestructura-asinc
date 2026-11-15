<?php

declare(strict_types=1);

namespace App\Practica31\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CrearReportePedidosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'solicitado_por' => ['required', 'integer', 'min:1'],
            'periodo_desde' => ['required', 'date_format:Y-m-d'],
            'periodo_hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodo_desde'],
            'correlation_id' => ['sometimes', 'nullable', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'solicitado_por.required' => 'El campo solicitado_por es obligatorio.',
            'periodo_desde.required' => 'El campo periodo_desde es obligatorio.',
            'periodo_hasta.required' => 'El campo periodo_hasta es obligatorio.',
            'periodo_hasta.after_or_equal' => 'periodo_hasta debe ser igual o posterior a periodo_desde.',
            'correlation_id.uuid' => 'correlation_id debe ser un UUID válido.',
        ];
    }
}
