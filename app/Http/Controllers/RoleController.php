<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RolePermission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);

        foreach ($validated['permissions'] as $menuName) {
            RolePermission::create([
                'role_id' => $role->id,
                'menu_name' => $menuName,
                'can_access' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'role' => $role->load('permissions')
        ]);
    }

    public function show(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return response()->json($role);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);

        $role->permissions()->delete();

        foreach ($validated['permissions'] as $menuName) {
            RolePermission::create([
                'role_id' => $role->id,
                'menu_name' => $menuName,
                'can_access' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'role' => $role->load('permissions')
        ]);
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users'
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
