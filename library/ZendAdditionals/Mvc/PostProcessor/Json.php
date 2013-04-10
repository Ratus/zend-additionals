<?php
namespace ZendAdditionals\Mvc\PostProcessor;

class Json
{
    public function __invoke(\Zend\Mvc\MvcEvent $event)
    {
        $variables = $event->getResult();
        if (
            !($variables instanceof \ArrayAccess) &&
            !($variables instanceof \JsonSerializable) &&
            !is_array($variables) &&
            null !== $variables
        ) {
            // When a ViewModel or any other type of model has been returned
            // we don't want to override the response!
            return;
        }

        $model = new \Zend\View\Model\JsonModel();
        if (null !== $variables) {
            if ($variables instanceof \JsonSerializable) {
                $variables = $variables->jsonSerialize();
            }
            $model->setVariables($variables);
        }
        $model->setTerminal(true);

        // Workaround for jquery callbacks over jsonp
        $callback = $event->getRequest()->getQuery()->get('callback');

        if (!empty($callback)) {
            $model->setJsonpCallback($callback);
        }

        $event->setResult($model);
    }
}
