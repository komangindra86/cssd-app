<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use RuntimeException;

class RuanganSimrs extends DaftarSimrs
{
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

    protected function cacheKey(): string
    {
        return 'simrs.ruangan';
    }

    protected function url(): ?string
    {
        return config('services.bali_mandara.ruangan_url');
    }

    protected function normalisasi(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                $nama = $row['label'] ?? $row['namaruangan'] ?? $row['nama_ruangan'] ?? $row['nama'] ?? $row['text'] ?? null;

                return $nama ? [
                    'id' => (string) ($row['id'] ?? $row['value'] ?? $row['idruangan'] ?? $row['id_ruangan'] ?? $nama),
                    'nama' => trim($nama),
                    'departemen_id' => isset($row['objectdepartemenfk']) ? (string) $row['objectdepartemenfk'] : null,
                ] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function kunci(?string $nama): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $nama)));
    }
}
