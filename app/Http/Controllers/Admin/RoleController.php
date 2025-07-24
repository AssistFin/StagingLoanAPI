<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Submenu;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        \App\Models\Role::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully!');
    }

    public function editPermissions($id)
    {
        $role = Role::findOrFail($id);
        $menus = Menu::with('submenus')->get();
        $roleMenus = $role->menus->pluck('id')->toArray();
        $roleSubmenus = $role->submenus->pluck('id')->toArray();

        return view('admin.roles.edit-permissions', compact('role', 'menus', 'roleMenus', 'roleSubmenus'));
    }

    public function updatePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $role->menus()->sync($request->menus ?? []);
        $role->submenus()->sync($request->submenus ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Permissions updated!');
    }
}