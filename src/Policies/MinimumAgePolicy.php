<?php

namespace EncoreDigitalGroup\Heimdall\Policies;

use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Package\RootPackageInterface;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

final class MinimumAgePolicy
{
    private ?DateTimeImmutable $threshold;
    private ?int $minimumAgeDays;

    public function __construct(
        private readonly IOInterface $io,
        int|string|null $minimumAge,
    ) {
        $this->minimumAgeDays = $this->normalize($minimumAge);
        $this->threshold = $this->buildThreshold($this->minimumAgeDays);
    }

    public function isActive(): bool
    {
        return $this->threshold instanceof DateTimeImmutable;
    }

    /**
     * @param  array<int, BasePackage>  $packages
     * @return array<int, BasePackage>
     */
    public function filter(array $packages): array
    {
        if (!$this->threshold instanceof DateTimeImmutable) {
            return $packages;
        }

        $kept = [];
        foreach ($packages as $package) {
            if ($this->isAllowed($package)) {
                $kept[] = $package;
            }
        }

        return $kept;
    }

    private function isAllowed(BasePackage $package): bool
    {
        if ($package instanceof RootPackageInterface) {
            return true;
        }

        $released = $package->getReleaseDate();

        if (!$released instanceof DateTimeInterface) {
            return true;
        }

        $releasedImmutable = DateTimeImmutable::createFromInterface($released);

        if ($releasedImmutable > $this->threshold) {
            $this->io->writeError(sprintf(
                "<warning>heimdall: rejecting %s %s — released %s, below minimum age %d days</warning>",
                $package->getPrettyName(),
                $package->getPrettyVersion(),
                $releasedImmutable->format("Y-m-d"),
                $this->minimumAgeDays ?? 0,
            ));

            return false;
        }

        return true;
    }

    private function normalize(int|string|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (!ctype_digit(trim($value))) {
            $this->io->writeError(sprintf(
                "<warning>heimdall: invalid minimum-age value \"%s\" — expected integer days, ignoring</warning>",
                $value,
            ));

            return null;
        }

        $days = (int) $value;

        return $days > 0 ? $days : null;
    }

    private function buildThreshold(?int $days): ?DateTimeImmutable
    {
        if ($days === null) {
            return null;
        }

        $now = new DateTimeImmutable;

        try {
            return $now->sub(new DateInterval("P" . $days . "D"));
        } catch (Exception) {
            return null;
        }
    }
}
