<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    // GET /api/admin/users
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $search  = trim((string) $request->input('search', ''));

        $q = User::query()->with('roles')->orderByDesc('id');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $q->paginate($perPage)
        ]);
    }

    // GET /api/admin/users/{id}
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $user]);
    }

    // PUT /api/admin/users/{id}
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json(['success' => true, 'data' => $user]);
    }

    // DELETE /api/admin/users/{id}
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Evitar que un admin se elimine a sí mismo
        if (auth()->id() === $user->id) {
            return response()->json(['success' => false, 'message' => 'No puedes eliminar tu propio usuario'], 422);
        }

        // Evitar eliminar al último admin del sistema
        $isAdmin = $user->roles()->where('slug', 'admin')->exists();
        if ($isAdmin) {
            $admins = User::whereHas('roles', fn($q) => $q->where('slug', 'admin'))->count();
            if ($admins <= 1) {
                return response()->json(['success' => false, 'message' => 'No puedes eliminar al último administrador'], 422);
            }
        }

        $user->delete();
        return response()->json(['success' => true]);
    }
}