import ConfirmDialog from '@/components/confirm-dialog';
import DocumentDetail from '@/components/document-detail';
import { Field, Textarea } from '@/components/field';
import { Button } from '@/components/ui/button';
import InternalLayout from '@/layouts/internal-layout';
import { type Dokumen } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function PersetujuanShow({ dokumen, bolehMemutuskan }: { dokumen: Dokumen; bolehMemutuskan: boolean }) {
    const [dialog, setDialog] = useState<'setujui' | 'tolak' | null>(null);
    const form = useForm({ catatan: '' });

    const tahapAkhir = dokumen.status === 'menunggu_gm';
    const tayangPublik = tahapAkhir && dokumen.akses === 'publik';

    const kirim = (aksi: 'setujui' | 'tolak') =>
        form.post(route(`persetujuan.${aksi}`, dokumen.id), { onSuccess: () => setDialog(null), preserveScroll: true });

    return (
        <InternalLayout>
            <Head title={`Tinjau ${dokumen.nomor}`} />
            <Link href={route('persetujuan.index')} className="text-muted-foreground hover:text-foreground mb-6 inline-flex items-center gap-1.5 text-sm">
                <ArrowLeft aria-hidden className="size-4" />
                Antrean persetujuan
            </Link>

            <DocumentDetail dokumen={dokumen} riwayat>
                {bolehMemutuskan && (
                    <div className="bg-surface mb-8 rounded-md border p-4">
                        <p className="text-sm">
                            Diajukan oleh <span className="font-medium">{dokumen.pengaju}</span> ({dokumen.divisi}).{' '}
                            {tayangPublik
                                ? 'Bila disetujui, dokumen ini langsung tampil di JDIH dan dapat diunduh siapa pun.'
                                : tahapAkhir
                                  ? `Bila disetujui, dokumen ini berlaku dan dapat dilihat oleh ${dokumen.divisi}.`
                                  : 'Bila disetujui, dokumen diteruskan ke General Manager.'}
                        </p>
                        <div className="mt-4 flex flex-wrap gap-3">
                            <Button onClick={() => setDialog('setujui')}>Setujui</Button>
                            <Button variant="outline" onClick={() => setDialog('tolak')}>
                                Tolak
                            </Button>
                        </div>
                    </div>
                )}
            </DocumentDetail>

            <ConfirmDialog
                open={dialog === 'setujui'}
                onOpenChange={(buka) => !buka && setDialog(null)}
                title={tayangPublik ? 'Setujui dan tayangkan ke publik?' : 'Setujui dokumen ini?'}
                description={
                    tayangPublik
                        ? 'Dokumen akan tampil di JDIH tanpa login. Setelah disetujui, isinya tidak bisa diubah; perubahan dilakukan dengan dokumen baru.'
                        : tahapAkhir
                          ? 'Setelah disetujui, isinya tidak bisa diubah; perubahan dilakukan dengan dokumen baru.'
                          : 'Dokumen akan diteruskan ke General Manager untuk persetujuan akhir.'
                }
                confirmLabel={tayangPublik ? 'Setujui dan tayangkan' : 'Setujui'}
                processing={form.processing}
                onConfirm={() => kirim('setujui')}
            />

            <ConfirmDialog
                open={dialog === 'tolak'}
                onOpenChange={(buka) => !buka && setDialog(null)}
                title="Tolak dokumen ini?"
                description="Dokumen dikembalikan ke pengaju untuk diperbaiki dan diajukan ulang."
                confirmLabel="Tolak dokumen"
                destructive
                processing={form.processing}
                onConfirm={() => kirim('tolak')}
            >
                <Field id="catatan" label="Catatan untuk pengaju" error={form.errors.catatan} hint="Jelaskan apa yang perlu diperbaiki.">
                    <Textarea id="catatan" required autoFocus value={form.data.catatan} onChange={(e) => form.setData('catatan', e.target.value)} />
                </Field>
            </ConfirmDialog>
        </InternalLayout>
    );
}
