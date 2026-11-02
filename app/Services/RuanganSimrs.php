<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RuanganSimrs
{
    private const CACHE = 'simrs.ruangan';
    private const CACHE_CADANGAN = 'simrs.ruangan.cadangan';

    public function semua(): Collection
    {
        return collect(Cache::remember(self::CACHE, now()->addHour(), function () {
            try {
                $ruangan = $this->ambil();
            } catch (RuntimeException $e) {
                // SIMRS sedang gangguan: pakai daftar terakhir yang berhasil dimuat.
                return Cache::get(self::CACHE_CADANGAN) ?? throw $e;
            }

            Cache::forever(self::CACHE_CADANGAN, $ruangan);

            return $ruangan;
        }));
    }

    public function cari(?string $nama): ?array
    {
        $kunci = $this->kunci($nama);

        return $kunci === '' ? null : $this->semua()->first(fn ($ruangan) => $this->kunci($ruangan['nama']) === $kunci);
    }

    /**
     * Nama ruangan resmi SIMRS untuk disimpan; ketikan bebas yang tidak ada di daftar ditolak.
     */
    public function namaResmi(?string $nama, string $field): ?string
    {
        if (blank($nama)) {
            return null;
        }

        try {
            $ruangan = $this->cari($nama);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                $field => 'Daftar ruangan SIMRS tidak bisa dimuat. Coba lagi beberapa saat.',
            ]);
        }

        if (!$ruangan) {
            throw ValidationException::withMessages([
                $field => 'Ruangan "' . trim($nama) . '" tidak ada di daftar ruangan SIMRS. Pilih ruangan dari daftar.',
            ]);
        }

        return $ruangan['nama'];
    }

    private function kunci(?string $nama): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $nama)));
    }

    private function ambil(): array
    {
        $url = config('services.bali_mandara.ruangan_url');
        $token = config('services.bali_mandara.token');

        if (!$url || !$token) {
            throw new RuntimeException('Konfigurasi API ruangan belum lengkap.');
        }

        $headers = ['token' => $this->nilaiHeader('token', $token)];

        if ($cookie = config('services.bali_mandara.cookie')) {
            $headers['Cookie'] = $this->nilaiHeader('Cookie', $cookie);
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(max(1, (int) config('services.bali_mandara.timeout', 30)))
                ->get($url);
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal koneksi ke service ruangan.', 0, $e);
        }

        $json = $response->json();
        $rows = $json['response'] ?? $json['data'] ?? $json;

        if ($response->failed() || !is_array($rows) || !array_is_list($rows)) {
            throw new RuntimeException('Respons service ruangan tidak valid.');
        }

        $ruangan = collect($rows)
            ->map(function ($row) {
                $nama = is_array($row)
                    ? ($row['label'] ?? $row['namaruangan'] ?? $row['nama_ruangan'] ?? $row['nama'] ?? $row['text'] ?? null)
                    : null;

                return $nama ? [
                    'id' => (string) ($row['id'] ?? $row['value'] ?? $row['idruangan'] ?? $row['id_ruangan'] ?? $nama),
                    'nama' => trim($nama),
                    'departemen_id' => isset($row['objectdepartemenfk']) ? (string) $row['objectdepartemenfk'] : null,
                ] : null;
            })
            ->filter()
            ->values()
            ->all();

        if (empty($ruangan)) {
            throw new RuntimeException('Daftar ruangan SIMRS kosong.');
        }

        return $ruangan;
    }

    private function nilaiHeader(string $nama, string $nilai): string
    {
        return stripos($nilai, $nama . ':') === 0 ? trim(substr($nilai, strlen($nama) + 1)) : $nilai;
    }
}
