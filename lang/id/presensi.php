<?php

return [

    'errors' => [
        'occurred'             => 'Terjadi kesalahan. Silakan coba lagi.',
        'OFFLINE'              => 'Absensi membutuhkan koneksi internet. Silakan periksa koneksi Anda.',
        'LOCATION_PERMISSION_DENIED' => 'Izin akses lokasi ditolak. Aktifkan GPS untuk melakukan absensi.',
        'LOCATION_UNAVAILABLE' => 'Lokasi tidak dapat ditentukan. Pastikan GPS aktif dan coba lagi.',
        'LOCATION_STALE'       => 'Data lokasi sudah lama. Silakan ambil ulang lokasi terbaru.',
        'LOW_ACCURACY'         => 'Akurasi GPS belum mencukupi. Coba di tempat yang lebih terbuka.',
        'OUTSIDE_AREA'         => 'Anda berada di luar area absensi.',
        'DUPLICATE_CHECK_IN'   => 'Anda sudah melakukan absensi masuk hari ini.',
        'DUPLICATE_CHECK_OUT'  => 'Anda sudah melakukan absensi pulang hari ini.',
        'CHECK_IN_REQUIRED'    => 'Lakukan absensi masuk terlebih dahulu sebelum absensi pulang.',
        'BEFORE_WORK_START'    => 'Absensi pulang belum dapat dilakukan sebelum jam mulai kerja.',
        'LOCATION_NOT_CONFIGURED' => 'Area absensi belum ditentukan admin.',
        'INVALID_LOCATION'     => 'Data lokasi tidak valid.',
        'PHOTO_REQUIRED'       => 'Foto absensi wajib diambil saat absensi masuk.',
        'PHOTO_PERMISSION_DENIED' => 'Izin kamera ditolak. Aktifkan kamera untuk melanjutkan.',
        'PHOTO_CAPTURE_FAILED'  => 'Foto absensi gagal diambil. Coba lagi.',
        'PHOTO_UNSUPPORTED'    => 'Perangkat ini tidak mendukung kamera yang diperlukan untuk absensi.',
    ],

    'success' => [
        'check_in'  => 'Absensi masuk berhasil.',
        'check_out' => 'Absensi pulang berhasil.',
    ],

];
