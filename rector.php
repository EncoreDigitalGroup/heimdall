<?php

declare(strict_types=1);

use PHPGenesis\DevUtilities\Rector\Rector;

return Rector::configure()
    ->withPaths([
        __DIR__ . "/src",
    ]);