import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function Flash() {
    const { flash } = usePage<SharedData>().props;

    if (!flash?.sukses && !flash?.galat) return null;

    return (
        <div
            role={flash.galat ? 'alert' : 'status'}
            className={
                flash.galat
                    ? 'text-status-bad border-status-bad/30 mb-6 rounded-md border bg-red-50 px-4 py-3 text-sm font-medium'
                    : 'text-status-ok border-status-ok/30 mb-6 rounded-md border bg-green-50 px-4 py-3 text-sm font-medium'
            }
        >
            {flash.galat ?? flash.sukses}
        </div>
    );
}
