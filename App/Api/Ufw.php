<?php
declare(strict_types = 1);

namespace JurgenMahn\UfwDocker\Api {

    use JurgenMahn\UfwDocker\Config;
    use JurgenMahn\UfwDocker\Log\Logger;
    use JurgenMahn\UfwDocker\Api\Docker;
    use \Main;
    use stdClass;

    class Ufw
    {
        public function __construct() 
        {
            $this->dockerApi = new Docker();
        }

        public function getRules(): array {
            $rules = $this->Execute('status numbered');
            return $rules;
        }

        // {
        //     "ContainerName": "xxxxx",
        //     "AllowFrom": "217.122.72.227",
        //     "AllowedPorts": "80/TCP, 443/TCP"
        // },
        // {
        //     "ContainerName": "yyyyy",
        //     "AllowFrom": "1.2.3.4, 99.99.99.99, 5.6.7.0/24",
        //     "AllowedPorts": "*"
        // },
        // {
        //     "ContainerName": "zzzzz",
        //     "AllowFrom": "*",
        //     "AllowedPorts": "80/TCP"
        // }
        public function ParseRule(object $rule): array {

            $containerIps = $this->dockerApi->getIpFromName($rule->ContainerName);
            $isRunning = $this->dockerApi->getStateFromName($rule->ContainerName);
            $containerPorts = $this->dockerApi->getOpenPortsFromName($rule->ContainerName);

            $fromIps = explode(',', $rule->AllowFrom);
            $portsAndTypes = explode(',', $rule->AllowedPorts);
            
            $generatedHashes = [];

            if (!$isRunning) {
                Logger::Log('Container is not running or doesnt exist: ' . $rule->ContainerName . "\n");
                return [];
            }

            foreach ($containerIps as $containerIp) {
                foreach ($fromIps as $fromIp) {
                    foreach ($portsAndTypes  as $portAndType) {

                        $port = 0;
                        $types = [];
                        if (strpos($portAndType, "/") > 0) {
                            $tmp = explode('/', $portAndType);
                            $port = trim($tmp[0]);
                            $types[] = trim(strtolower($tmp[1]));
                        } else {
                            $types = "tcp";
                            $types = "udp";
                        }

                        foreach ($types as $type) {

                            if (!array_key_exists($port, $containerPorts) || $containerPorts[$port] != $type) {
                                Logger::Log('Container ' . $rule->ContainerName . ' doesnt have this port publicly exposed: ' . $port . '/' . $type . "\n");
                                continue;
                            }

                            $createRule = $this->BuildRule($type, trim($fromIp), $containerIp, $rule->ContainerName, (int)$port, false);
                            $ruleCommand = $createRule->ruleCommand;
                            $hash = $createRule->hash;

                            if (!$this->RuleExist($hash)) {
                                $result = $this->Execute($ruleCommand);
                                Logger::Log('Created new UFW rule: ' . $ruleCommand);
                                Logger::Log('With result: ' . current($result));
                                $generatedHashes[] = $hash;                                
                            } else {
                                if (Main::$debug) {
                                    Logger::Log('Rule already exist, ignoring it');
                                    $generatedHashes[] = $hash; 
                                }
                            }

                        }
  
                    }
                }
            }

            return $generatedHashes;

        }

        public function cleanupOldRules(array $newRules): void {

            $rules = $this->getRules();
            $currentRules = [];
            foreach ($rules as $rule) {

                // check if we created this line
                if (strstr($rule, 'UFWDA') !== false) {
                    // find hash
                    preg_match("/\\[\s?([0-9]){1,4}\\].+- ([a-zA-Z0-9]{32}) -/", $rule, $matches);
                    $currentRules[$matches[1]] = $matches[2];
                }
            }

            ksort($currentRules, SORT_NUMERIC);
            $descCurrentRules = array_reverse($currentRules, true);

            foreach ($descCurrentRules as $ruleNum => $rule) {
                if (!in_array($rule, $newRules)) {
                    $result = $this->Execute('delete ' . $ruleNum, true);
                    Logger::Log('Unused rule deleted: ' . $rule);
                }
            }

        }

        private function BuildRule(string $protocol, string $source, string $to, string $container, int $port, bool $delete = false): object {

            $ruleHash = md5($protocol . '-' . $source . '-' . $to . '-' . $port);
            $comment = 'UFWDA - ' . $ruleHash . ' - ' . $container;
            $rule = ($delete ? 'delete ' : '') . "route allow proto $protocol from $source to $to port $port comment '$comment'";

            $result = new stdClass();
            $result->ruleCommand = $rule;
            $result->hash = $ruleHash;

            return $result;
        }

        private function RuleExist($hash): bool {
            $rules = $this->getRules();
            foreach ($rules as $rule) {
                if (strstr($rule, $hash) !== false)
                {
                    return true;
                }
            }
            return false;

        }

        private function Execute(string $command, bool $confirm = false): array {

           exec(($confirm ? 'yes | ' : '') . $this->Test() . ' ' . $command, $result, $resultCode);

           return $result;
        }

        public function Test(): string {
            $result = null;
            $resultCode = null;
            exec('which ufw', $result, $resultCode);
            
            if (count($result) == 0) {
                Logger::Log('UFW is not installed of UFW command cannot be found');
                return "No";
            }

            exec('which yes', $result2, $resultCode2);
            
            if (count($result2) == 0) {
                Logger::Log('YES is not installed ( dont be confued the yes command from GNU coreutils)');
                return "No";
            }            

            
            exec(current($result) . ' status', $resultStatus, $resultCode);

            if (strstr(strtolower(current($resultStatus)), 'inactive') !== false) {
                Logger::Log('UFW is inactive');
                return "No";
            }
            return current($result);
        }
    }
}