<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumTest extends TestCase
{
    /**
     * A basic feature test example.
     */

     use RefreshDatabase;

    // public function test_user_can_see_auth_routes(): void
    // {

    //     $plainPassword = 'pepe123';

    //     $user = User::factory()->create([
    //         'email' => 'pepe@pepe.es',
    //         'password' => bcrypt($plainPassword),
       
    //         'name' => 'pepe garcia',
    //     ]);

    //     $response = $this->post('/api/login', [
    //         'email' => $user->email,
    //         'password' => $plainPassword,
    //     ]);
      

    //     // $response->assertStatus(200);
    //     // $response->assertJsonStructure([
    //     //     'user' => ['email', 'name'],
    //     //     'token',
    //     // ]);

    //     // Frontend

    //     $token = $response->json('token');

    //     $response = $this
    //     ->withHeader('Authorization', "Bearer {$token}")
    //     ->get('/api/user');
    //     dd($response->json());
    // }



    public function test_user_can_request_with_permissions(): void
    {

        $plainPassword = 'pepe123';

        $user = User::factory()->create([
            'email' => 'pepe@pepe.es',
            'password' => bcrypt($plainPassword),
       
            'name' => 'pepe garcia',
        ]);
        Sanctum::actingAs($user, ['update-post']);
        // si tuviera por ejemplo actualizar (update) ya no podria por que no tiene esos permisos
        $response = $this->getJson('/api/post/create' ,[ 

            'title' => 'mi titulo',
            'content' => 'contenido del post',

        ]);
        $response->assertStatus(200);
    }

}
