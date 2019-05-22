<?php

namespace Controller;

use PluralSightReader\PluralSightReader;
use Monolog\Logger;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

class AppController
{
    /**
     * @var Container $container
     */
    private $container;
    /**
     * @var Twig $renderer
     */
    private $renderer;
    /**
     * @var Logger $logger
     */
    private $logger;
    /**
     * @var \SlimSession\Helper $session
     */
    private $session;

    private $settings;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->renderer = $container->get("renderer");
        $this->logger = $container->get("logger");
        $this->session = $container->get("session");
        $this->settings = $container->get("settings");
    }

    private function render(Response $response, string $template, array $args)
    {
        $this->logger->info("Render " . $template . " from client: " . $_SERVER['HTTP_USER_AGENT'] . ", ip: " . $_SERVER['REMOTE_ADDR']);
        $args['settings'] = $this->settings;
        $sessionIterator = $this->session->getIterator();
        while ($sessionIterator->valid()) {
            $args['session'][$sessionIterator->key()] = $sessionIterator->current();
            $sessionIterator->next();
        }

        return $this->renderer->render($response, $template, $args);
    }

    private function loadUsers(&$users, &$usersDetails){
        $users = [];
        $usersDetails = [];
        if(is_file(__DIR__."/../../data/users.json")){
            $usersData = json_decode(file_get_contents(__DIR__."/../../data/users.json"), true);
            if(is_file(__DIR__."/../../data/header.json")){
                $header = json_decode(file_get_contents(__DIR__."/../../data/header.json"), true);
                $idColumn = array_search("id", $header);
            } else {
                $header = ['id'];
                $idColumn = 0;
            }
            foreach($usersData as $user){
                $users[] = $user[$idColumn];
                if(count($header)>1){
                    unset($header[0]);
                    foreach($header as $headerIdx => $headerValue){
                        $usersDetails[$user[$idColumn]][$headerValue] = $user[$headerIdx];
                    }
                }
            }
            return $header;
        }
        if($this->settings['pluralsight']['users']){
            $users = $this->settings['pluralsight']['users'];
        }
        if($this->settings['pluralsight']['usersDetails']){
            $usersDetails = $this->settings['pluralsight']['usersDetails'];
            return array_keys($usersDetails[0]);
        }
    }

    public function usersAction(Request $request, Response $response, array $args)
    {
        $skillsOrder = $this->settings['pluralsight']['order'];
        $psReader = new PluralSightReader($this->settings['pluralsight']['cache'], $this->logger);

        $users = [];
        $usersDetails = [];
        $header = $this->loadUsers($users, $usersDetails);

        $userData = $psReader->getUsers($users, $skillsOrder, $usersDetails);

        $skillSums = array_fill_keys(array_keys($skillsOrder), []);
        foreach ($userData as $userId => $data) {
            foreach ($data['skills'] as $skillId => $skillData) {
                if ($skillData['score'] > 0) {
                    $skillSums[$skillId][] = $skillData['score'];
                    if (strtotime($skillData['dateCompleted']) > time() - 2 * 24 * 3600) {
                        $data['skills'][$skillId] = array_merge($skillData, ['recent' => 'recent']);
                    }
                }
            }
            $userData[$userId] = $data;
        }
        $skillAvgs = [];
        foreach ($skillSums as $skillId => $skillSum) {
            $skillAvgs[$skillId] = round(array_sum($skillSum) / count($skillSum));
        }
        $args['userData'] = $userData;
        $args['skillAvgs'] = $skillAvgs;
        $args['order'] = $skillsOrder;
        $args['header'] = array_slice($header, 1);
        if(!empty($args['csv']) && $this->settings['pluralsight']['usersDetails'] && $this->session->signedInUser){
            $out = fopen('php://temp', 'w');
            //header
            fputcsv($out, array_merge(['id', 'name'], array_keys($skillsOrder)), ";");
            foreach ($userData as $userId => $user) {
                $fields = [
                    'id' => $userId,
                    'name' => $this->settings['pluralsight']['usersDetails'][$userId]['name']
                ];
                foreach (array_keys($skillsOrder) as $skill) {
                    $fields[$skill] = $user['skills'][$skill]['score'];

                }
                fputcsv($out, $fields, ";");
            }
            rewind($out);
            $csvData = stream_get_contents($out);
            fclose($out);

            $response->getBody()->rewind();
            $response->getBody()->write(iconv("UTF-8", "windows-1250",$csvData));
            return $response->withHeader('Content-Disposition', 'attachment; filename="users.csv"');
        } else {
            return $this->render($response, 'users.twig', $args);
        }
    }

    public function userAction(Request $request, Response $response, array $args)
    {
        $skillsOrder = $this->settings['pluralsight']['order'];
        $psReader = new PluralSightReader($this->settings['pluralsight']['cache'], $this->logger);
        $users = [];
        $usersDetails = [];
        $this->loadUsers($users, $usersDetails);
        $userData = $psReader->getUsers([$args['id']], $skillsOrder, $usersDetails);

        if (empty($userData[$args['id']])) {
            return $response->withStatus(404);
        }

        $args['userData'] = $userData[$args['id']];

        return $this->render($response, 'user.twig', $args);
    }

    public function recentAction(Request $request, Response $response, array $args)
    {
        $psReader = new PluralSightReader($this->settings['pluralsight']['cache'], $this->logger);
        $userData = $psReader->getRecent($this->settings['pluralsight']['users'], $this->settings['pluralsight']['recent_to_show'], $this->settings['pluralsight']['usersDetails']);

        $args['recent'] = $userData;

        return $this->render($response, 'recent.twig', $args);
    }

    public function tokenSignInAction(Request $request, Response $response, array $args)
    {
        $this->session->signedIn = false;
        $this->session->signedInUser = false;
        $post = $request->getParsedBody();

        // Get $id_token via HTTPS POST.

        $client = new \Google_Client(['client_id' => $this->settings['google']['clientId']]);  // Specify the CLIENT_ID of the app that accesses the backend
        $payload = $client->verifyIdToken($post['idtoken']);
        if ($payload) {
            $this->session->signedIn = true;
            $this->session->token = $post['accesstoken'];
            $this->session->user = $payload;
            if (!empty($payload['email']) && !empty($payload['email_verified']) && !empty($this->settings['users']) && in_array($payload['email'], $this->settings['users'])) {
                $this->session->signedInUser = true;
            }
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function tokenSignOutAction(Request $request, Response $response, array $args)
    {
        $this->session->signedIn = false;
        $this->session->signedInUser = false;
        return $response->withStatus(200);
    }

    public function importAction(Request $request, Response $response, array $args)
    {
        if(empty($this->settings['pluralsight']['userSheet'])){
            return $response->withStatus(404)->withJson([
                'error'=>true,
                'message'=>"Set ['pluralsight']['userSheet'] config option to get access to user list."
            ]);
        }
        $client = new \Google_Client(['client_id' => $this->settings['google']['clientId']]);
        if(!empty($this->session->token)){
            $client->setAccessToken($this->session->token);
            $service = new \Google_Service_Sheets($client);
            $result = $service->spreadsheets_values->get($this->settings['pluralsight']['userSheet'], "A:E");
            $rows = $result->getValues();
            if(empty($rows)){
                return $response->withStatus(404)->withJson([
                    'error'=>true,
                    'message'=>"Empty users set."
                ]);
            }
            $header = null;
            if(in_array("name", $rows[0])){ //check if there's a header in data
                $header = $rows[0];
                unset($rows[0]);
            }
            if(!empty($args['check'])){
                $numRows =  count($rows);
                return $response->withStatus(200)->withJson([
                    'message'=>"$numRows are about to be imported. Continue?"
                ]);
            }
            if(!empty($header)){
                file_put_contents(__DIR__."/../../data/header.json", json_encode($header));
            }
            file_put_contents(__DIR__."/../../data/users.json", json_encode($rows));

            return $response->withStatus(200)->withJson([
                'message'=>count($rows)." records were imported."
            ]);
        } else {
            return $response->withStatus(403)->withJson([
                'error'=>true,
                'message'=>"Invalid access token. Are you logged in?"
            ]);
        }

    }


}