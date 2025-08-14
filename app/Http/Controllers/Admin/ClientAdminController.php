<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientAdminController extends Controller
{
    // GET /api/admin/clients
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $search  = trim((string) $request->input('search', ''));
        $userId  = $request->input('user_id');

        $q = Client::withoutGlobalScopes()->orderByDesc('id');

        if ($userId) $q->where('user_id', $userId);

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('address', 'like', "%{$search}%");
            });
        }

        return response()->json(['success' => true, 'data' => $q->paginate($perPage)]);
    }

    // GET /api/admin/clients/{id}
    public function show($id)
    {
        $client = Client::withoutGlobalScopes()->findOrFail($id);
        return response()->json(['success' => true, 'data' => $client]);
    }

    // PUT /api/admin/clients/{id}
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        $client = Client::withoutGlobalScopes()->findOrFail($id);
        $client->update($request->only(['name', 'address']));

        return response()->json(['success' => true, 'data' => $client]);
    }

    // DELETE /api/admin/clients/{id}
    public function destroy($id)
    {
        $client = Client::withoutGlobalScopes()->findOrFail($id);
        $client->delete();
        return response()->json(['success' => true]);
    }
}