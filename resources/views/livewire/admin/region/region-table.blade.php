    <?php

    use Livewire\Volt\Component;
    use Livewire\Attributes\Computed;
    use Livewire\WithPagination;
    use Livewire\WithoutUrlPagination;
    use App\Models\Region;
    use Livewire\Attributes\On;

    new class extends Component {
        use WithPagination;
        use WithoutUrlPagination;

        public function mount()
        {
            $this->resetPage();
        }

        public function getRegionsProperty()
        {
            return Region::paginate(10);
        }

        #[On('refreshTable')]
        public function refreshTable()
        {
            $this->resetPage();
        }
    }; ?>

    <div>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-zinc-50">
                        <tr class="border-b border-zinc-200">
                            <th
                                class="w-16 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                No
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Region Name
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Region Category
                            </th>
                            <th
                                class="w-44 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @if ($this->regions->isEmpty())
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-zinc-500">
                                    No regions found.
                                </td>
                            </tr>
                        @else
                            @foreach ($this->regions as $region)
                                <tr class="hover:bg-zinc-50">
                                    <td class="px-6 py-4">{{ $this->regions->firstItem() + $loop->index }}</td>
                                    <td class="px-6 py-4 font-medium">
                                        {{ $region->category_region }}
                                    </td>
                                    <td class="px-6 py-4 font-medium">
                                        {{ $region->name_region }}
                                    </td>
                                    <td class="px-6 py-4">
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
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            {{-- Footer --}}
            <div class="border-t border-zinc-200 px-6 py-4">
                {{ $this->regions->links() }}
                {{-- {{ $this->regions->links('vendor.pagination.tailwind') }} --}}
            </div>

        </div>
    </div>
