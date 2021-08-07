<?php
declare(strict_types = 1);

namespace JurgenMahn\UfwDocker\Config {

    class Read
    {
        private static $instance = null;

        public function __construct() {
            $this->settings = json_decode(file_get_contents(__DIR__ . "/local.json"), false);
        }

        public static function getInstance(): self
        {
          if (self::$instance == null)
          {
            self::$instance = new Read();
          }
       
          return self::$instance;
        }

        public function __get($argument) { // mixed, but only supported in PHP 8
            return $this->settings->{$argument};
        }
    }
}