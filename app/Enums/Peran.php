<?php

namespace App\Enums;

enum Peran: string
{
    case Karyawan = 'karyawan';
    case Manajer = 'manajer';
    case Gm = 'gm';
    case Admin = 'admin';
}
