<?php

namespace JurgenMahn\UfwDocker {

    // Just load all PHP files in this project
    class Autoload
    {

        public static function load(): void
        {
            $files = self::getFileList(__DIR__ . '/../', '/^.+\.php$/');

            foreach ($files as $file) {
                // require_once, the lazy way else im loading myself again.
                require_once($file);
            }
        }

        private static function getFileList($root, $pattern): array
        {
            $directory = new \RecursiveDirectoryIterator($root);
            $iterator = new \RecursiveIteratorIterator($directory);
            $files = new \RegexIterator($iterator, $pattern, \RegexIterator::GET_MATCH);
            $fileList = array();

            foreach ($files as $file) {
                $fileList = array_merge($fileList, $file);
            }

            return $fileList;
        }
    }

}