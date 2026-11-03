<?php

namespace App\Services;

class PegawaiSimrs extends DaftarSimrs
{
    /**
     * Nama pegawai per ID SIMRS, mis. untuk mengisi nama DPJP dari objectpegawaifk.
     */
    public function namaById(): array
    {
        return $this->semua()->pluck('nama', 'id')->all();
    }

    protected function cacheKey(): string
    {
        return 'simrs.pegawai';
    }

    protected function url(): ?string
    {
        return config('services.bali_mandara.pegawai_url');
    }

    protected function normalisasi(array $rows): array
    {
        // Hanya ID dan nama yang disimpan; data pribadi pegawai lainnya tidak ikut di-cache.
        return collect($rows)
            ->filter(fn ($row) => isset($row['id']) && filled($row['namalengkap'] ?? null))
            ->map(fn ($row) => ['id' => (string) $row['id'], 'nama' => trim($row['namalengkap'])])
            ->values()
            ->all();
    }
}
