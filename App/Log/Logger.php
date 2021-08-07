<?php
declare(strict_types = 1);

namespace JurgenMahn\UfwDocker\Log {

    use \Main;
    use \JurgenMahn\UfwDocker\Log\FileLogger;
    use \JurgenMahn\UfwDocker\Log\EchoLogger;

    class Logger
    {
        public static function Log($message): void {
            if (Main::$debug) {
                EchoLogger::Log($message);
            } else {
                FileLogger::Log($message);
            }
        }

    }
}