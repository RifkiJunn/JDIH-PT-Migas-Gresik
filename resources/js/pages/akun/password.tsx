import { Field } from '@/components/field';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InternalLayout, { Avatar, NAMA_PERAN } from '@/layouts/internal-layout';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

export default function Akun() {
    const user = usePage<SharedData>().props.auth.user!;
    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('password.update'), { preserveScroll: true, onSuccess: () => reset(), onError: () => reset('password', 'password_confirmation') });
    };

    const kolom = [
        ['current_password', 'Password saat ini', 'current-password'],
        ['password', 'Password baru', 'new-password'],
        ['password_confirmation', 'Ulangi password baru', 'new-password'],
    ] as const;

    const profil: [string, string][] = [
        ['Email', user.email],
        ['Peran', NAMA_PERAN[user.peran]],
        ['Divisi', user.division?.nama ?? 'Semua divisi'],
    ];

    return (
        <InternalLayout>
            <Head title="Akun saya" />
            <PageHeader title="Akun saya" />

            <section aria-labelledby="profil" className="grid gap-x-12 gap-y-4 lg:grid-cols-[14rem_1fr]">
                <div>
                    <h2 id="profil" className="font-semibold">
                        Profil
                    </h2>
                    <p className="text-muted-foreground mt-1 text-sm">Data ini dikelola Admin. Hubungi Admin bila ada yang perlu diubah.</p>
                </div>
                <div className="max-w-md">
                    <div className="flex items-center gap-4">
                        <Avatar nama={user.name} className="size-14 text-lg" />
                        <p className="font-serif text-xl font-semibold">{user.name}</p>
                    </div>
                    <dl className="mt-5 border-t">
                        {profil.map(([label, nilai]) => (
                            <div key={label} className="grid grid-cols-[7rem_1fr] gap-3 border-b py-2.5 text-sm">
                                <dt className="text-muted-foreground">{label}</dt>
                                <dd className="min-w-0 break-words">{nilai}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            <section aria-labelledby="password" className="mt-12 grid gap-x-12 gap-y-4 border-t pt-10 lg:grid-cols-[14rem_1fr]">
                <div>
                    <h2 id="password" className="font-semibold">
                        Ubah password
                    </h2>
                    <p className="text-muted-foreground mt-1 text-sm">Minimal 8 karakter. Jangan pakai password yang sama dengan akun lain.</p>
                </div>
                <form onSubmit={submit} className="grid max-w-md gap-5">
                    {kolom.map(([nama, label, auto]) => (
                        <Field key={nama} id={nama} label={label} error={errors[nama]}>
                            <Input id={nama} type="password" required autoComplete={auto} value={data[nama]} onChange={(e) => setData(nama, e.target.value)} />
                        </Field>
                    ))}
                    <div className="flex items-center gap-4">
                        <Button disabled={processing}>Simpan password</Button>
                        <span role="status" className="text-status-ok text-sm font-medium">
                            {recentlySuccessful && 'Password tersimpan.'}
                        </span>
                    </div>
                </form>
            </section>
        </InternalLayout>
    );
}
