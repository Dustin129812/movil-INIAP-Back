<?php

namespace Modules\Investigacion\Http\Requests\Reports;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Group;

class GenerateWeeklyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requester = $this->user();
        $targetUserId = (int) $this->input('user_id');

        if ($requester->id === $targetUserId) {
            return true;
        }

        if ($requester->can('approve-any-planning')) {
            return true;
        }

        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            return false;
        }

        if ($requester->hasRole('station-director')) {
            return $targetUser->location_id === $requester->location_id;
        }

        return Group::where('responsible_id', $requester->id)
            ->whereHas('members', function($q) use ($targetUserId) {
                $q->where('users.id', $targetUserId);
            })
            ->where('responsible_id', '!=', $targetUserId)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists(User::class, 'id'),
            ],
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'El usuario solicitado no existe.',
        ];
    }
}
