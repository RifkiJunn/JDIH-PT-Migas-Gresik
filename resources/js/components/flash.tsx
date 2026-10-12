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
                    ? 'border-status-bad text-status-bad mb-6 border-l-2 bg-red-50 px-4 py-3 text-sm'
                    : 'border-status-ok text-status-ok mb-6 border-l-2 bg-green-50 px-4 py-3 text-sm'
            }
        >
            {flash.galat ?? flash.sukses}
        </div>
    );
}
