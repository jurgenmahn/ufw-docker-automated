<?php
declare(strict_types = 1);

namespace JurgenMahn\UfwDocker\Api {

    use JurgenMahn\UfwDocker\Config;
    use JurgenMahn\UfwDocker\Log\Logger;
    use \Main;
    use stdClass;

    class Docker
    {
        private $containerCache = null;
        private $lastUpdate = 0;

        public function __construct() 
        {
            
        }

        public function getContainers(): array {
            if (!$this->containerCache || $this->lastUpdate + Main::$settings->CheckIntervalInSec < time()) {
                $this->containerCache = $this->parseContainerResult($this->Execute('GET', 'containers/json', null));
                $this->lastUpdate = time();
            }
            return $this->containerCache;
        }

        public function getContainerByName(string $search): ?object {
            
            foreach($this->getContainers() as $container) {
                foreach ($container->Names as $name) {
                    if (trim($name, '/') == $search) {
                        Logger::Log("Container found: " . $search . "\n");
                        return $container;
                    }
                }
            }

            Logger::Log("Warning, container doesnt exist: " . $search . "\n");
            return null;
        }

        public function getIpFromName(string $search): ?array {
            $container = $this->getContainerByName($search);

            if ($container) {
                return $container->Ips;
            }

            return null;
        }

        public function getOpenPortsFromName(string $search): ?array {
            $container = $this->getContainerByName($search);

            if ($container) {
                $ports = [];
                foreach($container->Ports as $port) {
                    $ports[$port->PublicPort] = $port->Type;
                }
                return $ports;
            }

            return null;
        }   
        
        public function getStateFromName(string $search): bool {
            $container = $this->getContainerByName($search);

            if ($container) {
               return ($container->State == 'running');
            }

            return false;
        }         

        private function parseContainerResult(array $containerResult): array {
         
            $retval = [];
            foreach ($containerResult as $container) {

                $state = $container->State;
                $id = $container->Id;

                $retval[$id] = new stdClass();
                $retval[$id]->State = $state;


                // ["IP"]=>
                // string(7) "0.0.0.0"
                // ["PrivatePort"]=>
                // int(3338)
                // ["PublicPort"]=>
                // int(3338)
                // ["Type"]=>
                // string(3) "tcp"                
                $retval[$id]->Ports = $container->Ports;

                $retval[$id]->Names = $container->Names;
                
                $containerIps = [];
                foreach ($container->NetworkSettings->Networks as $network) {
                    $containerIps[] = $network->IPAddress;
                }
                $retval[$id]->Ips = $containerIps;
            }

            return $retval;
            
        }

        private function Execute(string $method, string $endPoint, ?string $payload): array {

            $url = Main::$settings->DockerApiUrl . $endPoint;
            $socket = Main::$settings->DockerSocket;
            $timeout = 3000;

            Logger::Log("Calling docker API over socket " . $socket . " with url " . $url . "\n");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
            curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socket);

            if (Main::$debug) {
                curl_setopt($ch, CURLOPT_VERBOSE, true);
            }
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, strlen($payload) > 0 ? true : false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            }

            $jsonData = curl_exec($ch);

            if (!$jsonData) 
            {
                throw new \Exception('Error making docker API call, cURL error: ' . curl_error($ch));
            }

            curl_close($ch);

            Logger::Log("Connection succeed\n");

            $retval = json_decode($jsonData, false);    
            if ($retval) {
                Logger::Log("Response received from Docker API: " . $jsonData . "\n\n");
            }

            return $retval;
        }

        public function Test(): string {
            $result = null;
            $resultCode = null;
            exec('which docker', $result, $resultCode);

            if (count($result) == 0) {
                return "No";
            }
            return current($result);            
        }        

    }

}
