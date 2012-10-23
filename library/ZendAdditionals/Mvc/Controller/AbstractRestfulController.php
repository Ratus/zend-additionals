<?php

namespace ZendAdditionals\Mvc\Controller;

use Zend\Http\Request as HTTPRequest;
use Zend\Http\Response as HTTPResponse;
use Zend\Mvc\Exception;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Mvc\Controller\AbstractController;
use ZendAdditionals\Exception\NotImplementedException;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\Between;
use ZendAdditionals\Db\Sql\Predicate\NotBetween;
use Zend\Db\Sql\Predicate\Like;
use ZendAdditionals\Db\Sql\Predicate\NotLike;
use Zend\Db\Sql\Predicate\In;
use ZendAdditionals\Db\Sql\Predicate\NotIn;
use Zend\Mvc\Router\RouteMatch;

/**
 * Abstract RESTful controller
 *
 * @category   ZendAdditionals
 * @package    ZendAdditionals_Mvc
 * @subpackage Controller
 */
abstract class AbstractRestfulController extends AbstractController
{
    /**
     * @var boolean
     */
    protected $enableTunneling = false;

    /**
     * @var string
     */
    protected $eventIdentifier = __CLASS__;

    /**
     * Return available HTTP methods and other options
     *
     * @return mixed
     */
    abstract public function getOptions($id = null);

    /**
     * Return the entity mapper for this collection
     *
     * @return \ZendAdditionals\Db\Mapper\AbstractMapper
     */
    abstract protected function getMapper();

    /**
     * Return an array containing the allowed columns to filter
     *
     * @return array
     */
    abstract protected function getAllowedColumnsFilter();

    /**
     * Return an integer containing the maximum amount of resources
     * to provide within a collection
     *
     * @return integer
     */
    abstract protected function getMaxResources();

    /**
     * Get an array of filter patterns, these patterns will be used
     * to match against the X-Filter-By headers to prevent weird
     * filters to come through.
     *
     * @return array
     */
    abstract protected function getFilterPatterns();

    /**
     * Generate  and / or extend the current filter based on the parent information
     *
     * @param array $parent
     * @param array $filter
     * @throws NotImplementedException
     *
     * @return array<Operator>
     */
    protected function createParentFilter(array $parent, array $filter = null)
    {
        throw new NotImplementedException(
            'The method ' . __METHOD__ . ' must be implemented by class ' . __CLASS__
        );
    }

    /**
     * Get the unique column identifier, this only needs to be implemented
     * when the restful api supports GET with an identifier
     *
     * @throws NotImplementedException
     */
    protected function getUniqueIdentifier()
    {
        throw new NotImplementedException(
            'The method ' . __METHOD__ . ' must be implemented by class ' . __CLASS__
        );
    }

    /**
     * Get an array contianing direct relations from this resource
     *
     * @return array
     */
    protected function getRelations()
    {
        return array();
    }

    /**
     * Return a default list of entities to join
     *
     * @return array|null
     */
    protected function getDefaultJoins()
    {
        return null;
    }

    /**
     * Return a default order by array
     *
     * @return array|null
     */
    protected function getDefaultOrderBy()
    {
        return null;
    }

    protected function stripEntityAttributes(array $entity)
    {
        foreach ($entity as $key => &$column) {
            if (
                is_array($column) &&
                !isset($column['entity_id']) &&
                !isset($column['attribute_id'])
            ) {
                // We have found a sub-entity
                $column = $this->stripEntityAttributes($column);
            } elseif(is_array($column)) {
                // We have found an attribute
                $entity[$key] = $column['value'];
            }
        }
        return $entity;
    }

    /**
     * Return list of resources
     *
     * @param array $range [optional] Selection range like
     * array(
     *     'begin' => 1, // Required if array provided
     *     'end'   => 50 // Always optional
     * )
     * @param array $filter [optional] Selection filter like
     * array(
     *     'key'       => 'some_val',        // column key must match value some_val
     *     'other_key' => 'some_wile_*card', // other_key must match some_wile_%card
     *     'entity' => array(
     *         'col' => 50,                  // entity join column col must match 50
     *         '...' => array(...),          // more sub joins with match filters
     *         '...' => '*',                 // more columns match filters
     *     ),
     * )
     * @param array $orderBy [optional] Order the selection like
     * array(
     *     'key_one' => 'DESC',     // column key_one will sort descending
     *     'key_two' => 'ASC',      // column key_two will sort ascending
     *     'entity' => array(
     *         'col' => 'ASC',      // entity join col will sort ascending
     *         '...' => array(...), // more sub joins with order by's
     *         '...' => 'ASC',      // more culumns order by's
     *     ),
     * )
     * @param array $parent [optional] Contains parent information like
     * array(
     *     'collection' => 'somecollection',  // The parent collection through which this
     *                                        // collection got called
     *     'id'         => 'some_identifier', // The identifier of the parent collection
     * )
     *
     * @return mixed
     */
    public function getList(
        array $range = null,
        array $filter = null,
        array $orderBy = null,
        array $parent = null
    ) {
        $mapper = $this->getMapper();

        if (!empty($parent)) {
            $filter = $this->createParentFilter($parent, $filter);
        }

        $joins = $this->getDefaultJoins();

        $count = $mapper->count($filter, $joins);
        if ($range === null) {
            $range = array(
                'begin' => 0,
                'end' => 100,
            );
        }

        if (!isset($range['end']) || $range['end'] > $this->getMaxResources()) {
            $range['end'] = $this->getMaxResources();
        }

        if ($range['begin'] > $count || $count <= 0) {
            $this->getResponse()->setStatusCode(204);
            return;
        }

        if ($range['end'] > $count) {
            $range['end'] = $count;
        }
        $columnsFilter = $this->getAllowedColumnsFilter();
        if (null === $orderBy) {
            $orderBy = $this->getDefaultOrderBy();
        }

        $results = $mapper->search($range, $filter, $orderBy, $joins, $columnsFilter, false);

        $this->getResponse()->getHeaders()->addHeaderLine(
            'Content-Range',
            'resources=' . $range['begin'] . '-' . $range['end'] . '/' . $count
        );

        foreach ($results as &$result) {
            $result = $this->stripEntityAttributes($result);
        }

        return $results;
    }

    /**
     * Return a single resource
     *
     * @param mixed $id The identifier for this resource
     * @param array $parent [optional] Contains parent information like
     * array(
     *     'collection' => 'somecollection',  // The parent collection through which this
     *                                        // collection got called
     *     'id'         => 'some_identifier', // The identifier of the parent collection
     * )
     *
     * @return mixed
     */
    public function get($id, $parent = null)
    {
        $mapper = $this->getMapper();

        $filter = array();
        if (!empty($parent)) {
            $filter = $this->createParentFilter($parent, $filter);
        }

        $range = array(
            'begin' => 0,
            'end'   => 1,
        );

        $uniqueIdentifier          = $this->getUniqueIdentifier();
        $filter[$uniqueIdentifier] = $id;

        $joins         = $this->getDefaultJoins();
        $columnsFilter = $this->getAllowedColumnsFilter();

        $results = $mapper->search(
            $range,
            $filter,
            null,
            $joins,
            $columnsFilter,
            false
        );

        if (!empty($results)) {
            return $this->stripEntityAttributes($results[0]);
        }
    }

    protected function entityToArray($entity)
    {
        $hydrator = $this->getMapper()->getHydrator();
        $return = $hydrator->extractRecursive($entity);
        // TODO: filter return
        return $return;
    }

    /**
     * Create a new resource
     *
     * @param  mixed $data
     * @return mixed
     */
    abstract public function create($data);

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    abstract public function update($id, $data);

    /**
     * Delete an existing resource
     *
     * @param  mixed $id
     * @return mixed
     */
    abstract public function delete($id);

    /**
     * Basic functionality for when a page is not available
     *
     * @return array
     */
    public function notFoundAction()
    {
        $this->response->setStatusCode(404);

        return array('content' => 'Page not found');
    }

    /**
     * Dispatch a request
     *
     * If the route match includes an "action" key, then this acts basically like
     * a standard action controller. Otherwise, it introspects the HTTP method
     * to determine how to handle the request, and which method to delegate to.
     *
     * @events dispatch.pre, dispatch.post
     * @param  Request $request
     * @param  null|Response $response
     * @return mixed|Response
     * @throws Exception\InvalidArgumentException
     */
    public function dispatch(Request $request, Response $response = null)
    {
        if (!$request instanceof HttpRequest) {
            throw new Exception\InvalidArgumentException('Expected an HTTP request');
        }

        ini_set('html_errors', 0);
        ini_set('xdebug.cli_color', 2);

        return parent::dispatch($request, $response);
    }

    /**
     * Get the HTTPRequest
     *
     * @return HTTPRequest
     */
    public function getRequest()
    {
        $request = parent::getRequest();

        $this->checkEnableTunneling($request);
        if ($this->getTunnelingEnabled()) {
            $this->setRequestMethodFromQuery($request);
            $this->setRequestHeadersFromQuery($request);
        }

        return $request;
    }

    /**
     * Check if the given request contains tunneled parameters
     * for the method and/or the headers
     *
     * @param HTTPRequest $request
     */
    protected function checkEnableTunneling(HTTPRequest $request)
    {
        $this->enableTunneling = ($request->getQuery('et') == 1);
    }

    /**
     * Check if we are now in tunneled mode
     *
     * @return boolean
     */
    public function getTunnelingEnabled()
    {
        return $this->enableTunneling;
    }

    /**
     * Manipulate the http method based on the method parameter passed
     * in the url.
     *
     * @param HTTPRequest $request
     */
    protected function setRequestMethodFromQuery(HTTPRequest $request)
    {
        if (null !== ($method = $request->getQuery('method'))) {
            $request->setMethod(strtoupper($method));
        }
    }

    /**
     * Manipulate the request headers based on query parameters passed
     * in the url.
     *
     * @param HTTPRequest $request
     */
    protected function setRequestHeadersFromQuery(HTTPRequest $request)
    {
        if (null !== ($range = $request->getQuery('range'))) {
            $request->getHeaders()->addHeaderLine(
                'Range',
                'resources=' . $range
            );
        }
        if (null !== ($filterBy = $request->getQuery('filterby'))) {
            $request->getHeaders()->addHeaderLine(
                'X-Filter-By',
                base64_decode($filterBy)
            );
        }
        if (null !== ($orderby = $request->getQuery('orderby'))) {
            $request->getHeaders()->addHeaderLine(
                'X-Order-By',
                base64_decode($orderby)
            );
        }
        if (null !== ($authorize = $request->getQuery('authorize'))) {
            $request->getHeaders()->addHeaderLine(
                'Authorization',
                base64_decode($authorize)
            );
        }
        if (null !== ($browserDigest = $request->getQuery('browserdigest'))) {
            $request->getHeaders()->addHeaderLine(
                'X-Browser-Digest',
                $browserDigest
            );
        }
    }

    /**
     * Verify authentication to the given collection and method
     *
     * @param RouteMatch $routeMatch
     *
     * @return boolean
     */
    protected function verifyAuthentication(RouteMatch $routeMatch)
    {
        return true;
    }

    /**
     * Handle the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException if no route matches in event or invalid HTTP method
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        if (!$routeMatch) {
            throw new Exception\DomainException(
                'Missing route matches; unsure how to retrieve action'
            );
        }

        $request = $e->getRequest(); /*@var $request \Zend\Http\PhpEnvironment\Request*/

        $action  = $routeMatch->getParam('action', false);
        /*@var $headers \Zend\Http\Header\Accept*/
        if ($action) {
            // Handle arbitrary methods, ending in Action
            $method = static::getMethodFromAction($action);
            if (!method_exists($this, $method)) {
                $method = 'notFoundAction';
            }
            $return = $this->$method();
        } else {

            $varyOptions = array(
                'Accept',
                'Accept-Encoding',
                'Accept-Language',
            );

            $requestId = null;
            if (null === ($requestId = $routeMatch->getParam('id'))) {
                $varyOptions[] = 'Range';
                $varyOptions[] = 'X-Order-By';
                $varyOptions[] = 'X-Filter-By';
            }

            $range = null;
            $filter = null;
            $orderBy = null;
            $parent = null;

            if (
                ($rangeHeader = $this->getRequest()->getHeader('range')) &&
                ($rawRange = $rangeHeader->getFieldValue()) &&
                preg_match('/^resources=([0-9]+)-([0-9]+)?/', $rawRange, $rangeMatches)
            ) {
                $range = array(
                    'begin' => $rangeMatches[1],
                    'end'   => (isset($rangeMatches[2]) ? $rangeMatches[2] : null),
                );
            }

            $filterPatterns = $this->getFilterPatterns();

            $checkFilterPatterns = function($subject, $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match("/{$pattern}/", $subject)) {
                        return true;
                    }
                }
                return false;
            };

            $createOperator = function($column, $operand, $value) {
                if ($value === 'null') {
                    $value = null;
                }
                switch ($operand) {
                    case '!=':
                    case '=':
                        $not = $operand === '!=';
                        if ((preg_match('/^([0-9]+)-([0-9]+)$/', $value, $matches))) {
                            return ($not ?
                                new NotBetween($column, $matches[1], $matches[2]) :
                                new Between($column, $matches[1], $matches[2])
                            );
                        }
                        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})-([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})$/', $value, $matches)) {
                            return ($not ?
                                new NotBetween($column, $matches[1], $matches[2]) :
                                new Between($column, $matches[1], $matches[2])
                            );
                        }
                        if (strpos($value, '|') !== false) {
                            $values = explode('|', $value);
                            return ($not ?
                                new NotIn($column, $values) :
                                new In($column, $values)
                            );
                        }
                        if (strpos($value, '*') !== false) {
                            $value = str_replace('*', '%', $value);
                            return ($not ?
                                new NotLike($column, $value) :
                                new Like($column, $value)
                            );
                        }
                        return new Operator($column, $operand, $value);
                    case '>':
                    case '<':
                    case '>=':
                    case '<=':
                        return new Operator($column, $operand, (int)$value);
                        break;

                }
                throw new \UnexpectedValueException(
                    'Did not expect the operand "' . $operand .
                    '", this has not been implemented.'
                );
            };

            if (
                ($filterByHeader = $this->getRequest()->getHeader('xfilterby')) &&
                ($rawFilter = $filterByHeader->getFieldValue())
            ) {
                $filter = array();
                $filterParts = explode(',', $rawFilter);
                foreach ($filterParts as $filterPart) {
                    if (preg_match('/^([a-z]{1}[a-z0-9_\.]*)(=|!=|>=|<=|>|<)([0-9]+-[0-9]+|[a-zA-Z0-9\*-_\|\s:]+)$/', $filterPart, $matches)) {
                        $column  = $matches[1];
                        $operand = $matches[2];
                        $value   = $matches[3];
                        //$value = $matches[4];
                        if (
                            !isset($filterPatterns[$column]) ||
                            !$checkFilterPatterns($operand . $value, $filterPatterns[$column])
                        ) {
                            continue;
                        }

                        $filterParts = explode('.', $column);
                        $pointer = &$filter;
                        foreach ($filterParts as $part) {
                            if (!isset($pointer[$part])) {
                                $pointer[$part] = array();
                            }
                            $pointer = &$pointer[$part];
                        }
                        $pointer = $createOperator($part, $operand, $value);
                    }
                }
            }

            if (
                ($orderByHeader = $this->getRequest()->getHeader('xorderby')) &&
                ($rawOrder = $orderByHeader->getFieldValue())
            ) {
                $orderBy = array();
                $orderParts = explode(',', $rawOrder);
                foreach ($orderParts as $orderPart) {
                    if (preg_match('/^([a-z]{1}[a-z0-9_]*)=(ASC|DESC)/i', $orderPart, $matches)) {
                        $orderBy[$matches[1]] = strtoupper($matches[2]);
                    } elseif (preg_match('/^([a-z]{1}[a-z0-9_.]*)=(ASC|DESC)/i', $orderPart, $matches)) {
                        $matchParts = explode('.', $matches[1]);
                        $pointer = &$orderBy;
                        foreach ($matchParts as $part) {
                            if (!isset($pointer[$part])) {
                                $pointer[$part] = array();
                            }
                            $pointer = &$pointer[$part];
                        }
                        $pointer = strtoupper($matches[2]);
                    }
                }
            }

            if (
                null !== ($parentCollection = $routeMatch->getParam('parent_collection')) &&
                null !== ($parentId = $routeMatch->getParam('parent_id'))
            ) {
                $parent = array(
                    'collection' => $parentCollection,
                    'id'         => $parentId,
                );
            }

            $this->getResponse()->getHeaders()->addHeaderLine(
                'Vary',
                implode(',', $varyOptions)
            );

            if (!$this->verifyAuthentication($routeMatch)) {
                return $this->getResponse();
            }

            switch (strtolower($request->getMethod())) {
                case 'get':
                    if (null !== $requestId) {
                        $action = 'get';
                        $return = $this->get($requestId, $parent);
                        $relations = $this->getRelations();
                        foreach ($relations as $relation) {
                            $return[$relation] = array(
                                'rel'  => 'collection/' . $relation,
                                'link' => $_SERVER['REQUEST_URI'] . '/' . $relation
                            );
                        }
                        break;
                    }
                    $action = 'getList';
                    $return = $this->getList($range, $filter, $orderBy, $parent);
                    break;
                case 'post':
                    $action = 'create';
                    $return = $this->processPostData($request);
                    break;
                case 'put':
                    $action = 'update';
                    $return = $this->processPutData($request, $routeMatch);
                    break;
                case 'patch':
                    $action = 'patch';
                    $return = $this->processPatchData($request, $routeMatch);
                    break;
                case 'delete':
                    if (null === $requestId) {
                        throw new Exception\DomainException('Missing identifier');
                    }
                    $action = 'delete';
                    $return = $this->delete($requestId, $parent);
                    break;
                case 'options':
                    if (null !== $requestId) {
                        $return = $this->getOptions($requestId, $parent);
                        break;
                    }
                    $return = $this->getOptions(null, $parent);
                    break;
                default:
                    throw new Exception\DomainException('Invalid HTTP method!');
            }

            $routeMatch->setParam('action', $action);
        }

        $allowedOrigin = isset($_SERVER['HTTP_HOST']) ?
            str_replace('api.', '', $_SERVER['HTTP_HOST']) : '*';

        if (($originHeader = $this->getRequest()->getHeader('Origin'))) {
            $allowedOrigin = $originHeader->getFieldValue();
        }

        $this->getResponse()->getHeaders()->addHeaderLine(
            'Access-Control-Allow-Credentials', 'true'
        );
        $this->getResponse()->getHeaders()->addHeaderLine(
            'Access-Control-Allow-Origin', $allowedOrigin
        );
        $this->getResponse()->getHeaders()->addHeaderLine(
            'Access-Control-Allow-Methods',
            'GET, POST, PUT, PATCH, HEAD, TRACE, OPTIONS'
        );
        $this->getResponse()->getHeaders()->addHeaderLine(
            'Access-Control-Expose-Headers',
            'WWW-Authenticate, Vary, Content-Range'
        );
        $this->getResponse()->getHeaders()->addHeaderLine(
            'Access-Control-Allow-Headers',
            'Accept, Authorization, Accept-Encoding, Accept-Language, Range, X-Browser-Digest, X-Filter-By, X-Order-By'
        );

        $viewModel = new \Zend\View\Model\JsonModel();

        if (empty($return) && $this->getResponse()->getStatusCode() != 401) {
            $this->getResponse()->setStatusCode(204);
        }

        $trMetaData = null;
        if ($this->getTunnelingEnabled()) {

            $trMetaData = $this->getTunnelledResponsemetaData(
                $this->getResponse()
            );

            $wwwAuthenticate = $this->getResponse()->getHeaders()->get('WWW-Authenticate');
            if ($wwwAuthenticate) {
                $this->getResponse()->getHeaders()->removeHeader($wwwAuthenticate[0]);
            }

        }
        if (empty($return)) {
            $this->getResponse()->setStatusCode(204);
            if (null !== $trMetaData) {
                $return = $trMetaData;
            } else {
                return $this->getResponse();
            }
        } else {
            if (null !== $trMetaData) {
                $return = array_merge($trMetaData, $return);
            }
            $accept         = $this->getRequest()->getHeader('accept')->getFieldValue();
            $acceptList     = array();
            $explodedAccept = explode(',', $accept);

            foreach($explodedAccept as $explode) {
                $tmp            = explode(';', $explode);
                $acceptMime     = array_shift($tmp);
                $acceptList[]   = trim($acceptMime);
            }

            if (false !== $accept && in_array('*/*', $acceptList) === false) {
                if (in_array('application/json', $acceptList) === false) {
                    $this->getResponse()->setStatusCode(406);
                    return $this->getResponse();
                    // TODO: Support more accept types
                }
            }
        }

        // jsonp workaround for */* header..
        if ($this->getRequest()->getQuery('callback') !== false) {
            // Enforce the callback to be called
            $viewModel->setJsonpCallback($this->getRequest()->getQuery('callback'));
        }

        // When tunneling for method, status and headers is enabled alwayw
        // return status 200 for the tunnel client to be able to get the
        // body.
        if ($this->getTunnelingEnabled()) {
            $this->getResponse()->setStatusCode(200);
        }
        $viewModel->setVariables($return);

        // Emit post-dispatch signal, passing:
        // - return from method, request, response
        // If a listener returns a response object, return it immediately
        $e->setResult($viewModel);


        return $viewModel;
    }

    protected function getTunnelledResponsemetaData(HTTPResponse $response)
    {
        return array(
            'tr-meta-data' => array(
                'headers' => $response->getHeaders()->toArray(),
                'status'  => $response->getStatusCode(),
            ),
        );
    }

    /**
     * Process post data and call create
     *
     * @param Request $request
     * @return mixed
     */
    public function processPostData(Request $request)
    {
        return $this->create($request->getPost()->toArray());
    }

    /**
     * Process put data and call replace
     *
     * @param Request $request
     * @param $routeMatch
     * @return mixed
     * @throws Exception\DomainException
     */
    public function processPutData(Request $request, $routeMatch)
    {
        if (null === $id = $routeMatch->getParam('id')) {
            if (!($id = $request->getQuery()->get('id', false))) {
                throw new Exception\DomainException('Missing identifier');
            }
        }
        $content = $request->getContent();
        parse_str($content, $parsedParams);

        return $this->update($id, $parsedParams);
    }

    /**
     * Process patch data and call update
     *
     * @param Request $request
     * @param $routeMatch
     * @return mixed
     * @throws Exception\DomainException
     */
    public function processPatchData(Request $request, $routeMatch)
    {
        if (null === $id = $routeMatch->getParam('id')) {
            if (!($id = $request->getQuery()->get('id', false))) {
                throw new Exception\DomainException('Missing identifier');
            }
        }
        $content = $request->getContent();
        parse_str($content, $parsedParams);

        return $this->update($id, $parsedParams);
    }

}

