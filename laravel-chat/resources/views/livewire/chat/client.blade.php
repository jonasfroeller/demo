<div class="p-8 prose">
    <h1>Laravel Chat Client Demo</h1>

    <div class="relative flex flex-col overflow-auto h-96" id="output"> <!-- TODO: listen to livewire event -->
    </div>

    <form wire:submit.prevent="sendMessage">
        <input type="text" wire:model="message" id="message" placeholder="Message">
        <button class="h-full px-4 py-2 text-gray-200 bg-gray-800" type="submit">
            Send
        </button>
    </form>
</div>