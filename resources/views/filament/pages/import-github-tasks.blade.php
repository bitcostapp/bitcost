<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Open issues from
            <span class="font-medium text-gray-950 dark:text-white">{{ $this->repo() ?? 'an unconfigured repository' }}</span>,
            read via the <code>gh</code> CLI. Check the ones to add and import them as Tasks.
        </p>

        <x-filament::button color="gray" icon="heroicon-m-arrow-path" wire:click="loadIssues" wire:loading.attr="disabled">
            Refresh
        </x-filament::button>
    </div>

    @if ($loadError)
        <x-filament::section>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-5 text-warning-500" />
                <div>
                    <p class="font-medium text-gray-950 dark:text-white">Could not load issues</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $loadError }}</p>
                </div>
            </div>
        </x-filament::section>
    @else
        <div class="grid gap-3 sm:max-w-xs">
            <label for="departmentId" class="text-sm font-medium text-gray-950 dark:text-white">Import into department</label>
            <select
                id="departmentId"
                wire:model="departmentId"
                class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900"
            >
                @foreach ($this->departmentOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <x-filament::section>
            @if (count($issues) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">No open issues found in this repository.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($issues as $issue)
                        <li class="flex items-center gap-3 py-3">
                            <input
                                type="checkbox"
                                id="issue-{{ $issue['number'] }}"
                                wire:model="selected.{{ $issue['number'] }}"
                                class="fi-checkbox-input size-5 rounded border-gray-300 text-primary-600 shadow-sm dark:border-gray-600 dark:bg-gray-900"
                            />
                            <label for="issue-{{ $issue['number'] }}" class="min-w-0 flex-1 cursor-pointer">
                                <span class="block truncate font-medium text-gray-950 dark:text-white">
                                    #{{ $issue['number'] }} &middot; {{ $issue['title'] }}
                                </span>
                            </label>
                            <a href="{{ $issue['url'] }}" target="_blank" rel="noopener" class="shrink-0 text-sm text-primary-600 hover:underline dark:text-primary-400">
                                View
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 flex justify-end">
                    <x-filament::button icon="heroicon-m-plus" wire:click="import" wire:loading.attr="disabled">
                        Import selected
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
