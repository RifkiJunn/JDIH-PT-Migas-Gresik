import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InternalLayout from '@/layouts/internal-layout';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

export default function Password() {
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

    return (
        <InternalLayout>
            <Head title="Ubah password" />
            <PageHeader title="Ubah password" description="Minimal 8 karakter. Jangan pakai password yang sama dengan akun lain." />

            <form onSubmit={submit} className="grid max-w-sm gap-5">
                {kolom.map(([nama, label, auto]) => (
                    <div key={nama} className="grid gap-2">
                        <Label htmlFor={nama}>{label}</Label>
                        <Input
                            id={nama}
                            type="password"
                            required
                            autoComplete={auto}
                            value={data[nama]}
                            onChange={(e) => setData(nama, e.target.value)}
                        />
                        <InputError message={errors[nama]} />
                    </div>
                ))}
                <div className="flex items-center gap-4">
                    <Button disabled={processing}>Simpan password</Button>
                    <span role="status" className="text-status-ok text-sm">
                        {recentlySuccessful && 'Tersimpan.'}
                    </span>
                </div>
            </form>
        </InternalLayout>
    );
}
