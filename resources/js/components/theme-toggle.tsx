import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Moon, Sun } from 'lucide-react';
import { useState } from 'react';

/** Tombol mode gelap/terang. Pilihan disimpan di peramban; tanpa pilihan, ikut pengaturan sistem (lihat app.blade.php). */
export default function ThemeToggle({ className }: { className?: string }) {
    const [gelap, setGelap] = useState(() => typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));

    const ganti = () => {
        const berikut = !gelap;
        document.documentElement.classList.toggle('dark', berikut);
        try {
            localStorage.setItem('tema', berikut ? 'gelap' : 'terang');
        } catch {
            // Penyimpanan diblokir: tema tetap berganti untuk halaman ini.
        }
        setGelap(berikut);
    };

    return (
        <Button
            variant="ghost"
            size="icon"
            onClick={ganti}
            aria-label={gelap ? 'Pakai mode terang' : 'Pakai mode gelap'}
            title={gelap ? 'Mode terang' : 'Mode gelap'}
            className={cn('text-muted-foreground hover:text-foreground', className)}
        >
            {gelap ? <Sun aria-hidden /> : <Moon aria-hidden />}
        </Button>
    );
}
