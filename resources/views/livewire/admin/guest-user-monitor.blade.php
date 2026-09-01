<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Guest_User;

new class extends Component {
    use WithPagination;

    public function getUsersProperty()
    {
        return Guest_User::simplePaginate(10);
    }
}; ?>

{{-- <div> --}}
<div x-data="{
    init() {
        Echo.channel('scanner-room')
            .listen('.UserLocationUpdated', (e) => {
                $wire.$refresh(); 
            });
    }
}">
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-zinc-50">
                    <tr class="border-b border-zinc-200">
                        <th class="w-16 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            No
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Item Name
                        </th>
                        <th
                            class="w-44 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Current Location
                        </th>
                        {{-- <th
                            class="w-44 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Actions
                        </th> --}}
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @if ($this->users->isEmpty())
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-zinc-500">
                                No items found.
                            </td>
                        </tr>
                    @else
                        @foreach ($this->users as $user)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-6 py-4">{{ $this->users->firstItem() + $loop->index }}</td>
                                <td class="px-6 py-4 font-medium">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 text-nowrap text-zinc-500">
                                    {{ $user->current_location }}
                                </td>
                                {{-- <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            class="rounded-md border border-indigo-600 px-3 py-1.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-600 hover:text-white">
                                            Edit
                                        </button>
                                        <button
                                            class="rounded-md border border-red-500 px-3 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-500 hover:text-white">
                                            Delete
                                        </button>
                                    </div>
                                </td> --}}
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        {{-- Footer --}}
        <div class="border-t border-zinc-200 px-6 py-4">
            {{ $this->users->links() }}
        </div>

    </div>
</div>
