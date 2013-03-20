<?php

namespace ZendAdditionals\Mvc\Controller;

/**
 * @category   ZendAdditionals
 * @package    Mvc
 * @subpackage Controller
 */
trait TraitAuthenticationController
{
    /**
     * @var RouteMatch
     */
    protected $routeMatch;

    /**
     * @var mixed
     */
    protected $config;

    /**
     * @var integer
     */
    protected $applicationId;

    /**
     * @var integer
     */
    protected $customId;

    /**
     * @return mixed
     */
    public function getConfig()
    {
        if (empty($this->config)) {
            $this->config = $this->getServiceLocator()->get('Config');
        }
        return $this->config;
    }

    /**
     * @return integer
     */
    public function getApplicationId()
    {
        if (empty($this->applicationId)) {
            $config = $this->getServiceLocator()->get('Config');
            $this->applicationId = $config['application']['id'];
        }

        return $this->applicationId;
    }

    /**
     * @return integer
     */
    public function getCustomId()
    {
        if (empty($this->applicationId)) {
            $config = $this->getServiceLocator()->get('Config');
            $this->applicationId = $config['custom']['id'];
        }

        return $this->applicationId;
    }

    /**
     * Get controller based on the full classname
     * The required objects will be injected in the controller
     *
     * @param string $controllerName The fullclassname of the controller
     * @return AbstractController
     */
    public function getController($controllerName)
    {
        $controller = new $controllerName();
        $controller->setEventManager($this->getEventManager());
        $controller->setPluginManager($this->getPluginManager());
        $controller->setServiceLocator($this->getServiceLocator());
        $controller->setRequest($this->getRequest());
        $controller->setResponse($this->getResponse());

        return $controller;
    }

    /**
     * @return RouteMatch | null
     */
    protected function getRouteMatch()
    {
        return $this->routeMatch;
    }

    /**
     * This will be called on the onDispatch event
     *
     * @param RouteMatch $routeMatch
     * @return self
     */
    protected function setRouteMatch(\Zend\Mvc\Router\Http\RouteMatch $routeMatch)
    {
        $this->routeMatch = $routeMatch;
        return $this;
    }
}
