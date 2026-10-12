import { cn } from '@/lib/utils';
import { type ComponentProps } from 'react';

/** Tabel rapat untuk ruang internal. Di layar sempit tabel bergulir di dalam bingkainya, bukan halamannya. */
export function DataTable({ className, ...props }: ComponentProps<'table'>) {
    return (
        <div className="overflow-x-auto border-y">
            <table className={cn('w-full border-collapse text-left text-sm', className)} {...props} />
        </div>
    );
}

export function Th({ className, ...props }: ComponentProps<'th'>) {
    return <th scope="col" className={cn('text-muted-foreground bg-surface px-3 py-2.5 text-xs font-medium whitespace-nowrap', className)} {...props} />;
}

export function Td({ className, ...props }: ComponentProps<'td'>) {
    return <td className={cn('border-t px-3 py-2.5 align-top', className)} {...props} />;
}
