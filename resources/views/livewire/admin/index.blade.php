<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    //
};

?>

<div>
    <div class="flex h-16 w-full items-center justify-between">
        <div class="flex flex-col gap-2">
            <h1 class="text-3xl font-bold">Wellcome, {{ auth()->user()->name }}</h1>
            <h1 class="text-l font-normal">
                Wednesday, 22 July 2026
            </h1>
        </div>
        <div class="flex gap-3">
            <button
                class="px-5 py-2 outline-1 outline-solid outline-gray-400 rounded-md text-sm text-[#4F46E5] hover:bg-[#4F46E5] hover:text-white">
                Export Report
            </button>
            <button
                class="px-5 py-2 bg-[#4F46E5] rounded-md text-sm text-white hover:bg-transparent hover:outline-1 hover:outline-solid hover:outline-gray-400 hover:text-[#4F46E5]">
                New Project
            </button>
        </div>
    </div>

    {{-- Card --}}
    <div class="mt-6 grid grid-cols-4 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <div
            class="flex flex-col items-center justify-center gap-2 w-full h-30 outline-2 outline-solid outline-gray-400 rounded-lg">
            <span class="text-xl font-bold">Total Project</span>
            <span class="text-3xl font-bold">20</span>
        </div>
        <div
            class="flex flex-col items-center justify-center gap-2 w-full h-30 outline-2 outline-solid outline-gray-400 rounded-lg">
            <span class="text-xl font-bold">Total Scan</span>
            <span class="text-3xl font-bold">200</span>
        </div>
        <div
            class="flex flex-col items-center justify-center gap-2 w-full h-30 outline-2 outline-solid outline-gray-400 rounded-lg">
            <span class="text-xl font-bold">Total Project</span>
            <span class="text-3xl font-bold">20</span>
        </div>
        <div
            class="flex flex-col items-center justify-center gap-2 w-full h-30 outline-2 outline-solid outline-gray-400 rounded-lg">
            <span class="text-xl font-bold">Total Project</span>
            <span class="text-3xl font-bold">20</span>
        </div>
    </div>
</div>
