<?php

return [

    'errors' => [
        'occurred'             => 'Terjadi kesalahan. Silakan coba lagi.',
        'OFFLINE'              => 'Presensi membutuhkan koneksi internet. Silakan periksa koneksi Anda.',
        'LOCATION_PERMISSION_DENIED' => 'Izin akses lokasi ditolak. Aktifkan izin lokasi untuk melakukan presensi.',
        'LOCATION_UNAVAILABLE' => 'Lokasi tidak dapat ditentukan. Pastikan GPS aktif dan coba lagi.',
        'LOCATION_STALE'       => 'Data lokasi sudah lama. Silakan ambil ulang lokasi terbaru.',
        'LOW_ACCURACY'         => 'Akurasi GPS belum mencukupi. Coba di tempat yang lebih terbuka.',
        'OUTSIDE_AREA'         => 'Anda berada di luar area presensi.',
        'DUPLICATE_CHECK_IN'   => 'Anda sudah melakukan presensi masuk hari ini.',
        'DUPLICATE_CHECK_OUT'  => 'Anda sudah melakukan presensi pulang hari ini.',
        'CHECK_IN_REQUIRED'    => 'Lakukan presensi masuk terlebih dahulu sebelum presensi pulang.',
        'LOCATION_NOT_CONFIGURED' => 'Area presensi belum ditentukan admin.',
        'INVALID_LOCATION'     => 'Data lokasi tidak valid.',
        'PHOTO_REQUIRED'       => 'Foto presensi wajib diambil saat presensi masuk.',
        'PHOTO_PERMISSION_DENIED' => 'Izin kamera ditolak. Aktifkan kamera untuk melanjutkan.',
        'PHOTO_CAPTURE_FAILED'  => 'Foto presensi gagal diambil. Coba lagi.',
        'PHOTO_UNSUPPORTED'    => 'Perangkat ini tidak mendukung kamera yang diperlukan untuk presensi.',
    ],

    'success' => [
        'check_in'  => 'Presensi masuk berhasil.',
        'check_out' => 'Presensi pulang berhasil.',
    ],

];