<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all(); // El scope global ya filtra por usuario actual
        
        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        $client = new Client([
            'user_id' => Auth::id(),
            'name'    => $validated['name'],
            'address' => $validated['address'],
        ]);
        $client->save();

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado correctamente',
            'data' => $client
        ], 201);
    }
    
    public function show(Client $client)
    {
        return response()->json([
            'success' => true,
            'data' => $client
        ]);
    }
    
    public function update(Request $request, Client $client)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);
        
        $client->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado correctamente',
            'data' => $client
        ]);
    }
    
    public function destroy(Client $client)
    {
        $client->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado correctamente'
        ]);
    }
    
    /**
     * Obtiene el cliente del usuario autenticado
     */
    public function getUserClient()
    {
        // Buscar el cliente del usuario autenticado
        $client = Client::where('user_id', Auth::id())->first();
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información de cliente para este usuario'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $client
        ]);
    }
}
