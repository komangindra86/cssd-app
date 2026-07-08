<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemAlatController;
use App\Http\Controllers\KriteriaRusakController;
use App\Http\Controllers\MasterBmhpController;
use App\Http\Controllers\OperasionalCssdController;
use App\Http\Controllers\ReportReuseController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'prosesLogin'])->name('login.proses');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('dashboard');

    Route::middleware('role:super_admin,user_cssd,user_perawat')->group(function () {
        Route::get('/operasional/dashboard-data', [OperasionalCssdController::class, 'dashboardData']);
        Route::get('/operasional/get-pegawai', [OperasionalCssdController::class, 'getpegawai']);
        Route::get('/operasional/get-ruangan', [OperasionalCssdController::class, 'getruangan']);
        Route::get('/operasional/rawat-inap', [OperasionalCssdController::class, 'getrawatinap']);
        Route::get('/operasional/rawat-jalan', [OperasionalCssdController::class, 'getrawatjalan']);
    });

    Route::middleware('role:super_admin,user_cssd')->group(function () {
        Route::get('/master-bmhp', [MasterBmhpController::class, 'index'])->name('master-bmhp');
        Route::get('/master-bmhp/data', [MasterBmhpController::class, 'data']);
        Route::post('/master-bmhp/tambah', [MasterBmhpController::class, 'tambahbmhp']);
        Route::get('/master-bmhp/get/{id}', [MasterBmhpController::class, 'getbmhp']);
        Route::put('/master-bmhp/edit/{id}', [MasterBmhpController::class, 'editbmhp']);
        Route::delete('/master-bmhp/hapus/{id}', [MasterBmhpController::class, 'hapusbmhp']);

        Route::get('/item-alat', [ItemAlatController::class, 'index'])->name('item-alat');
        Route::get('/item-alat/data', [ItemAlatController::class, 'data']);
        Route::get('/item-alat/get-unit', [ItemAlatController::class, 'getunit']);
        Route::post('/item-alat/tambah', [ItemAlatController::class, 'tambahitemalat']);
        Route::get('/item-alat/get/{id}', [ItemAlatController::class, 'getitemalat']);
        Route::put('/item-alat/edit/{id}', [ItemAlatController::class, 'edititemalat']);
        Route::delete('/item-alat/hapus/{id}', [ItemAlatController::class, 'hapusitemalat']);

        Route::get('/kriteria-rusak', [KriteriaRusakController::class, 'index'])->name('kriteria-rusak');
        Route::get('/kriteria-rusak/data', [KriteriaRusakController::class, 'data']);
        Route::post('/kriteria-rusak/tambah', [KriteriaRusakController::class, 'tambahkriteriarusak']);
        Route::get('/kriteria-rusak/get/{id}', [KriteriaRusakController::class, 'getkriteriarusak']);
        Route::put('/kriteria-rusak/edit/{id}', [KriteriaRusakController::class, 'editkriteriarusak']);
        Route::delete('/kriteria-rusak/hapus/{id}', [KriteriaRusakController::class, 'hapuskriteriarusak']);

        Route::get('/barang-masuk', [OperasionalCssdController::class, 'masuk'])->name('barang-masuk');
        Route::post('/barang-masuk/simpan', [OperasionalCssdController::class, 'masukSimpan']);
        Route::get('/masuk-cssd', [OperasionalCssdController::class, 'masuk'])->name('masuk-cssd');
        Route::post('/masuk-cssd/simpan', [OperasionalCssdController::class, 'masukSimpan']);
        Route::get('/barang-keluar', [OperasionalCssdController::class, 'keluar'])->name('barang-keluar');
        Route::post('/barang-keluar/simpan', [OperasionalCssdController::class, 'keluarSimpan']);
        Route::get('/labeling', [OperasionalCssdController::class, 'labeling'])->name('labeling');
        Route::get('/ready', [OperasionalCssdController::class, 'ready'])->name('ready');
        Route::get('/dispose', [OperasionalCssdController::class, 'dispose'])->name('dispose');

        Route::get('/laporan-reuse', [ReportReuseController::class, 'laporanReuse'])->name('laporan-reuse');
        Route::get('/laporan-reuse/data', [ReportReuseController::class, 'laporanReuseData']);
        Route::get('/laporan-reuse/rekap-jenis', [ReportReuseController::class, 'laporanReuseRekapJenis']);
        Route::get('/laporan-reuse/alat-rusak', [ReportReuseController::class, 'laporanReuseAlatRusak']);
        Route::get('/laporan-reuse/pemakaian-pasien', [ReportReuseController::class, 'laporanReuseData']);
        Route::get('/laporan-reuse/cari-rm', [ReportReuseController::class, 'laporanReuseCariRm']);

        Route::get('/operasional/item-data', [OperasionalCssdController::class, 'itemData']);
        Route::get('/operasional/item/{id}', [OperasionalCssdController::class, 'item']);
        Route::get('/operasional/item-kode/{kode}', [OperasionalCssdController::class, 'itemByKode']);
        Route::get('/operasional/log-data', [OperasionalCssdController::class, 'logData']);
    });

    Route::middleware('role:super_admin,user_perawat')->group(function () {
        Route::get('/input-perawat', [OperasionalCssdController::class, 'perawat'])->name('input-perawat');
        Route::post('/input-perawat/simpan', [OperasionalCssdController::class, 'perawatSimpan']);
        Route::get('/operasional/keluar-data', [OperasionalCssdController::class, 'keluarData']);
    });

    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users');
        Route::get('/users/data', [UserManagementController::class, 'data']);
        Route::post('/users/tambah', [UserManagementController::class, 'tambahuser']);
        Route::get('/users/get/{id}', [UserManagementController::class, 'getuser']);
        Route::put('/users/edit/{id}', [UserManagementController::class, 'edituser']);
        Route::put('/users/status/{id}', [UserManagementController::class, 'ubahstatus']);
        Route::delete('/users/hapus/{id}', [UserManagementController::class, 'hapususer']);
    });
});
