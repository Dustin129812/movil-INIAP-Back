<?php

namespace Modules\Investigacion\Services;

use Modules\Investigacion\Entities\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class GroupService
{
    public function getGroups(array $filters): LengthAwarePaginator
    {
        return Group::with(['rubro', 'location', 'responsible', 'creator', 'members', 'parent'])
            ->withCount('members')
            ->when($filters['location_id'] ?? null, function ($query, $locationId) {
                $query->where('location_id', $locationId);
            })
            ->latest()
            ->paginate(30);
    }

    public function createGroup(array $data): Group
    {
        return DB::transaction(function () use ($data) {
            $members = collect($data['members']);
            $responsibleId = $data['responsible_id'];

            if (!$members->contains($responsibleId)) {
                $members->push($responsibleId);
            }

            $group = Group::create([
                'name' => $data['name'],
                'rubro_id' => $data['rubro_id'],
                'location_id' => $data['location_id'],
                'creator_id' => Auth::id(),
                'responsible_id' => $responsibleId,
            ]);

            $group->members()->sync($members->unique()->all());

            return $group;
        });
    }

    public function updateGroup(Group $group, array $data): Group
    {
        $group->update($data);
        return $group;
    }

    public function syncMembers(Group $group, array $memberIds): Group
    {
        $group->members()->sync($memberIds);
        return $group;
    }

    public function changeResponsible(Group $group, int $responsibleId): Group
    {
        $group->update(['responsible_id' => $responsibleId]);
        return $group;
    }

    public function deleteGroup(Group $group): void
    {
        $group->delete();
    }
}
