import DocumentCard from '@/components/document-card';
import DocumentFilter, { type Filter, type PilihanFilter, saring } from '@/components/document-filter';
import { NativeSelect } from '@/components/field';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { type Dokumen, type Halaman } from '@/types';
import { SlidersHorizontal } from 'lucide-react';
import { type ReactNode } from 'react';

interface Props {
    action: string;
    dokumen: Halaman<Dokumen>;
    filter: Filter;
    pilihan: PilihanFilter;
    internal?: boolean;
    kosong: ReactNode;
}

/** Katalog dokumen: panel filter di kiri (lembar samping di ponsel), daftar kartu dan halaman di kanan. */
export default function DocumentCatalog({ action, dokumen, filter, pilihan, internal, kosong }: Props) {
    const aktif = Object.entries(filter).filter(([k, v]) => k !== 'urut' && v).length;
    const panel = <DocumentFilter action={action} filter={filter} pilihan={pilihan} internal={internal} />;

    return (
        <div className="grid items-start gap-6 lg:grid-cols-[17.5rem_minmax(0,1fr)] lg:gap-8">
            <aside aria-label="Filter" className={cn('hidden lg:sticky lg:block', internal ? 'lg:top-8' : 'lg:top-24')}>
                {panel}
            </aside>

            <section aria-labelledby="katalog" className="min-w-0">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-4">
                    <div>
                        <h2 id="katalog" className="font-serif text-xl font-semibold tracking-tight">
                            Katalog dokumen
                        </h2>
                        <p aria-live="polite" className="text-muted-foreground mt-0.5 text-sm">
                            {dokumen.total.toLocaleString('id-ID')} dokumen{aktif > 0 && ' sesuai filter'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="outline" className="lg:hidden">
                                    <SlidersHorizontal aria-hidden />
                                    Saring
                                    {aktif > 0 && (
                                        <span className="bg-primary text-primary-foreground grid size-5 place-items-center rounded-full text-xs">
                                            {aktif}
                                        </span>
                                    )}
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="w-[min(22rem,100vw)] overflow-y-auto p-3 pt-12">
                                <SheetTitle className="sr-only">Saring dokumen</SheetTitle>
                                {panel}
                            </SheetContent>
                        </Sheet>
                        <label className="text-muted-foreground flex items-center gap-2 text-sm">
                            <span className="hidden sm:inline">Urutkan</span>
                            <NativeSelect
                                aria-label="Urutkan"
                                value={filter.urut ?? ''}
                                onChange={(e) => saring(action, { ...filter, urut: e.target.value })}
                                className="w-auto"
                            >
                                <option value="">Terbaru</option>
                                <option value="terlama">Terlama</option>
                                <option value="judul">Judul A–Z</option>
                            </NativeSelect>
                        </label>
                    </div>
                </div>

                {dokumen.data.length > 0 ? (
                    <ul className="mt-5 grid gap-4">
                        {dokumen.data.map((d) => (
                            <DocumentCard key={d.id} document={d} internal={internal} />
                        ))}
                    </ul>
                ) : (
                    <div className="py-14 text-center">
                        <p className="font-serif text-lg">{aktif > 0 ? 'Tidak ada dokumen yang cocok.' : kosong}</p>
                        {aktif > 0 && <p className="text-muted-foreground mt-1 text-sm">Coba longgarkan filter, atau atur ulang dari panel kiri.</p>}
                    </div>
                )}

                <Pagination halaman={dokumen} />
            </section>
        </div>
    );
}
