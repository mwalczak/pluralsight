<?php

namespace PluralSightReader;

use Curl\Curl;
use Monolog\Logger;

class PluralSightReader
{
    private $serviceUrl = "https://app.pluralsight.com/profile/data/skillmeasurements/";
    private $cache;
    /**
     * @var Logger $logger
     */
    private $logger;
    /**
     * @var Curl $curl
     */
    private $curl;

    public function __construct($cache = false, Logger $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->curl = new Curl();
        $this->curl->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36');
    }

    public function getUsers(array $userIds){
        $users = [];
        foreach($userIds as $userId){
            $users[$userId] = $this->fetch($userId);
        }
        return $users;
    }

    private function fetch($userId, $ignoreCache = false){
        $cacheFile = $this->cache.$userId;
        if(!empty($this->cache) && is_file($cacheFile) && !$ignoreCache){
            return json_decode(file_get_contents($cacheFile));
        } else {
            $url = $this->serviceUrl.$userId;
            $this->curl->get($url);
            if ($this->curl->error) {
                $this->logger->info("PluralSightReader reading error: ".$url);
                return false;
            }
            else {
                $userData['id'] = $userId;
                $userData['skills'] = json_decode($this->curl->response);
                if(!empty($this->cache)){
                    file_put_contents($cacheFile, json_encode($userData));
                }
                return $userData;
            }
        }
    }
}