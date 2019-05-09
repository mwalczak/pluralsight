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

    public function getUsers(array $userIds, &$order){
        $users = [];
        //get user data
        foreach($userIds as $userId){
            $users[$userId] = $this->fetch($userId);
        }
        //fill order array
        $ordersWithNames = [];
        foreach($users as $userId=>$userData){
            if(!empty($userData['skills'])){
                foreach($userData['skills'] as $skill){
                    if(!in_array($skill['id'], $order)){
                        $order[] = $skill['id'];
                    }
                    $ordersWithNames[$skill['id']] = ['id'=>$skill['id'], 'title'=>$skill['title']];
                }
            }
        }
        $ordersWithNames = array_merge(array_flip($order), $ordersWithNames); //order array
        //order skills
        foreach($users as $userId=>$userData){
            if(!empty($userData['skills'])) {
                $userData['skills'] = array_merge(array_flip($order), $userData['skills']); //order array
            }
            $users[$userId] = $userData;
        }
        $order = $ordersWithNames;
        return $users;
    }

    public function getRecent(array $userIds, $limit = 0){
        $recent = [];
        //get user data
        foreach($userIds as $userId){
            $userData = $this->fetch($userId);
            if(!empty($userData['skills'])){
                foreach($userData['skills'] as $skill){
                    $skill['user'] = $userId;
                    $recent[$skill['dateCompleted']] = $skill;
                }
            }

        }
        krsort($recent);
        if($limit){
            $recent = array_slice($recent, 0, $limit);
        }

        return $recent;
    }

    private function fetch($userId, $ignoreCache = false){
        $cacheFile = $this->cache.$userId.".json";
        if(!empty($this->cache) && is_file($cacheFile) && !$ignoreCache){
            return json_decode(file_get_contents($cacheFile), true);
        } else {
            $url = $this->serviceUrl.$userId;
            $this->curl->get($url);
            if ($this->curl->error) {
                $this->logger->info("PluralSightReader reading error: ".$url);
                return false;
            }
            else {
                $userData['id'] = $userId;
                $skillsJson = json_decode($this->curl->response, true);
                $skills = [];
                if(!empty($skillsJson)){
                    foreach($skillsJson as $skill){
                        $skills[$skill['id']] = $skill;
                    }
                }
                $userData['skills'] = $skills;

                if(!empty($this->cache)){
                    file_put_contents($cacheFile, json_encode($userData));
                }
                return $userData;
            }
        }
    }
}