<?php

namespace Modules\Investigacion\Services\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Modules\Investigacion\Entities\WeeklyPulse;

class TeamPulseReportService
{
    public function generateTeamPulseReport(User $manager)
    {
        Carbon::setLocale('es');
        $manager->load('groups.members');

        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $endDate = Carbon::now()->subWeek()->endOfWeek();

        $teamMemberIds = $manager->groups->flatMap(fn($group) => $group->members->pluck('id'))->unique();
        $teamMembers = User::whereIn('id', $teamMemberIds)->get();

        $pulses = WeeklyPulse::whereIn('user_id', $teamMemberIds)->where('week_start_date', $startDate->toDateString())->get()->keyBy('user_id');

        $teamPulseData = $teamMembers->map(function ($member) use ($pulses) {
            $pulse = $pulses->get($member->id);
            return [
                'name' => $member->name,
                'status' => $pulse->status ?? 'gray',
                'comment' => $pulse->comment ?? null,
            ];
        });

        $counts = $teamPulseData->countBy('status');
        $total = $teamMembers->count() > 0 ? $teamMembers->count() : 1;
        $summary = [
            'total' => $teamMembers->count(),
            'counts' => [
                'green' => $counts->get('green', 0), 'yellow' => $counts->get('yellow', 0), 'red' => $counts->get('red', 0), 'gray' => $counts->get('gray', 0),
            ],
            'percentages' => [
                'green' => round(($counts->get('green', 0) / $total) * 100), 'yellow' => round(($counts->get('yellow', 0) / $total) * 100),
                'red' => round(($counts->get('red', 0) / $total) * 100), 'gray' => round(($counts->get('gray', 0) / $total) * 100),
            ]
        ];

        $data = [
            'iniap_logo_path' => public_path('storage/images/iniap_logo.png'),
            'teamName' => $manager->groups->first()->name ?? 'Equipo',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teamPulseData' => $teamPulseData,
            'summary' => $summary,
        ];

        return Pdf::loadView('reports.team_pulse_report', $data)->download('informe-pulso-semanal-' . $startDate->format('Y-m-d') . '.pdf');
    }
}
