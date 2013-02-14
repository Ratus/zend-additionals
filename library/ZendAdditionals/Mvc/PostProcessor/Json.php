<?php
namespace ZendAdditionals\Mvc\PostProcessor;

/**
 *
 */
class Json
{
    public function __invoke(\Zend\Mvc\MvcEvent $event)
    {
        $variables = null;
        $eventResult = $event->getResult();
        if ($eventResult instanceof \Zend\View\Model\ViewModel) {
            if (is_array($eventResult->getVariables())) {
                $variables = $eventResult->getVariables();
            } else {
                $variables = null;
            }
        } else {
            $variables = $eventResult;
        }
        $model = new \Zend\View\Model\JsonModel();
        if (null !== $variables) {
            $model->setVariables($variables);
        }
        $model->setTerminal(true);

        // Workaround for jquery callbacks over jsonp
        if (isset($_REQUEST['callback'])) {
            $model->setJsonpCallback($_REQUEST['callback']);
        }

        $event->setResult($model);
    }
}
