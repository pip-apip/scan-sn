<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Guest_User;

new #[Layout('layouts.app-mobile')] class extends Component {
    public ?string $guestUuid = null;
    public bool $isChecking = true;

    // Dipanggil oleh Alpine saat halaman pertama kali dimuat
    public function verifyUuid($uuid)
    {
        try {
            if ($uuid) {
                $user = Guest_User::where('uuid', $uuid)->first();
                if ($user) {
                    $this->guestUuid = $uuid;
                    session()->put('guest_uuid', $uuid);
                    $user->update([
                        'current_location' => 'idle',
                        'last_seen_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Error in verifyUuid: ' . $e->getMessage());
        } finally {
            $this->isChecking = false;
        }
    }

    #[On('user-checked-in')]
    public function handleUserCheckedIn($uuid)
    {
        $this->guestUuid = $uuid;
    }
}; ?>

<div x-data x-init="$wire.verifyUuid(localStorage.getItem('guest_uuid') || '')" @save-uuid.window="localStorage.setItem('guest_uuid', $event.detail.uuid);"
    class="space-y-4">

    @if ($isChecking)
        {{-- LAYAR LOADING --}}
        <div class="flex min-h-[50vh] flex-col items-center justify-center space-y-4">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600"></div>
            <p class="text-sm font-medium text-zinc-500">Memeriksa akses...</p>
        </div>
    @elseif (!$guestUuid)
        {{-- PANGGIL COMPONENT FORM JIKA UUID KOSONG --}}
        <livewire:user.guest-check-in />
    @else
        {{-- PANGGIL COMPONENT LIST REGION JIKA UUID ADA --}}
        <livewire:user.regions :guestUuid="$guestUuid" />
    @endif

</div>
