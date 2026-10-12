<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\ApprovalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tab = $request->query('tab') === 'akses' ? 'akses' : 'persetujuan';
        $tanggal = function (string $k) use ($request) {
            $v = $request->query($k);

            return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Date::parse($v) : null;
        };

        $log = ($tab === 'akses' ? AccessLog::query() : ApprovalLog::query())
            ->with('document', 'user')
            ->when($tanggal('dari'), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when($tanggal('sampai'), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()))
            ->latest('id')
            ->paginate(50)->withQueryString()
            ->through(fn ($l) => [
                'id' => $l->id,
                'waktu' => $l->created_at->toIso8601String(),
                'oleh' => $l->user?->name ?? 'Pengunjung',
                'aksi' => $l->aksi,
                'tahap' => $l->tahap ?? null,
                'catatan' => $l->catatan ?? null,
                'ip' => $l->ip ?? null,
                'dokumen' => $l->document?->judul ?? '(dokumen dihapus)',
                'nomor' => $l->document?->nomor,
            ]);

        return Inertia::render('admin/log', [
            'tab' => $tab,
            'log' => $log,
            'filter' => ['dari' => $tanggal('dari')?->toDateString(), 'sampai' => $tanggal('sampai')?->toDateString()],
        ]);
    }
}
