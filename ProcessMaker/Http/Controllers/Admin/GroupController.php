<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\HasControllerAddons;

class GroupController extends Controller
{
    use HasControllerAddons;

    /**
     * Get the list of groups.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('admin.groups.index');
    }

    /**
     * Get a specific group
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function edit(Group $group)
    {
        $permissionNames = $group->permissions()->pluck('name')->toArray();
        $all_permissions = Permission::all();
        $permissionGroups = $all_permissions->sortBy('title')->groupBy('group')->sortKeys();

        // Get the 2FA settings
        $settings = [
            '2fa_enabled' => config('password-policies.2fa_enabled', false),
            '2fa_methods' => config('password-policies.2fa_method', []),
        ];

        $addons = $this->getPluginAddons('edit', compact(['group']));

        return view('admin.groups.edit', compact(
            'group',
            'permissionNames',
            'all_permissions',
            'permissionGroups',
            'addons',
            'settings',
        ));
    }

    public function show(Group $group)
    {
        return view('admin.groups.show', compact('group'));
    }
}
