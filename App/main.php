<?php

declare(strict_types = 1);

require(__DIR__ . '/Autoload/Autoload.php');

use \JurgenMahn\UfwDocker\Log\Logger;
use \JurgenMahn\UfwDocker\Autoload;
use \JurgenMahn\UfwDocker\Config\Read;
use \JurgenMahn\UfwDocker\Api\Docker;
use \JurgenMahn\UfwDocker\Api\Ufw;

class Main
{
    public static $debug = false;
    public static $settings = null;

    public function __construct()
    {
        // init all needed classes
        Autoload::load();
        self::$settings = Read::getInstance();
        self::$debug = self::$settings->Debug;

        Logger::Log("Starting...\n");

        $this->dockerApi = new Docker();
        $this->ufwApi = new Ufw();

        Logger::Log('Checking requirements');
        Logger::Log('ufw installed and: ' . $this->ufwApi->Test() . "\n");
        Logger::Log('docker installed: ' . $this->dockerApi->Test() . "\n");

        if ($this->ufwApi->Test() == "No" || $this->dockerApi->Test() == "No") {
            throw new \Exception('Requirements not met');
        }

        while (true) {
            $this->UpdateRules();

            if (self::$debug) {
                exit;
            }

            // Give the system his deserved rest
            if (!Main::$debug) {
                sleep(self::$settings->CheckIntervalInSec);
            }            
        }
    }

    private function UpdateRules(): void {


        $rules = Main::$settings->FirewallRules;

        $createdRules = [];
        foreach ($rules as $rule) {
            $createdRules = array_merge($this->ufwApi->ParseRule($rule), $createdRules);
            if (!$rule) {
                Logger::Log('Failed creating rule ' . json_encode($rule));
            }
        }   
        
        $this->ufwApi->cleanupOldRules($createdRules);

    }
}

new Main();