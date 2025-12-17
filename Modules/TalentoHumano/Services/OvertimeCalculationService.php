<?php

namespace Modules\TalentoHumano\Services;

use Carbon\Carbon;
use Modules\TalentoHumano\Entities\ThOvertimeReport;
use Modules\TalentoHumano\Entities\ThHoliday;
use Illuminate\Database\Eloquent\Collection;

class OvertimeCalculationService
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $holidays;

    // --- LÍMITES (SOLO PARA HORAS SUPLEMENTARIAS) ---
    private const WORKDAY_START = '08:00:00';
    private const WORKDAY_END = '16:30:00';
    private const LEGAL_NIGHT_END = '06:00:00';
    private const MAX_MINUTES_PER_DAY = 240; // 4 horas (solo S)
    private const MAX_MINUTES_PER_WEEK = 720; // 12 horas (solo S)

    public function __construct()
    {
        $this->holidays = ThHoliday::all()->keyBy(function ($holiday) {
            return Carbon::parse($holiday->date)->toDateString();
        });
    }

    /**
     * Calcula todos los minutos y los totales en USD para un reporte.
     * (Esta función no cambia)
     */
    public function calculate(ThOvertimeReport $report)
    {
        $report->loadMissing('entries');

        foreach ($report->entries as $entry) {
            list($supplemental, $extraordinary) = $this->calculateRawEntryMinutes($entry);

            $entry->update([
                'supplemental_minutes' => $supplemental,
                'extraordinary_minutes' => $extraordinary,
            ]);
        }

        $report->load('entries');

        // 2. Aplicar límites (Lógica Híbrida)
        list($totalSupplementalNet, $totalExtraordinaryNet) = $this->calculateNetReportMinutes($report->entries);

        $hourValue = $report->hour_value;

        $totalSupplementalUSD = ($hourValue * 1.5) * ($totalSupplementalNet / 60);
        $totalExtraordinaryUSD = ($hourValue * 2.0) * ($totalExtraordinaryNet / 60);
        $subTotalUSD = $totalSupplementalUSD + $totalExtraordinaryUSD;

        $decimoTercero = $subTotalUSD * (1 / 12);
        $fondosReserva = $subTotalUSD * (1 / 12);
        $totalUSD_Pay = $subTotalUSD;

        $report->update([
            'total_supplemental_minutes' => $totalSupplementalNet,
            'total_extraordinary_minutes' => $totalExtraordinaryNet,
            'total_supplemental_usd' => $totalSupplementalUSD,
            'total_extraordinary_usd' => $totalExtraordinaryUSD,
            'decimo_tercero_usd' => $decimoTercero,
            'fondos_reserva_usd' => $fondosReserva,
            'total_usd_pay' => $totalUSD_Pay,
        ]);
    }

    /**
     * Calcula los minutos brutos (sin límites) para una sola entrada.
     */
    private function calculateRawEntryMinutes($entry): array
    {
        $entryDate = Carbon::parse($entry->date);
        $startTime = $entryDate->copy()->setTimeFromTimeString($entry->start_time);
        $endTime = $entryDate->copy()->setTimeFromTimeString($entry->end_time);

        // Si es día de semana y la hora de fin está en la ventana de "llegada" (ej: 07:30 - 08:00),
        // extendemos el cálculo hasta las 08:00 para cubrir esos minutos muertos.

        if (!$this->isWeekend($entryDate) && !$this->isHoliday($entryDate)) {
            $workdayStart = $entryDate->copy()->setTimeFromTimeString(self::WORKDAY_START); // 08:00:00

            // Definimos una ventana de 30 minutos (ajustable)
            // Si termina después de las 7:30 y antes de las 8:00, redondeamos.
            $roundingThreshold = $workdayStart->copy()->subMinutes(30);

            if ($endTime->gt($roundingThreshold) && $endTime->lt($workdayStart)) {
                // Para el cálculo, empujamos el fin hasta las 08:00
                $endTime = $workdayStart;
            }
        }

        $supplemental = 0;
        $extraordinary = 0;

        // REGLA 1: Fines de semana y Feriados (Sin cambios)
        if ($this->isWeekend($entryDate) || $this->isHoliday($entryDate)) {
            $extraordinary = $endTime->diffInMinutes($startTime);
        }
        // REGLA 2: Días Laborales (L-V)
        else {
            // E = 00:00:00 hasta 06:00:00
            $extraStart = $entryDate->copy()->startOfDay();
            $extraEnd = $entryDate->copy()->setTimeFromTimeString(self::LEGAL_NIGHT_END);
            $extraordinary += $this->calculateOverlap($startTime, $endTime, $extraStart, $extraEnd);

            // S (Bloque 1) = 06:00:00 hasta 08:00:00
            // Al haber ajustado $endTime arriba, este bloque capturará los minutos faltantes hasta las 8:00
            $supp1_Start = $extraEnd;
            $supp1_End = $entryDate->copy()->setTimeFromTimeString(self::WORKDAY_START);
            $supplemental += $this->calculateOverlap($startTime, $endTime, $supp1_Start, $supp1_End);

            // S (Bloque 2) = 16:30:00 hasta 23:59:59
            $supp2_Start = $entryDate->copy()->setTimeFromTimeString(self::WORKDAY_END);
            $supp2_End = $entryDate->copy()->endOfDay();
            $supplemental += $this->calculateOverlap($startTime, $endTime, $supp2_Start, $supp2_End);
        }

        return [$supplemental, $extraordinary];
    }

    /**
     * LÓGICA CORREGIDA:
     * 1. Extraordinarias (E) tienen prioridad y NO restan del límite diario en fines de semana.
     * 2. En días laborales, el límite de 4h es la SUMA de (S + E).
     * 3. El límite semanal de 12h aplica al total acumulado de S.
     */
    private function calculateNetReportMinutes(Collection $entries): array
    {
        $totalSupplementalNet = 0;
        $totalExtraordinaryNet = 0;

        // Contadores Semanales
        $weeklyMinutesPaid_S = 0;
        $currentWeekOfYear = null;

        // 1. Agrupar por fecha para evaluar límites diarios
        $dailyTotals = $entries->groupBy('date')->map(function ($dayEntries) {
            return (object)[
                'raw_supp' => $dayEntries->sum('supplemental_minutes'),
                'raw_extra' => $dayEntries->sum('extraordinary_minutes'),
                'date' => $dayEntries->first()->date, // Para chequear si es finde
            ];
        })->sortBy('date');

        foreach ($dailyTotals as $date => $totals) {
            $carbonDate = Carbon::parse($date);

            $weekOfYear = $carbonDate->weekOfYear;
            if ($weekOfYear != $currentWeekOfYear) {
                $weeklyMinutesPaid_S = 0;
                $currentWeekOfYear = $weekOfYear;
            }

            $dayPaid_E = $totals->raw_extra;
            $totalExtraordinaryNet += $dayPaid_E;


            $dayPaid_S = 0;

            if ($totals->raw_supp > 0) {

                $isFreeDay = $this->isWeekend($carbonDate) || $this->isHoliday($carbonDate);

                if ($isFreeDay) {
                    $dayCap_S = $totals->raw_supp;
                } else {
                    $dailyQuotaRemaining = self::MAX_MINUTES_PER_DAY;

                    $dayCap_S = max(0, min($totals->raw_supp, $dailyQuotaRemaining));
                }

                $availableWeekly = self::MAX_MINUTES_PER_WEEK - $weeklyMinutesPaid_S;

                if ($availableWeekly > 0) {
                    $finalDaySupp = min($dayCap_S, $availableWeekly);

                    $totalSupplementalNet += $finalDaySupp;
                    $weeklyMinutesPaid_S += $finalDaySupp;
                }
            }
        }

        return [$totalSupplementalNet, $totalExtraordinaryNet];
    }

    /**
     * Calcula los minutos de superposición (sin cambios)
     */
    private function calculateOverlap(Carbon $eventStart, Carbon $eventEnd, Carbon $rangeStart, Carbon $rangeEnd): int
    {
        $maxStart = $eventStart->max($rangeStart);
        $minEnd = $eventEnd->min($rangeEnd);

        if ($maxStart->lt($minEnd)) {
            return $minEnd->diffInMinutes($maxStart);
        }
        return 0;
    }

    // --- SIN CAMBIOS ---
    private function isWeekend(Carbon $date): bool
    {
        return $date->isSaturday() || $date->isSunday();
    }

    // --- SIN CAMBIOS ---
    private function isHoliday(Carbon $date): bool
    {
        return $this->holidays->has($date->toDateString());
    }

    /**
     * Calcula y actualiza los minutos S/E brutos para todas las entradas de un reporte.
     * Esto es para que la UI pueda mostrarlos ANTES de enviar el reporte.
     * No calcula totales ni aplica límites.
     */
    public function calculateRawEntries(ThOvertimeReport $report)
    {
        // Solo recalculamos si el reporte está en borrador
        if ($report->status !== 'borrador') {
            return;
        }

        $report->loadMissing('entries');

        foreach ($report->entries as $entry) {
            // Llama a la misma función privada que ya tenemos
            list($supplemental, $extraordinary) = $this->calculateRawEntryMinutes($entry);

            // Actualiza la entrada en la base de datos
            // para que la UI pueda leer los nuevos valores.
            $entry->update([
                'supplemental_minutes' => $supplemental,
                'extraordinary_minutes' => $extraordinary,
            ]);
        }
    }
}
