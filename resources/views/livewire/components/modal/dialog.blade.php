<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {

    public bool $show = false;

    public string $message = '';

    public string $title = 'Confirmation';

    public string $action = '';


    #[On('show-dialog')]
    public function showDialog(
        string $message,
        string $title = 'Confirmation',
        string $action = ''
    ) {
        $this->message = $message;
        $this->title = $title;
        $this->action = $action;

        $this->show = true;
    }


    public function cancel()
    {
        $action = $this->action;

        $this->show = false;

        $this->dispatch(
            'dialog-result',
            confirmed: false,
            action: $action
        );

        $this->resetDialog();
    }


    public function confirm()
    {
        $action = $this->action;

        $this->show = false;

        $this->dispatch(
            'dialog-result',
            confirmed: true,
            action: $action
        );

        $this->resetDialog();
    }


    private function resetDialog()
    {
        $this->message = '';
        $this->title = 'Confirmation';
        $this->action = '';
    }

};
?>

<div
    x-data="{ show: @entangle('show') }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
>
    <div
        class="mx-4 w-full max-w-md rounded-xl bg-white shadow-xl"
        @click.stop
    >

        {{-- Header --}}
        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">
                {{ $title }}
            </h2>
        </div>


        {{-- Message --}}
        <div class="px-6 py-6">
            <p class="text-sm text-zinc-600">
                {{ $message }}
            </p>
        </div>


        {{-- Buttons --}}
        <div class="flex justify-end gap-2 border-t px-6 py-4">

            <button
                type="button"
                wire:click="cancel"
                class="rounded-lg border border-zinc-300 px-4 py-2 hover:bg-zinc-100"
            >
                No
            </button>

            <button
                type="button"
                wire:click="confirm"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
            >
                Yes
            </button>

        </div>

    </div>
</div>
