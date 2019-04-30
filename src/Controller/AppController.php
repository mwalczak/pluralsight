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

    private $settings;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->renderer = $container->get("renderer");
        $this->logger = $container->get("logger");
        $this->settings = $container->get("settings");
    }

    private function render(Response $response, string $template, array $args)
    {
        $this->logger->info("Render ".$template. " from client: ".$_SERVER['HTTP_USER_AGENT'].", ip: ".$_SERVER['REMOTE_ADDR']);
        return $this->renderer->render($response, $template, $args);
    }

    public function usersAction(Request $request, Response $response, array $args)
    {
        $skillsOrder = $this->settings['pluralsight']['order'];
        $psReader = new PluralSightReader($this->settings['pluralsight']['cache'], $this->logger);
        $userData = $psReader->getUsers($this->settings['pluralsight']['users'], $skillsOrder);

        $skillSums = array_fill_keys(array_keys($skillsOrder), []);
        foreach($userData as $userId=>$data){
            foreach($data['skills'] as $skillId => $skillData){
                if($skillData['score']>0){
                    $skillSums[$skillId][]=$skillData['score'];
                }
            }
        }
        $skillAvgs = [];
        foreach($skillSums as $skillId=>$skillSum){
            $skillAvgs[$skillId] = round(array_sum($skillSum)/count($skillSum));
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
        $userData = $psReader->getUsers([$args['id']], $skillsOrder);

        if (empty($userData[$args['id']])) {
            return $response->withStatus(404);
        }
        $args['userData'] = $userData[$args['id']];

        return $this->render($response, 'user.twig', $args);
    }

}