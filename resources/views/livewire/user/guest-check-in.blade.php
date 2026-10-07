<?php
use Livewire\Volt\Component;
use App\Models\Guest_User;
use Illuminate\Support\Str;

new class extends Component {
    public string $guestName = '';
    public $existingUsers = [];
    public bool $showConfirmModal = false;

    public function submitName()
    {
        $this->validate(['guestName' => 'required|min:3|max:50']);
        $users = Guest_User::where('name', $this->guestName)->get();

        if ($users->count() > 0) {
            $this->existingUsers = $users;
            $this->showConfirmModal = true;
        } else {
            $this->registerNewUser();
        }
    }

    public function registerNewUser()
    {
        $uuid = Str::uuid()->toString();
        Guest_User::create([
            'uuid' => $uuid,
            'name' => $this->guestName,
            'current_location' => 'idle',
            'last_seen_at' => now(),
        ]);

        $this->finishCheckIn($uuid);
    }

    public function claimIdentity($uuid)
    {
        Guest_User::where('uuid', $uuid)->update([
            'last_seen_at' => now(),
            'current_location' => 'idle',
        ]);

        $this->finishCheckIn($uuid);
    }

    private function finishCheckIn($uuid)
    {
        session()->put('guest_uuid', $uuid);

        // 1. Suruh browser simpan di LocalStorage
        $this->dispatch('save-uuid', uuid: $uuid);

        // 2. Beritahu komponen Parent (index) bahwa login sukses agar layar berganti
        $this->dispatch('user-checked-in', uuid: $uuid);
    }
}; ?>

<div class="flex min-h-[70vh] items-center justify-center px-4">

    {{-- 1. Form Input Nama --}}
    @if (!$showConfirmModal)
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-8 shadow-sm">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-zinc-800">Selamat Datang</h2>
                <p class="mt-2 text-sm text-zinc-500">Silakan masukkan nama Anda untuk mulai melakukan scan.</p>
            </div>
            <form wire:submit.prevent="submitName" class="mt-6 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-zinc-700">Nama Panggilan</label>
                    <input type="text" id="name" wire:model="guestName" placeholder="Contoh: Budi"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        required autofocus>
                    @error('guestName')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Mulai Scan
                </button>
            </form>
        </div>
    @endif

    {{-- 2. Modal Konfirmasi (Nama Sudah Digunakan) --}}
    @if ($showConfirmModal)
        {{-- Backdrop --}}
        <div class="fixed inset-0 z-40 bg-black/50" wire:click="$set('showConfirmModal', false)"></div>

        {{-- Modal Content --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-8 shadow-xl">
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-xl font-bold text-zinc-800">Nama Sudah Digunakan</h2>
                    <p class="mt-2 text-sm text-zinc-500">
                        Kami menemukan nama <span class="font-bold text-zinc-800">"{{ $guestName }}"</span> di
                        sistem kami. Apakah salah satu dari daftar di bawah ini adalah Anda sebelumnya?
                    </p>
                </div>

                <div class="mt-6 space-y-3">
                    @foreach ($existingUsers as $user)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                            <div>
                                <p class="text-sm font-semibold text-zinc-800">{{ $user->name }}</p>
                                <p class="text-xs text-zinc-500">Terakhir aktif:
                                    {{ $user->last_seen_at?->diffForHumans() ?? 'Belum pernah login' }}
                                </p>
                            </div>
                            <button wire:click="claimIdentity('{{ $user->uuid }}')"
                                class="rounded bg-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-200 cursor-pointer">
                                Ya, ini saya
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 border-t border-zinc-200 pt-6">
                    <p class="text-center text-sm text-zinc-500 mb-3">Bukan salah satu dari di atas?</p>
                    <button wire:click="registerNewUser"
                        class="w-full rounded-lg bg-zinc-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-900 cursor-pointer">
                        Buat Profil Baru "{{ $guestName }}"
                    </button>
                    <button wire:click="$set('showConfirmModal', false)"
                        class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 cursor-pointer">
                        Batal & Ganti Nama
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
