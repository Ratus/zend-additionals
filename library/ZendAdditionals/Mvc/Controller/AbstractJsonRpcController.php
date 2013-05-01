<?php
namespace ZendAdditionals\Mvc\Controller;

use ZendAdditionals\Mvc\Controller\AbstractActionController;
use ZendAdditionals\Exception;
use Zend\Json\Server\Server;
use Zend\Json\Server\Smd;

/**
 * @category   ZendAdditionals
 * @package    Mvc
 * @subpackage Controller
 */
abstract class AbstractJsonRpcController extends AbstractActionController
{
    protected $jsonRpcServer;

    /**
     * Constructs a new JsonRpcController
     */
    public function __construct()
    {
        $this->jsonRpcServer = new Server();
        $this->jsonRpcServer->getRequest()->setVersion(Server::VERSION_2);
    }

    /**
     * Returns what must be set in
     * {@see Zend\Json\Server\Server::setClass}
     */
    protected function getJsonRpcClass()
    {
        throw new Exception\NotImplementedException(
            'When extending "' . __CLASS__ . '" "' . get_called_class() . '" ' .
            'must implement method: "' . __METHOD__ . '"!'
        );
    }

    /**
     * Default action to handle json rpc calls.
     *
     * @return mixed
     */
    public function indexAction()
    {
        $serviceClass  = $this->getJsonRpcClass();
        $jsonRpcServer = $this->jsonRpcServer;
        $jsonRpcServer->setClass($serviceClass);
        $jsonRpcServer->getRequest()->setMethod(
            $this->getRequest()->getPost('method', null)
        );
        $params = (array)$this->getRequest()->getPost();
        unset($params['method']);
        $id = '';
        if (isset($params['id'])) {
            $id = $params['id'];
            unset($params['id']);
        }
        if (isset($params['params'])) {
            $jsonRpcServer->getRequest()->setParams(
                $params['params']
            );
        }
        $jsonRpcServer->setReturnResponse(true);
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            $uri        = $this->getRequest()->getUri();
            $serviceUri = $uri->getHost() . $uri->getPath();
            $serviceMap = $jsonRpcServer->getServiceMap();
            $smd        = $serviceMap->setEnvelope(Smd::ENV_JSONRPC_2);
            $smdArray   = array_merge(
                array(
                    'service' => $serviceUri,
                ),
                $smd->toArray()
            );
            // Services and methods seem to be equal, remove verbose info
            unset($smdArray['services']);
            // Fix duplicate method return posibilities when methods have
            // optional parameters
            foreach ($smdArray['methods'] as &$method) {
                if (is_array($method['returns'])) {
                    $method['returns'] = $method['returns'][0];
                }
            }
            return $smdArray;
        } else {
            $response = $jsonRpcServer->handle();
            if ($response instanceof \Zend\Json\Server\Response\Http) {
                $response->setId($id);
                $responseArray = array(
                    'jsonrpc' => $response->getVersion(),
                    'id'      => $response->getId(),
                );
                if ($response->isError()) {
                    $responseArray['error'] = $response->getError()->toArray();
                } else {
                    $responseArray['result'] = $response->getResult();
                }
                return $responseArray;
            }
            return (string) $response;
        }
    }
}
