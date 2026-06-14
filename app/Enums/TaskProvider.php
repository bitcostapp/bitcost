<?php

namespace App\Enums;

/**
 * Where a Task originates: a Work Provider link (GitHub/Jira) or Internal
 * (Bitcost-native, no Work Provider link). Backed by the `external_provider`
 * column; a null/empty `external_provider` means Internal.
 */
enum TaskProvider: string
{
    case Internal = 'internal';
    case Github = 'github';
    case Jira = 'jira';

    /**
     * Resolve the provider from a stored `external_provider` value, treating
     * null/empty/unknown as Internal.
     */
    public static function fromExternalProvider(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Internal;
    }

    /**
     * Get the display label for the provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::Github => 'GitHub',
            self::Jira => 'Jira',
        };
    }

    /**
     * Heroicon name used to badge the provider in the admin UI.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Internal => 'heroicon-m-building-office',
            self::Github => 'heroicon-m-code-bracket',
            self::Jira => 'heroicon-m-bug-ant',
        };
    }

    /**
     * Badge color used in the admin UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::Internal => 'gray',
            self::Github => 'info',
            self::Jira => 'warning',
        };
    }

    /**
     * Whether a Task with this provider carries an external Work Provider link.
     */
    public function requiresExternalUrl(): bool
    {
        return $this !== self::Internal;
    }

    /**
     * Options map for Filament selects/filters: value => label.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
