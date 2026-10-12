<?php

return [

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        // Nama model berubah dari waktu ke waktu; cek daftar model tier gratis di ai.google.dev.
        'model' => env('GEMINI_MODEL') ?: 'gemini-3.8-flash',
    ],

    /*
     * Isi otomatis mengirim berkas ke layanan di luar perusahaan. Bawaannya hanya untuk
     * pengajuan ber-akses Publik; nyalakan ini hanya bila kebijakan data penyedia sudah disetujui.
     */
    'izinkan_dokumen_divisi' => (bool) env('AI_IZINKAN_DOKUMEN_DIVISI', false),

];
