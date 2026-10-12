import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { type ComponentProps, type ReactNode } from 'react';

/** Label + kontrol + keterangan + galat. Label selalu tampil; galat berada tepat di bawah kolomnya. */
export function Field({
    id,
    label,
    optional,
    hint,
    error,
    children,
    className,
}: {
    id: string;
    label: string;
    optional?: boolean;
    hint?: ReactNode;
    error?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('grid content-start gap-2', className)}>
            <Label htmlFor={id}>
                {label}
                {optional && <span className="text-muted-foreground font-normal"> (opsional)</span>}
            </Label>
            {children}
            {hint && !error && <p className="text-muted-foreground text-xs">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

const KONTROL =
    'border-input bg-background w-full rounded-md border px-3 text-base disabled:cursor-not-allowed disabled:opacity-50 md:text-sm';

export function NativeSelect({ className, ...props }: ComponentProps<'select'>) {
    return <select className={cn(KONTROL, 'h-11 sm:h-10', className)} {...props} />;
}

export function Textarea({ className, ...props }: ComponentProps<'textarea'>) {
    return <textarea className={cn(KONTROL, 'min-h-28 py-2 leading-relaxed', className)} {...props} />;
}
