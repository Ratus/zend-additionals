<?php
namespace ZendAdditionals\Mvc;

abstract class AbstractPostProcessingModule
{    
    /**
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onBootstrap($event)
    {
        $moduleManager = $event->getApplication()->getServiceManager()->get('modulemanager');
        /* @var $moduleManager \Zend\ModuleManager\ModuleManager */
        $events = $moduleManager->getEventManager()->getSharedManager();
        /* @var $events \Zend\EventManager\SharedEventManager */
        $serviceManager = $event->getApplication()->getServiceManager();
        /* @var $serviceManager \Zend\ServiceManager\ServiceManager */
        if (!$serviceManager->has('JsonPostProcessor')) {
            $serviceManager->setInvokableClass('JsonPostProcessor', 'ZendAdditionals\Mvc\PostProcessor\Json');
        }
        
        $events->attach(
            'Zend\Mvc\Controller\AbstractActionController',
            \Zend\Mvc\MvcEvent::EVENT_DISPATCH,
            array($this, 'postProcess'),
            -50
        );
        
        $events->attach(
            'Zend\Mvc\Application',
            \Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR,
            array($this, 'errorProcess'),
            -50
        );
    }
    
    /**
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function postProcess(\Zend\Mvc\MvcEvent $event)
    {
        $routeMatch     = $event->getRouteMatch();

        // Dont do stuff without a http routematch
        if (!($routeMatch instanceof \Zend\Mvc\Router\Http\RouteMatch)) {
            return;
        }
        $formatter      = $routeMatch->getParam('formatter', false);
        $serviceManager = $event->getApplication()->getServiceManager();

        // When no formatter has been defined return
        if (false === $formatter) {
            return;
        }
        $postProcessor = ucfirst(strtolower($formatter)) . 'PostProcessor';

        // When the service manager does not know this post processor return
        if (!$serviceManager->has($postProcessor)) {
            return;
        }

        // Call the post processor invokable with the event
        $postProcess = $serviceManager->get($postProcessor);
        
        $postProcess($event);
    }

    /**
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function errorProcess(\Zend\Mvc\MvcEvent $event)
    {
        $eventParams = $event->getParams();

        /** @var array $configuration */
        $configuration = $event->getApplication()->getConfig();

        $vars = array();
        if (isset($eventParams['exception'])) {
            /** @var \Exception $exception */
            $exception = $eventParams['exception'];
            if ($configuration['view_manager']['display_exceptions']) {
                $vars['error-message'] = $exception->getMessage();
                $vars['error-trace'] = $exception->getTrace();
            }
        }
        $event->setResult($vars);
        $this->postProcess($event);
    }
}
