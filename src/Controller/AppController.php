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

    public function usersAction(Request $request, Response $response, array $args)
    {
        $skillsOrder = $this->settings['pluralsight']['order'];
        $psReader = new PluralSightReader($this->settings['pluralsight']['cache'], $this->logger);
        $userData = $psReader->getUsers($this->settings['pluralsight']['users'], $skillsOrder, $this->settings['pluralsight']['usersDetails']);

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
        return $this->render($response, 'users.twig', $args);
    }

    public function userAction(Request $request, Response $response, array $args)
    {
        $skillsOrder = $this->settings['pluralsight']['order'];
        $psReader = new PluralSightReader($this->settings['pluralsight']['cache'], $this->logger);
        $userData = $psReader->getUsers([$args['id']], $skillsOrder, $this->settings['pluralsight']['usersDetails']);

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


}