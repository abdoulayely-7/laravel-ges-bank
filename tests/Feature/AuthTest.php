<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client OAuth pour les tests
        Client::create([
            'id' => 1,
            'user_id' => null,
            'name' => 'Test Client',
            'secret' => 'test-secret',
            'redirect' => 'http://localhost',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);
    }

    /**
     * Test de connexion réussie
     */
    public function test_user_can_login_successfully()
    {
        // Créer un utilisateur de test
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/ly/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in',
                        'user'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'token_type' => 'Bearer',
                        'expires_in' => 3600,
                    ]
                ]);

        // Vérifier que les cookies sont définis
        $response->assertCookie('refresh_token');
    }

    /**
     * Test de connexion avec identifiants invalides
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/ly/v1/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Identifiants invalides'
                ]);
    }

    /**
     * Test de validation des données de connexion
     */
    public function test_login_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/ly/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test de rafraîchissement du token
     */
    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token');
        $refreshToken = $token->token;
        $refreshToken->save();

        // Simuler le cookie de refresh token
        $response = $this->withCookies([
            'refresh_token' => $refreshToken->id
        ])->postJson('/ly/v1/auth/refresh');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in'
                    ]
                ]);
    }

    /**
     * Test de déconnexion
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/ly/v1/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Déconnexion réussie'
                ]);
    }

    /**
     * Test d'accès à une route protégée sans authentification
     */
    public function test_protected_route_requires_authentication()
    {
        $response = $this->getJson('/ly/v1/comptes');

        $response->assertStatus(401);
    }

    /**
     * Test d'accès à une route protégée avec authentification
     */
    public function test_protected_route_works_with_authentication()
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token', ['view_own_comptes'])->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/ly/v1/comptes');

        $response->assertStatus(200);
    }
}
