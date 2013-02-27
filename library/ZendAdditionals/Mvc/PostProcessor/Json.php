<?php
namespace ZendAdditionals\Mvc\PostProcessor;

/**
 *
 */
class Json
{
    public function __invoke(\Zend\Mvc\MvcEvent $event)
    {
        $variables = $event->getResult();
        if (!is_array($variables) && null !== $variables) {
            // When a ViewModel or any other type of model has been returned
            // we don't want to override the response!
            return;
        }
        
        $model = new \Zend\View\Model\JsonModel();
        if (null !== $variables) {
            $model->setVariables($variables);
        }
        $model->setTerminal(true);
        
        // Workaround for jquery callbacks over jsonp
        $callback = $event->getRouteMatch()->getParam('callback');
        if (!empty($callback)) {
            $model->setJsonpCallback($callback);
        }
        
        $event->setResult($model);
    }
}
