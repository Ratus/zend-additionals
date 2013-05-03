<?php
namespace ZendAdditionals\Mvc\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use ZendAdditionals\Exception;
use Zend\Json\Server\Server;
use Zend\Json\Server\Smd;
use Zend\Mvc\MvcEvent;

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
    protected function getRpcService()
    {
        throw new Exception\NotImplementedException(
            'When extending "' . __CLASS__ . '" "' . get_called_class() . '" ' .
            'must implement method: "' . __METHOD__ . '"!'
        );
    }

    /**
     * Set Access-Control headers
     *
     * @param MvcEvent $mvcEvent
     * @return mixed
     */
    public function onDispatch(MvcEvent $mvcEvent)
    {
        /** @var \Zend\Http\PhpEnvironment\Response */
        $response = $mvcEvent->getResponse();

        if ($response instanceof \Zend\Http\PhpEnvironment\Response) {
            $response->getHeaders()
                ->addHeaderLine('Access-Control-Allow-Origin','*')
                ->addHeaderLine('Access-Control-Allow-Headers','origin, content-type, accept')
                ->addHeaderLine('Access-Control-Allow-Methods','POST GET');
        }

        return parent::onDispatch($mvcEvent);
    }

    /**
     * Default action to handle json rpc calls.
     *
     * @return mixed
     */
    public function indexAction()
    {
        $serviceClass  = $this->getRpcService();
        $jsonRpcServer = $this->jsonRpcServer;
        $jsonRpcServer->setClass($serviceClass);
        $jsonRpcServer->setReturnResponse(true);
        if ('GET' == $this->getRequest()->getMethod()) {
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
