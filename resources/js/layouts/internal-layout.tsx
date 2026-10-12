import Brand from '@/components/brand';
import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { type Peran, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Archive, Building2, ClipboardCheck, FilePlus2, type LucideIcon, Menu, ScrollText, Tags, Users } from 'lucide-react';
import { type ReactNode } from 'react';

const MENU: { label: string; href: string; icon: LucideIcon; peran: Peran[] }[] = [
    { label: 'Persetujuan', href: '/persetujuan', icon: ClipboardCheck, peran: ['manajer', 'gm'] },
    { label: 'Arsip', href: '/arsip', icon: Archive, peran: ['karyawan', 'manajer', 'gm'] },
    { label: 'Pengajuan', href: '/pengajuan', icon: FilePlus2, peran: ['karyawan', 'manajer'] },
    { label: 'Pengguna', href: '/admin/pengguna', icon: Users, peran: ['admin'] },
    { label: 'Divisi', href: '/admin/divisi', icon: Building2, peran: ['admin'] },
    { label: 'Jenis Dokumen', href: '/admin/jenis-dokumen', icon: Tags, peran: ['admin'] },
    { label: 'Log', href: '/admin/log', icon: ScrollText, peran: ['admin'] },
];

const NAMA_PERAN: Record<Peran, string> = { karyawan: 'Karyawan', manajer: 'Manajer', gm: 'General Manager', admin: 'Admin' };

function Navigasi() {
    const { auth } = usePage<SharedData>().props;
    const { url } = usePage();
    const user = auth.user!;

    return (
        <div className="flex h-full flex-col">
            <div className="flex h-16 items-center border-b px-5">
                <Brand sub="Ruang internal" />
            </div>
            <nav aria-label="Menu utama" className="flex-1 py-3">
                {MENU.filter((m) => m.peran.includes(user.peran)).map(({ label, href, icon: Icon }) => {
                    const aktif = url.startsWith(href);
                    return (
                        <Link
                            key={href}
                            href={href}
                            aria-current={aktif ? 'page' : undefined}
                            className={cn(
                                'flex min-h-11 items-center gap-3 border-l-2 px-5 text-sm transition-colors duration-150',
                                aktif
                                    ? 'border-sky bg-surface text-primary font-semibold'
                                    : 'text-muted-foreground hover:bg-surface hover:text-foreground border-transparent',
                            )}
                        >
                            <Icon aria-hidden className="size-4" />
                            {label}
                        </Link>
                    );
                })}
            </nav>
            <div className="border-t px-5 py-4 text-sm">
                <div className="truncate font-medium">{user.name}</div>
                <div className="text-muted-foreground truncate text-xs">
                    {NAMA_PERAN[user.peran]}
                    {user.division && ` · ${user.division.nama}`}
                </div>
                <div className="text-muted-foreground mt-3 flex gap-4 text-xs">
                    <Link href={route('password.edit')} className="hover:text-foreground underline underline-offset-4">
                        Ubah password
                    </Link>
                    <Link href={route('logout')} method="post" as="button" className="hover:text-foreground underline underline-offset-4">
                        Keluar
                    </Link>
                </div>
            </div>
        </div>
    );
}

export default function InternalLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-svh lg:grid lg:grid-cols-[15rem_1fr]">
            <aside className="sticky top-0 hidden h-svh border-r lg:block">
                <Navigasi />
            </aside>

            <div className="flex h-14 items-center justify-between border-b px-4 lg:hidden">
                <Brand sub="Ruang internal" />
                <Sheet>
                    <SheetTrigger asChild>
                        <Button variant="outline" size="icon" aria-label="Buka menu">
                            <Menu aria-hidden />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" className="w-64 p-0">
                        <SheetTitle className="sr-only">Menu</SheetTitle>
                        <Navigasi />
                    </SheetContent>
                </Sheet>
            </div>

            <main className="min-w-0 px-4 py-6 sm:px-8 sm:py-8">
                <div className="mx-auto max-w-5xl">
                    <Flash />
                    {children}
                </div>
            </main>
        </div>
    );
}
