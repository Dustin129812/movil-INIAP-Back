<?php

namespace App\Modules\TalentoHumano\HorasExtras\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class CalculoHorasService
{
    // --- CONSTANTES DE CÁLCULO (Ajustar según la ley/política) ---

    // Suplementarias (ej. 1.5x)
    private const FACTOR_SUPLEMENTARIA = 1.5;
    // Extraordinarias (ej. 2.0x)
    private const FACTOR_EXTRAORDINARIA = 2.0;
    // Días promedio del mes para cálculo de valor/hora
    private const DIAS_LABORABLES_MES = 30;
    private const HORAS_LABORABLES_DIA = 8;
    // Porcentaje Fondos de Reserva (ej. 8.33%)
    private const PORCENTAJE_FONDOS_RESERVA = 0.0833;
    // Porcentaje Décimo Tercero (ej. 8.33%)
    private const PORCENTAJE_DECIMO_TERCERO = 0.0833;

    /**
     * Calcula todos los montos para un conjunto de registros de horas y un sueldo.
     *
     * @param float $sueldo El sueldo base del usuario.
     * @param Collection $registros Una colección de modelos RegistroHora.
     * @return array Un array con todos los totales calculados.
     */
    public function calcularMontos(float $sueldo, Collection $registros): array
    {
        if ($sueldo <= 0) {
            // Previene división por cero si el sueldo no está configurado
            return $this->getArrayVacio();
        }

        // 1. Calcular valor base por hora
        $valorHora = ($sueldo / self::DIAS_LABORABLES_MES) / self::HORAS_LABORABLES_DIA;

        // 2. Calcular totales de horas
        $totalHorasSuplementarias = $registros->sum('horas_suplementarias');
        $totalHorasExtraordinarias = $registros->sum('horas_extraordinarias');

        // 3. Calcular montos de horas
        $montoSuplementarias = $totalHorasSuplementarias * $valorHora * self::FACTOR_SUPLEMENTARIA;
        $montoExtraordinarias = $totalHorasExtraordinarias * $valorHora * self::FACTOR_EXTRAORDINARIA;

        $subtotalHoras = $montoSuplementarias + $montoExtraordinarias;

        // 4. Calcular beneficios (basado en el requisito "formula en Excel")
        // Asumimos que se calculan sobre el subtotal de horas extras.
        // *Ajustar esta lógica si la fórmula es diferente (ej. sobre el sueldo)*
        $montoFondosReserva = $subtotalHoras * self::PORCENTAJE_FONDOS_RESERVA;
        $montoDecimoTercero = $subtotalHoras * self::PORCENTAJE_DECIMO_TERCERO;

        // 5. Calcular total final
        $montoTotalPagar = $subtotalHoras + $montoFondosReserva + $montoDecimoTercero;

        return [
            'total_horas_suplementarias' => $totalHorasSuplementarias,
            'total_horas_extraordinarias' => $totalHorasExtraordinarias,
            'monto_suplementarias' => round($montoSuplementarias, 2),
            'monto_extraordinarias' => round($montoExtraordinarias, 2),
            'monto_fondos_reserva' => round($montoFondosReserva, 2),
            'monto_decimo_tercERO' => round($montoDecimoTercero, 2), // Corregido a 'monto_decimo_tercero'
            'monto_total_pagar' => round($montoTotalPagar, 2),
        ];
    }

    /**
     * Devuelve un array de ceros si el cálculo no es posible.
     */
    private function getArrayVacio(): array
    {
        return [
            'total_horas_suplementarias' => 0,
            'total_horas_extraordinarias' => 0,
            'monto_suplementarias' => 0,
            'monto_extraordinarias' => 0,
            'monto_fondos_reserva' => 0,
            'monto_decimo_tercero' => 0, // Nombre corregido
            'monto_total_pagar' => 0,
        ];
    }
}
