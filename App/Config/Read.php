<?php
declare(strict_types = 1);

namespace JurgenMahn\UfwDocker\Config {

    class Read
    {
        private static $instance = null;
        private $lastUpdated = 0;

        public function __construct() {

            $localPath = (\Phar::running(false) !== "" ? dirname(\Phar::running(false)) . '/Config' : __DIR__);
            $this->settings = json_decode(file_get_contents($localPath . "/local.json"), false);
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

            if ($this->lastUpdated + 60 < time()) { 
              $this->lastUpdated = time();
              $localPath = (\Phar::running(false) !== "" ? dirname(\Phar::running(false)) . '/Config' : __DIR__);
              $this->settings = json_decode(file_get_contents($localPath . "/local.json"), false);   
            }       
            return $this->settings->{$argument};
        }
    }
}