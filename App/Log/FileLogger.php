<?php
declare(strict_types = 1);

namespace JurgenMahn\UfwDocker\Log {

    use \Main;

    class FileLogger
    {
        public static function Log($message): void {
            file_put_contents(Main::$settings->LogPath, date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
        }

    }
}