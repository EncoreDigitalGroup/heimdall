<?php

namespace EncoreDigitalGroup\Heimdall;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PrePoolCreateEvent;
use EncoreDigitalGroup\Heimdall\Policies\MinimumAgePolicy;

final class HeimdallPlugin implements EventSubscriberInterface, PluginInterface
{
    private Composer $composer;
    private IOInterface $io;

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PRE_POOL_CREATE => "onPrePoolCreate",
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public function onPrePoolCreate(PrePoolCreateEvent $event): void
    {
        $config = $this->resolveConfig();
        $trusted = is_array($config["trusted"] ?? null) ? $config["trusted"] : [];

        $heimdallConfig = new HeimdallConfig(
            minimumAge: $config["minimum_age"] ?? null,
            trustedVendors: $this->stringList($trusted["vendors"] ?? []),
            trustedPackages: $this->stringList($trusted["packages"] ?? []),
            lockedPackages: $this->lockedPackages(),
            showLogs: (bool) ($config["show_logs"] ?? false),
        );

        $policy = new MinimumAgePolicy($this->io, $heimdallConfig);

        if (!$policy->isActive()) {
            return;
        }

        $event->setPackages($policy->filter($event->getPackages()));
    }

    /** @return array<string, mixed> */
    private function resolveConfig(): array
    {
        $extra = $this->composer->getPackage()->getExtra();
        $config = $extra["heimdall"] ?? [];

        return is_array($config) ? $config : [];
    }

    /** @return array<string, string> */
    private function lockedPackages(): array
    {
        $locker = $this->composer->getLocker();

        if (!$locker->isLocked()) {
            return [];
        }

        $result = [];
        foreach ($locker->getLockedRepository(true)->getPackages() as $basePackage) {
            $result[strtolower($basePackage->getName())] = $basePackage->getVersion();
        }

        return $result;
    }

    /** @return array<int, string> */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== "") {
                $result[] = $item;
            }
        }

        return $result;
    }
}
