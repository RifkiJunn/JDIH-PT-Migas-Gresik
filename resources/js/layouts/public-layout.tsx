import Brand from '@/components/brand';
import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <div className="flex min-h-svh flex-col">
            <a href="#isi" className="bg-primary text-primary-foreground sr-only px-4 py-2 focus:not-sr-only focus:absolute focus:z-50">
                Lewati ke isi
            </a>
            <header className="border-sky border-t-[3px] border-b">
                <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                    <Brand />
                    <Button asChild variant={auth.user ? 'default' : 'outline'} size="sm">
                        {auth.user ? <Link href={route('ruang')}>Ruang internal</Link> : <Link href={route('login')}>Masuk</Link>}
                    </Button>
                </div>
            </header>

            <main id="isi" className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
                <Flash />
                {children}
            </main>

            <footer className="bg-primary text-primary-foreground/80 text-sm">
                <div className="mx-auto flex w-full max-w-6xl flex-wrap justify-between gap-x-8 gap-y-2 px-4 py-6 sm:px-6">
                    <p>
                        <span className="text-primary-foreground font-medium">Jaringan Dokumentasi dan Informasi Hukum</span>
                        <br />
                        PT Gresik Migas
                    </p>
                    <p className="self-end">Dokumen di halaman ini telah disetujui untuk dibuka kepada publik.</p>
                </div>
            </footer>
        </div>
    );
}
