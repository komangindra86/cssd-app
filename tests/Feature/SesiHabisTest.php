<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Support\CssdSchema;
use Tests\TestCase;

class SesiHabisTest extends TestCase
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

    public function test_ajax_tanpa_sesi_mendapat_401_untuk_dialihkan_ke_login(): void
    {
        $this->getJson('/operasional/item-data')->assertUnauthorized();
        $this->get('/barang-keluar')->assertRedirect(route('login'));
    }

    public function test_token_kedaluwarsa_di_form_biasa_dialihkan_ke_login(): void
    {
        Route::middleware('web')->post('/uji-token-kedaluwarsa', fn () => throw new TokenMismatchException());

        $this->post('/uji-token-kedaluwarsa')->assertRedirect(route('login', ['sesi' => 'berakhir']));
        $this->postJson('/uji-token-kedaluwarsa')->assertStatus(419);
    }

    public function test_halaman_login_menampilkan_pesan_sesi_berakhir(): void
    {
        $this->get('/login?sesi=berakhir')->assertOk()->assertSee('Sesi Anda telah berakhir. Silakan login kembali.');
        $this->get('/login')->assertOk()->assertDontSee('Sesi Anda telah berakhir');
    }

    public function test_setelah_login_kembali_ke_halaman_yang_tadi_dibuka(): void
    {
        User::factory()->create(['email' => 'cssd@example.test', 'role' => 'user_cssd']);

        $this->get('/login?sesi=berakhir&kembali=' . urlencode('/barang-keluar?tab=ready'))->assertOk();
        $this->post('/login', ['email' => 'cssd@example.test', 'password' => 'password'])
            ->assertRedirect(url('/barang-keluar?tab=ready'));
    }

    public function test_alamat_kembali_ke_situs_lain_diabaikan(): void
    {
        User::factory()->create(['email' => 'cssd@example.test', 'role' => 'user_cssd']);

        foreach (['//evil.example/x', 'https://evil.example', '/\\evil.example', 'barang-keluar', '/logout'] as $kembali) {
            $this->get('/login?kembali=' . urlencode($kembali))->assertOk();
            $this->post('/login', ['email' => 'cssd@example.test', 'password' => 'password'])
                ->assertRedirect(route('dashboard'));
            $this->post('/logout');
        }
    }

    public function test_user_yang_masih_login_dikembalikan_dari_halaman_login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user_cssd']));

        $this->get('/login?sesi=berakhir&kembali=' . urlencode('/barang-keluar'))->assertRedirect(url('/barang-keluar'));
        $this->get('/login?kembali=' . urlencode('//evil.example'))->assertRedirect(route('dashboard'));
    }

    public function test_user_dinonaktifkan_melihat_alasannya_di_login(): void
    {
        $user = User::factory()->create(['role' => 'user_cssd']);
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->getJson('/operasional/item-data')->assertUnauthorized()->assertJsonPath('message', 'User tidak aktif. Hubungi admin.');
        $this->get('/login?sesi=berakhir')->assertOk()->assertSee('User tidak aktif. Hubungi admin.')
            ->assertDontSee('Sesi Anda telah berakhir');
    }

    public function test_timer_sesi_tidak_dipakai_untuk_user_ingat_saya(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user_cssd']));

        $this->get('/')->assertOk()->assertSee('var pakaiTimer = true;', false)
            ->assertSee('var batasSesi = 120 * 60 * 1000 + 5000;', false);
        $this->withCookie(auth()->guard()->getRecallerName(), 'ingat-saya')
            ->get('/')->assertOk()->assertSee('var pakaiTimer = false;', false);
    }
}
