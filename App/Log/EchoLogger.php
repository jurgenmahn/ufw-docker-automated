<?php
declare(strict_types = 1);

namespace JurgenMahn\UfwDocker\Log {

    class EchoLogger
    {
        public static function Log($message): void {
            echo date('Y-m-d H:i:s') . ' - ' . $message . "\n";
        }

    }
}