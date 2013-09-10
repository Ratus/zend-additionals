<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendAdditionals\View\Helper;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\View\Exception;
use Zend\Uri\Http as HttpUri;

/**
 * Helper for making easy links and getting urls that depend on the routes and router.
 */
class Url extends \Zend\View\Helper\Url
{

    /**
     *
     * @var HttpUri
     */
    protected $uri;

    /**
     * Force an Uri to be used for this helper
     *
     * @param \Zend\Uri\Http $uri
     *
     * @return \ZendAdditionals\View\Helper\Url
     */
    public function setUri(HttpUri $uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return \Zend\Uri\Http
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return \Zend\Mvc\Router\RouteMatch
     */
    public function getRouteMatch()
    {
        return $this->routeMatch;
    }

    /**
     * Generates an url given the name of a route.
     *
     * @see    Zend\Mvc\Router\RouteInterface::assemble()
     */
    public function __invoke($name = null, $params = array(), $options = array(), $reuseMatchedParams = false)
    {
        if (!isset($options['uri']) && $this->uri instanceof HttpUri) {
            $options['uri'] = $this->uri;
        }

        return parent::__invoke($name, $params, $options, $reuseMatchedParams);
    }
}
