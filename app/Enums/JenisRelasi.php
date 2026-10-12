<?php

namespace App\Enums;

enum JenisRelasi: string
{
    case Mengubah = 'mengubah';
    case Mencabut = 'mencabut';
    case Menggantikan = 'menggantikan';
    case Terkait = 'terkait';
}
