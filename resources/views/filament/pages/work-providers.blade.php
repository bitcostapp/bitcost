<x-filament-panels::page>
    <p class="-mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
        Connect the external systems your Tasks can link to. These are placeholders &mdash; connecting does not yet
        perform a real OAuth handshake.
    </p>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem;">
        @foreach ($this->getProviders() as $provider)
            @php($brand = $provider['value'] === 'github' ? '#181717' : '#0052CC')

            <div @class([
                'fi-section relative overflow-hidden rounded-xl border bg-white shadow-sm transition dark:bg-gray-900',
                'border-gray-200 dark:border-white/10' => ! $provider['connected'],
                'border-success-300 ring-1 ring-success-500/30 dark:border-success-500/40' => $provider['connected'],
            ])>
                {{-- Accent bar in the provider's brand colour --}}
                <div style="background-color: {{ $brand }}; height: 0.25rem; width: 100%;"></div>

                <div class="flex flex-col" style="gap: 1.25rem; padding: 1.5rem; height: calc(100% - 0.25rem);">
                    <div class="flex items-start justify-between" style="gap: 1rem;">
                        <div class="flex items-center" style="gap: 1rem;">
                            <span
                                class="flex shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                style="background-color: {{ $brand }}; width: 2.75rem; height: 2.75rem;"
                            >
                                @if ($provider['value'] === 'github')
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" />
                                    </svg>
                                @else
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M11.571 11.513H0a5.218 5.218 0 0 0 5.232 5.215h2.13v2.057A5.215 5.215 0 0 0 12.575 24V12.518a1.005 1.005 0 0 0-1.005-1.005zm5.723-5.756H5.736a5.215 5.215 0 0 0 5.215 5.214h2.129v2.058a5.218 5.218 0 0 0 5.215 5.214V6.758a1.001 1.001 0 0 0-1-1.001zM23.013 0H11.455a5.215 5.215 0 0 0 5.215 5.215h2.129v2.057A5.215 5.215 0 0 0 24 12.483V1.005A1.001 1.001 0 0 0 23.013 0z" />
                                    </svg>
                                @endif
                            </span>

                            <div>
                                <p class="text-base font-semibold text-gray-950 dark:text-white">
                                    {{ $provider['label'] }}
                                </p>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    Work provider
                                </p>
                            </div>
                        </div>

                        @if ($provider['connected'])
                            <x-filament::badge color="success" icon="heroicon-m-check-circle">
                                Connected
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="gray">
                                Not connected
                            </x-filament::badge>
                        @endif
                    </div>

                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ $provider['description'] }}
                    </p>

                    <div class="mt-auto flex items-center justify-between border-t border-gray-100 dark:border-white/10" style="gap: 0.75rem; padding-top: 1rem;">
                        @if ($provider['connected'])
                            <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <x-filament::icon icon="heroicon-m-clock" style="width: 0.875rem; height: 0.875rem;" />
                                Connected {{ $provider['connectedAt'] }}
                            </span>

                            <div class="flex items-center gap-2">
                                @if ($provider['value'] === 'github')
                                    <x-filament::button
                                        tag="a"
                                        href="{{ \App\Filament\Pages\ImportGithubTasks::getUrl() }}"
                                        color="gray"
                                        size="sm"
                                        icon="heroicon-m-arrow-down-tray"
                                    >
                                        Import issues
                                    </x-filament::button>
                                @endif

                                <x-filament::button
                                    color="danger"
                                    outlined
                                    size="sm"
                                    icon="heroicon-m-x-mark"
                                    wire:click="disconnect('{{ $provider['value'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="disconnect('{{ $provider['value'] }}')"
                                >
                                    Disconnect
                                </x-filament::button>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">Not yet connected</span>

                            <x-filament::button
                                color="primary"
                                size="sm"
                                icon="heroicon-m-link"
                                wire:click="connect('{{ $provider['value'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="connect('{{ $provider['value'] }}')"
                            >
                                Connect
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
