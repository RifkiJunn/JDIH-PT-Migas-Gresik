import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm<{ email: string; password: string; remember: boolean }>({ email: '', password: '', remember: false });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <div className="border-sky flex min-h-svh flex-col border-t-[3px]">
            <Head title="Masuk" />
            <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-10">
                <img src="/images/ptgm-logo.png" alt="PT Gresik Migas" width={48} height={72} className="h-[72px] w-auto self-start" />
                <h1 className="mt-6 font-serif text-2xl font-semibold tracking-tight">Masuk ke ruang internal</h1>
                <p className="text-muted-foreground mt-1 text-sm">Gunakan akun yang diberikan Admin.</p>

                <form className="mt-8 grid gap-5" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autoFocus
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Checkbox id="remember" checked={data.remember} onCheckedChange={(v) => setData('remember', v === true)} />
                        <Label htmlFor="remember">Ingat saya di perangkat ini</Label>
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing && <LoaderCircle aria-hidden className="animate-spin" />}
                        Masuk
                    </Button>
                </form>

                <p className="text-muted-foreground mt-8 text-sm">
                    Lupa password? Hubungi Admin.{' '}
                    <Link href={route('beranda')} className="text-primary underline underline-offset-4">
                        Kembali ke JDIH
                    </Link>
                </p>
            </main>
        </div>
    );
}
