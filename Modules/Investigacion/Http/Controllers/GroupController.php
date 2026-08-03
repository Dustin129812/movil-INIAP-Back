<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Investigacion\Entities\Group;
use Modules\Investigacion\Http\Requests\Groups\ChangeResponsibleRequest;
use Modules\Investigacion\Http\Requests\Groups\IndexGroupRequest;
use Modules\Investigacion\Http\Requests\Groups\StoreGroupRequest;
use Modules\Investigacion\Http\Requests\Groups\SyncMembersRequest;
use Modules\Investigacion\Http\Requests\Groups\UpdateGroupRequest;
use Modules\Investigacion\Http\Resources\GroupResource;
use Modules\Investigacion\Services\GroupService;

class GroupController extends Controller
{
    public function __construct(
        private readonly GroupService $groupService
    ) {}

    public function index(IndexGroupRequest $request): AnonymousResourceCollection
    {
        $groups = $this->groupService->getGroups($request->validated());

        return GroupResource::collection($groups);
    }

    public function store(StoreGroupRequest $request): GroupResource
    {
        $group = $this->groupService->createGroup($request->validated());

        return new GroupResource($group->load(['rubro', 'location', 'members', 'creator', 'responsible', 'parent']));
    }

    public function show(Group $group): GroupResource
    {
        return new GroupResource($group->load(['rubro', 'location', 'creator', 'members', 'responsible', 'parent']));
    }

    public function update(UpdateGroupRequest $request, Group $group): GroupResource
    {
        $group = $this->groupService->updateGroup($group, $request->validated());

        return new GroupResource($group->load(['rubro', 'location', 'members', 'parent']));
    }

    public function syncMembers(SyncMembersRequest $request, Group $group): GroupResource
    {
        $group = $this->groupService->syncMembers($group, $request->validated('members'));

        return new GroupResource($group->load('members'));
    }

    public function changeResponsible(ChangeResponsibleRequest $request, Group $group): GroupResource
    {
        $group = $this->groupService->changeResponsible($group, $request->validated('responsible_id'));

        return new GroupResource($group->load(['responsible', 'members']));
    }

    public function destroy(Group $group): Response
    {
        $this->groupService->deleteGroup($group);

        return response()->noContent();
    }
}
