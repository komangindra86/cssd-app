<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Daftar master dari API dashboard SIMRS (ruangan, pegawai) dengan cache dan cadangan saat SIMRS gangguan.
 */
abstract class DaftarSimrs
{
    abstract protected function cacheKey(): string;

    abstract protected function url(): ?string;

    abstract protected function normalisasi(array $rows): array;

    public function semua(): Collection
    {
        return collect(Cache::remember($this->cacheKey(), now()->addHour(), function () {
            try {
                $data = $this->ambil();
            } catch (RuntimeException $e) {
                // SIMRS sedang gangguan: pakai daftar terakhir yang berhasil dimuat.
                return Cache::get($this->cacheKey() . '.cadangan') ?? throw $e;
            }

            Cache::forever($this->cacheKey() . '.cadangan', $data);

            return $data;
        }));
    }

    private function ambil(): array
    {
        $url = $this->url();
        $token = config('services.bali_mandara.token');

        if (!$url || !$token) {
            throw new RuntimeException('Konfigurasi API SIMRS belum lengkap.');
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
            throw new RuntimeException('Gagal koneksi ke service SIMRS.', 0, $e);
        }

        $json = $response->json();
        $rows = $json['response'] ?? $json['data'] ?? $json;

        if ($response->failed() || !is_array($rows) || !array_is_list($rows)) {
            throw new RuntimeException('Respons service SIMRS tidak valid.');
        }

        $data = $this->normalisasi(array_filter($rows, 'is_array'));

        if (empty($data)) {
            throw new RuntimeException('Daftar dari SIMRS kosong.');
        }

        return $data;
    }

    private function nilaiHeader(string $nama, string $nilai): string
    {
        return stripos($nilai, $nama . ':') === 0 ? trim(substr($nilai, strlen($nama) + 1)) : $nilai;
    }
}
