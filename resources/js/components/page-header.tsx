import { type ReactNode } from 'react';

export default function PageHeader({ title, description, actions }: { title: string; description?: ReactNode; actions?: ReactNode }) {
    return (
        <div className="mb-6 flex flex-wrap items-end justify-between gap-4 border-b pb-5">
            <div className="min-w-0">
                <h1 className="font-serif text-2xl font-semibold tracking-tight text-balance">{title}</h1>
                {description && <p className="text-muted-foreground mt-1 max-w-2xl text-sm">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 gap-2">{actions}</div>}
        </div>
    );
}
