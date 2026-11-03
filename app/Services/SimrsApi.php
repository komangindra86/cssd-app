<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use RuntimeException;

class SimrsApi
{
    public function aktif(): bool
    {
        return filled(config('services.simrs_api.url'))
            && filled(config('services.simrs_api.token'))
            && filled(config('services.simrs_api.secret'));
    }

    /**
     * Pasien yang berada di ruangan pada tanggal tersebut, termasuk yang kemudian pindah ruangan atau pulang.
     */
    public function riwayatRawatInap(string $ruanganfk, string $tanggal): array
    {
        $json = $this->get('/api/service/getdatariwayatRI', [
            'tanggal' => $tanggal,
            'ruanganfk' => $ruanganfk,
            'limit' => 500,
        ]);

        if (!is_array($json['data'] ?? null)) {
            throw new RuntimeException('Respons riwayat rawat inap SIMRS tidak valid.');
        }

        return $json['data'];
    }

    private function get(string $path, array $query): array
    {
        // Server menolak request identik dengan timestamp yang sama (409); ulangi sekali dengan timestamp baru.
        for ($percobaan = 1; ; $percobaan++) {
            $response = $this->kirim('GET', $path . '?' . http_build_query($query));

            if ($response->status() !== 409 || $percobaan >= 2) {
                break;
            }

            Sleep::for(1)->second();
        }

        if ($response->failed() || !is_array($response->json())) {
            Log::warning('API SIMRS ' . $path . ' mengembalikan error', [
                'status' => $response->status(),
                'response' => mb_substr($response->body(), 0, 2000),
            ]);

            throw new RuntimeException(match ($response->status()) {
                401 => 'Autentikasi API SIMRS ditolak. Periksa token, secret, dan jam server.',
                default => 'API SIMRS mengembalikan error (' . $response->status() . ').',
            });
        }

        return $response->json();
    }

    private function kirim(string $method, string $uri)
    {
        $timestamp = (string) time();

        try {
            return Http::withHeaders([
                'X-Token' => config('services.simrs_api.token'),
                'X-Timestamp' => $timestamp,
                'X-Signature' => $this->signature($method, $uri, $timestamp, hash('sha256', '')),
                'Accept' => 'application/json',
            ])
                ->timeout(max(1, (int) config('services.simrs_api.timeout', 30)))
                ->send($method, rtrim(config('services.simrs_api.url'), '/') . $uri);
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal terhubung ke API SIMRS.', 0, $e);
        }
    }

    /**
     * HMAC-SHA256 atas method, URI lengkap dengan query, timestamp, dan hash body (sesuai ketentuan API SIMRS).
     */
    private function signature(string $method, string $uri, string $timestamp, string $bodyHash): string
    {
        return hash_hmac('sha256', implode("\n", [strtoupper($method), $uri, $timestamp, $bodyHash]),
            trim((string) config('services.simrs_api.secret')));
    }
}
