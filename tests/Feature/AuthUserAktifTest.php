<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\CssdSchema;
use Tests\TestCase;

class AuthUserAktifTest extends TestCase
{
    use CssdSchema;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutVite();
        $this->buatSchema();
    }

    public function test_sesi_user_yang_dinonaktifkan_langsung_berakhir(): void
    {
        $user = User::factory()->create(['role' => 'user_cssd', 'is_active' => true]);
        $this->actingAs($user)->getJson('/operasional/item-data')->assertOk();

        $user->update(['is_active' => false]);

        $this->getJson('/operasional/item-data')->assertUnauthorized();
        $this->assertGuest();

        $this->actingAs($user->fresh())->get('/')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_percobaan_login_dibatasi(): void
    {
        User::factory()->create(['email' => 'cssd@example.test']);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'cssd@example.test', 'password' => 'salah'])->assertRedirect();
        }

        $this->post('/login', ['email' => 'cssd@example.test', 'password' => 'salah'])->assertStatus(429);
    }
}
