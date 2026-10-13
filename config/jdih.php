<?php

return [

    /*
     * Kontak pengelola yang tampil di kaki portal publik dan halaman Tentang.
     * Baris yang kosong tidak ditampilkan.
     */
    'kontak' => [
        'pengelola' => env('JDIH_PENGELOLA'),
        'alamat' => env('JDIH_ALAMAT'),
        'email' => env('JDIH_EMAIL'),
        'telepon' => env('JDIH_TELEPON'),
    ],

];
