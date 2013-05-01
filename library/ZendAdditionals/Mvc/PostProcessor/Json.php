<?php
namespace ZendAdditionals\Mvc\PostProcessor;

use ZendAdditionals\Stdlib\StringUtils;

class Json
{
    public function __invoke(\Zend\Mvc\MvcEvent $event)
    {
        $variables   = $event->getResult();
        $alreadyJson = false;
        if (
            !($variables instanceof \ArrayAccess) &&
            !($variables instanceof \JsonSerializable) &&
            !is_array($variables) &&
            false === ($alreadyJson = StringUtils::isJson($variables)) &&
            null !== $variables
        ) {
            // When a ViewModel or any other type of model has been returned
            // we don't want to override the response!
            return;
        }

        if ($alreadyJson) {
            $variables = (array) json_decode($variables);
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
