import Brand from '@/components/brand';
import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { type Peran, type SharedData, type User } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Archive,
    Building2,
    ChevronsUpDown,
    ClipboardCheck,
    FilePlus2,
    Globe,
    LayoutDashboard,
    LogOut,
    type LucideIcon,
    Menu,
    ScrollText,
    Tags,
    UserRound,
    Users,
} from 'lucide-react';
import { type ReactNode } from 'react';

const MENU: { label: string; href: string; icon: LucideIcon; peran: Peran[] }[] = [
    { label: 'Ringkasan', href: '/ruang', icon: LayoutDashboard, peran: ['karyawan', 'manajer', 'gm', 'admin'] },
    { label: 'Persetujuan', href: '/persetujuan', icon: ClipboardCheck, peran: ['manajer', 'gm'] },
    { label: 'Arsip', href: '/arsip', icon: Archive, peran: ['karyawan', 'manajer', 'gm'] },
    { label: 'Pengajuan', href: '/pengajuan', icon: FilePlus2, peran: ['karyawan', 'manajer'] },
    { label: 'Pengguna', href: '/admin/pengguna', icon: Users, peran: ['admin'] },
    { label: 'Divisi', href: '/admin/divisi', icon: Building2, peran: ['admin'] },
    { label: 'Jenis Dokumen', href: '/admin/jenis-dokumen', icon: Tags, peran: ['admin'] },
    { label: 'Log', href: '/admin/log', icon: ScrollText, peran: ['admin'] },
];

export const NAMA_PERAN: Record<Peran, string> = { karyawan: 'Karyawan', manajer: 'Manajer', gm: 'General Manager', admin: 'Admin' };

export function inisial(nama: string): string {
    const kata = nama.trim().split(/\s+/);
    return ((kata[0]?.[0] ?? '') + (kata.length > 1 ? kata[kata.length - 1][0] : '')).toUpperCase();
}

export function Avatar({ nama, className }: { nama: string; className?: string }) {
    return (
        <span aria-hidden className={cn('bg-primary text-primary-foreground grid size-9 shrink-0 place-items-center rounded-full text-sm font-semibold', className)}>
            {inisial(nama)}
        </span>
    );
}

function MenuAkun({ user }: { user: User }) {
    const butir = 'min-h-10 cursor-pointer gap-3 px-3';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger className="hover:bg-surface data-[state=open]:bg-surface flex w-full items-center gap-3 rounded-md p-2 text-left transition-colors duration-150">
                <Avatar nama={user.name} />
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-medium">{user.name}</span>
                    <span className="text-muted-foreground block truncate text-xs">
                        {NAMA_PERAN[user.peran]}
                        {user.division && ` · ${user.division.nama}`}
                    </span>
                </span>
                <ChevronsUpDown aria-hidden className="text-muted-foreground size-4 shrink-0" />
                <span className="sr-only">Buka menu akun</span>
            </DropdownMenuTrigger>
            <DropdownMenuContent side="top" align="start" sideOffset={8} className="w-(--radix-dropdown-menu-trigger-width) min-w-56 p-1.5">
                <DropdownMenuLabel className="px-3 py-2 font-normal">
                    <span className="block truncate text-sm font-medium">{user.name}</span>
                    <span className="text-muted-foreground block truncate text-xs">{user.email}</span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild className={butir}>
                    <Link href={route('password.edit')}>
                        <UserRound aria-hidden />
                        Akun saya
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild className={butir}>
                    <Link href={route('beranda')}>
                        <Globe aria-hidden />
                        Portal publik JDIH
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild className={cn(butir, 'text-status-bad focus:text-status-bad')}>
                    <Link href={route('logout')} method="post" as="button" className="w-full">
                        <LogOut aria-hidden />
                        Keluar
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function Navigasi() {
    const { auth } = usePage<SharedData>().props;
    const { url } = usePage();
    const user = auth.user!;

    return (
        <div className="flex h-full flex-col">
            <div className="flex h-16 shrink-0 items-center border-b px-5">
                <Brand sub="Ruang internal" />
            </div>
            <nav aria-label="Menu utama" className="flex-1 space-y-1 overflow-y-auto p-3">
                {MENU.filter((m) => m.peran.includes(user.peran)).map(({ label, href, icon: Icon }) => {
                    const aktif = url.startsWith(href);
                    return (
                        <Link
                            key={href}
                            href={href}
                            aria-current={aktif ? 'page' : undefined}
                            className={cn(
                                'flex min-h-11 items-center gap-3 rounded-md px-3 text-sm transition-colors duration-150',
                                aktif ? 'bg-primary text-primary-foreground font-medium' : 'text-muted-foreground hover:bg-surface hover:text-foreground',
                            )}
                        >
                            <Icon aria-hidden className="size-4" />
                            {label}
                        </Link>
                    );
                })}
            </nav>
            <div className="shrink-0 border-t p-3">
                <MenuAkun user={user} />
            </div>
        </div>
    );
}

export default function InternalLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-svh lg:grid lg:grid-cols-[16rem_1fr]">
            <aside className="sticky top-0 hidden h-svh border-r lg:block">
                <Navigasi />
            </aside>

            <div className="bg-background sticky top-0 z-30 flex h-14 items-center justify-between border-b px-4 lg:hidden">
                <Brand sub="Ruang internal" />
                <Sheet>
                    <SheetTrigger asChild>
                        <Button variant="outline" size="icon" aria-label="Buka menu">
                            <Menu aria-hidden />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" className="w-72 p-0">
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
