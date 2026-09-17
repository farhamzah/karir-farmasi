<?php

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger;

final class ConfigureRedaction
{
    public function __invoke(IlluminateLogger|Logger $logger): void
    {
        if ($logger instanceof IlluminateLogger) {
            $logger = $logger->getLogger();
        }

        $logger->pushProcessor(new SensitiveContextProcessor);
    }
}
