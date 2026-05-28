<?php

namespace EncoreDigitalGroup\Heimdall;

final readonly class HeimdallConfig
{
    /**
     * @param  array<int, string>  $trustedVendors
     * @param  array<int, string>  $trustedPackages
     * @param  array<string, string>  $lockedPackages  map of lowercased package name to installed version
     */
    public function __construct(
        private int|string|null $minimumAge = null,
        private array $trustedVendors = [],
        private array $trustedPackages = [],
        private array $lockedPackages = [],
        private bool $showLogs = false,
    ) {}

    public function minimumAge(): int|string|null
    {
        return $this->minimumAge;
    }

    /** @return array<int, string> */
    public function trustedVendors(): array
    {
        return $this->trustedVendors;
    }

    /** @return array<int, string> */
    public function trustedPackages(): array
    {
        return $this->trustedPackages;
    }

    /** @return array<string, string> */
    public function lockedPackages(): array
    {
        return $this->lockedPackages;
    }

    public function showLogs(): bool
    {
        return $this->showLogs;
    }
}