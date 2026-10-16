import Brand from '@/components/brand';
import Flash from '@/components/flash';
import ThemeToggle from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, ChevronDown, LogIn, Menu } from 'lucide-react';
import { type ReactNode } from 'react';

const LEBAR = 'mx-auto w-full max-w-6xl px-4 sm:px-6';

/** Tautan menu utama. Yang aktif ditandai garis biru langit di dasar kepala halaman. */
function MenuLink({ href, aktif, children }: { href: string; aktif: boolean; children: ReactNode }) {
    return (
        <Link
            href={href}
            aria-current={aktif ? 'page' : undefined}
            className={cn(
                'inline-flex h-16 items-center px-3 text-sm font-medium transition-colors duration-150',
                aktif ? 'text-primary shadow-[inset_0_-2px_0_var(--sky)]' : 'text-muted-foreground hover:text-foreground',
            )}
        >
            {children}
        </Link>
    );
}

function Kepala() {
    const { auth, jenisPublik } = usePage<SharedData>().props;
    const { url } = usePage();
    const path = url.split('?')[0];
    const diDokumen = path.startsWith('/dokumen');

    const masuk = auth.user ? { href: route('ruang'), label: 'Ruang internal' } : { href: route('login'), label: 'Masuk' };

    return (
        <header className="border-sky bg-background sticky top-0 z-40 border-t-[3px] border-b">
            <div className={cn(LEBAR, 'flex h-16 items-center gap-6')}>
                <Brand />

                <nav aria-label="Menu utama" className="ml-auto hidden items-center md:flex">
                    <MenuLink href={route('beranda')} aktif={path === '/'}>
                        Beranda
                    </MenuLink>

                    <DropdownMenu>
                        <DropdownMenuTrigger
                            className={cn(
                                'inline-flex h-16 items-center gap-1 px-3 text-sm font-medium transition-colors duration-150',
                                diDokumen ? 'text-primary shadow-[inset_0_-2px_0_var(--sky)]' : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            Dokumen hukum
                            <ChevronDown aria-hidden className="size-4" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" sideOffset={0} className="min-w-64 rounded-t-none p-1.5">
                            <DropdownMenuItem asChild className="min-h-10 cursor-pointer px-3 font-medium">
                                <Link href={route('dokumen.index')}>Semua dokumen</Link>
                            </DropdownMenuItem>
                            {jenisPublik.length > 0 && <DropdownMenuSeparator />}
                            {jenisPublik.map((j) => (
                                <DropdownMenuItem key={j.id} asChild className="min-h-10 cursor-pointer justify-between gap-6 px-3">
                                    <Link href={route('dokumen.index', { jenis: j.id })}>
                                        {j.nama}
                                        <span className="text-muted-foreground text-xs">{j.jumlah}</span>
                                    </Link>
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <MenuLink href={route('tentang')} aktif={path === '/tentang'}>
                        Tentang
                    </MenuLink>
                </nav>

                <ThemeToggle className="-mr-3 ml-auto md:mr-0 md:ml-0" />

                <Button asChild variant={auth.user ? 'default' : 'outline'} size="sm" className="hidden md:inline-flex">
                    <Link href={masuk.href}>
                        <LogIn aria-hidden />
                        {masuk.label}
                    </Link>
                </Button>

                <Sheet>
                    <SheetTrigger asChild>
                        <Button variant="outline" size="icon" aria-label="Buka menu" className="md:hidden">
                            <Menu aria-hidden />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="right" className="flex w-72 flex-col gap-0 p-0">
                        <SheetTitle className="border-b px-5 py-4 font-serif text-lg">Menu</SheetTitle>
                        <nav aria-label="Menu utama" className="flex-1 overflow-y-auto py-2">
                            {[
                                { href: route('beranda'), label: 'Beranda' },
                                { href: route('dokumen.index'), label: 'Semua dokumen' },
                                ...jenisPublik.map((j) => ({ href: route('dokumen.index', { jenis: j.id }), label: j.nama, anak: true })),
                                { href: route('tentang'), label: 'Tentang' },
                            ].map((m) => (
                                <Link
                                    key={m.href}
                                    href={m.href}
                                    className={cn(
                                        'hover:bg-surface flex min-h-11 items-center px-5 text-sm',
                                        'anak' in m ? 'text-muted-foreground pl-9' : 'font-medium',
                                    )}
                                >
                                    {m.label}
                                </Link>
                            ))}
                        </nav>
                        <div className="border-t p-4">
                            <Button asChild className="w-full">
                                <Link href={masuk.href}>
                                    <LogIn aria-hidden />
                                    {masuk.label}
                                </Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
    );
}

function Kaki() {
    const { jenisPublik, kontak, auth } = usePage<SharedData>().props;
    const judul = 'text-on-brand text-sm font-semibold';
    const tautan = 'hover:text-on-brand inline-flex min-h-8 items-center underline-offset-4 hover:underline';

    return (
        <footer className="bg-brand-deep text-on-primary-muted text-sm">
            <div className={cn(LEBAR, 'grid gap-x-10 gap-y-10 py-12 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr]')}>
                <div className="max-w-sm">
                    <div className="flex items-center gap-3">
                        <span aria-hidden className="mask-flame bg-on-brand block h-12 w-8" />
                        <p className="text-on-brand leading-tight">
                            <span className="block font-serif text-xl font-semibold">JDIH</span>
                            PT Gresik Migas
                        </p>
                    </div>
                    <p className="mt-4 leading-relaxed">
                        Jaringan Dokumentasi dan Informasi Hukum PT Gresik Migas. Tempat peraturan perusahaan, keputusan direksi, dan dokumen lain
                        yang dibuka untuk umum.
                    </p>
                    {Object.keys(kontak).length > 0 && (
                        <address className="mt-4 leading-relaxed not-italic">
                            {kontak.pengelola && <span className="text-on-brand block font-medium">{kontak.pengelola}</span>}
                            {kontak.alamat && <span className="block">{kontak.alamat}</span>}
                            {kontak.email && (
                                <a href={`mailto:${kontak.email}`} className={cn(tautan, 'block')}>
                                    {kontak.email}
                                </a>
                            )}
                            {kontak.telepon && <span className="block">{kontak.telepon}</span>}
                        </address>
                    )}
                </div>

                <nav aria-label="Telusuri">
                    <h2 className={judul}>Telusuri</h2>
                    <ul className="mt-3">
                        <li>
                            <Link href={route('beranda')} className={tautan}>
                                Beranda
                            </Link>
                        </li>
                        <li>
                            <Link href={route('dokumen.index')} className={tautan}>
                                Semua dokumen
                            </Link>
                        </li>
                        <li>
                            <Link href={route('tentang')} className={tautan}>
                                Tentang JDIH
                            </Link>
                        </li>
                    </ul>
                </nav>

                <nav aria-label="Jenis dokumen">
                    <h2 className={judul}>Jenis dokumen</h2>
                    <ul className="mt-3">
                        {jenisPublik.length === 0 && <li>Belum ada dokumen publik.</li>}
                        {jenisPublik.slice(0, 6).map((j) => (
                            <li key={j.id}>
                                <Link href={route('dokumen.index', { jenis: j.id })} className={tautan}>
                                    {j.nama}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <nav aria-label="Tautan">
                    <h2 className={judul}>Tautan</h2>
                    <ul className="mt-3">
                        <li>
                            <a href="https://jdihn.go.id" target="_blank" rel="noreferrer" className={cn(tautan, 'gap-1')}>
                                JDIH Nasional
                                <ArrowUpRight aria-hidden className="size-3.5" />
                            </a>
                        </li>
                        <li>
                            <Link href={auth.user ? route('ruang') : route('login')} className={tautan}>
                                Ruang internal karyawan
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>

            <div className="border-on-brand/15 border-t">
                <div className={cn(LEBAR, 'flex flex-wrap justify-between gap-x-8 gap-y-2 py-5 text-xs')}>
                    <p className="max-w-2xl">
                        Dokumen di portal ini adalah salinan elektronik untuk keperluan informasi. Bila terdapat perbedaan, yang berlaku adalah naskah
                        asli yang disimpan perusahaan.
                    </p>
                    <p>© {new Date().getFullYear()} PT Gresik Migas</p>
                </div>
            </div>
        </footer>
    );
}

/** Kerangka portal publik. `hero` dirender selebar layar, di antara kepala halaman dan isi. */
export default function PublicLayout({ children, hero }: { children: ReactNode; hero?: ReactNode }) {
    return (
        <div className="flex min-h-svh flex-col">
            <a href="#isi" className="bg-primary text-primary-foreground sr-only px-4 py-2 focus:not-sr-only focus:absolute focus:z-50">
                Lewati ke isi
            </a>
            <Kepala />
            {hero}
            <main id="isi" className={cn(LEBAR, 'flex-1 py-8 sm:py-12')}>
                <Flash />
                {children}
            </main>
            <Kaki />
        </div>
    );
}
