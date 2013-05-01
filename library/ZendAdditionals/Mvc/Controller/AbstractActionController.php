<?php
namespace ZendAdditionals\Mvc\Controller;

class AbstractActionController extends \Zend\Mvc\Controller\AbstractActionController
{
    use \ZendAdditionals\Mvc\Controller\TraitAuthenticationController;

    const REQUEST_GET       = 'GET',
          REQUEST_POST      = 'POST',
          REQUEST_HEAD      = 'HEAD',
          REQUEST_OPTIONS   = 'OPTIONS',
          REQUEST_PATCH     = 'PATCH',
          REQUEST_PUT       = 'PUT',
          REQUEST_TRACE     = 'TRACE';

    /**
    * @return \Zend\Http\PhpEnvironment\Request
    */
    public function getRequest()
    {
        return parent::getRequest();
    }
}
