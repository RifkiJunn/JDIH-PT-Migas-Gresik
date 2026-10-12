<?php

namespace App\Enums;

enum StatusDokumen: string
{
    case Draf = 'draf';
    case MenungguManajer = 'menunggu_manajer';
    case MenungguGm = 'menunggu_gm';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
}
