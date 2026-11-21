<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\AuthRepository;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Jalankan: php artisan test --filter=GoogleLoginTest
 *
 * Verifikasi ke server Google di-mock, jadi test ini tidak butuh internet
 * dan tidak butuh token Google asli. Logika lain (validasi, buat user,
 * token Sanctum, /me, /logout) tetap berjalan sungguhan.
 */
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function payloadGoogle(array $override = []): array
    {
        return array_merge([
            'sub' => '1234567890',
            'email' => 'budi@gmail.com',
            'email_verified' => true,
            'name' => 'Budi Santoso',
            'picture' => 'https://example.com/avatar.jpg',
            'aud' => 'test-client-id',
        ], $override);
    }

    /** Ganti hasil verifyIdToken(); method lain tetap asli. */
    private function mockGoogle(?array $payload): void
    {
        // Constructor tetap dijalankan, jadi $authRepository terisi
        $mock = Mockery::mock(AuthService::class, [
            $this->app->make(AuthRepository::class),
        ])->makePartial();

        // Hanya verifikasi Google yang dipalsukan, method lain tetap asli
        $mock->shouldReceive('verifyIdToken')->andReturn($payload);

        $this->app->instance(AuthService::class, $mock);
    }

    public function test_login_berhasil_membuat_user_baru_dan_token(): void
    {
        $this->mockGoogle($this->payloadGoogle());

        $response = $this->postJson('/api/login-google', ['credential' => 'token-palsu']);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'name', 'email', 'avatar']])
            ->assertJsonPath('user.email', 'budi@gmail.com');

        $this->assertDatabaseHas('users', [
            'email' => 'budi@gmail.com',
            'google_id' => '1234567890',
        ]);
    }

    public function test_token_hasil_login_bisa_dipakai_di_endpoint_me(): void
    {
        $this->mockGoogle($this->payloadGoogle());

        $token = $this->postJson('/api/login-google', ['credential' => 'token-palsu'])
            ->json('access_token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email', 'budi@gmail.com');
    }

    public function test_login_kedua_kali_tidak_membuat_user_ganda(): void
    {
        $this->mockGoogle($this->payloadGoogle());

        $this->postJson('/api/login-google', ['credential' => 'a'])->assertOk();
        $this->postJson('/api/login-google', ['credential' => 'b'])->assertOk();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_akun_lama_dengan_email_sama_dihubungkan_ke_google(): void
    {
        $lama = User::factory()->create(['email' => 'budi@gmail.com']);
        $this->mockGoogle($this->payloadGoogle());

        $this->postJson('/api/login-google', ['credential' => 'token-palsu'])
            ->assertOk()
            ->assertJsonPath('user.id', $lama->id);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('1234567890', $lama->fresh()->google_id);
    }

    public function test_token_tidak_valid_ditolak_401(): void
    {
        $this->mockGoogle(null);

        $this->postJson('/api/login-google', ['credential' => 'token-rusak'])
            ->assertStatus(401);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_belum_terverifikasi_ditolak_403(): void
    {
        $this->mockGoogle($this->payloadGoogle(['email_verified' => false]));

        $this->postJson('/api/login-google', ['credential' => 'token-palsu'])
            ->assertStatus(403);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_credential_wajib_diisi_422(): void
    {
        $this->postJson('/api/login-google', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('credential');
    }

    public function test_endpoint_me_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_logout_membuat_token_tidak_berlaku(): void
    {
        $this->mockGoogle($this->payloadGoogle());

        $token = $this->postJson('/api/login-google', ['credential' => 'token-palsu'])
            ->json('access_token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->app['auth']->forgetGuards();

        // Token JWT yang sudah logout masuk blacklist, jadi harus ditolak
        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_service_menolak_jika_client_id_belum_diisi(): void
    {
        config(['services.google.client_ids' => []]);

        $this->assertNull(app(AuthService::class)->verifyIdToken('apa-saja'));
    }
}
