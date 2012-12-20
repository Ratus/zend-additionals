<?php

namespace ZendAdditionals\Mvc\Controller;

use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\Between;
use Zend\Db\Sql\Predicate\Like;
use Zend\Db\Sql\Predicate\In;
use Zend\Http\Request as HTTPRequest;
use Zend\Http\Response as HTTPResponse;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Mvc\Exception;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Stdlib\Hydrator\ClassMethods;
use Zend\Validator;

use ZendAdditionals\Exception\NotImplementedException;
use ZendAdditionals\Db\Sql\Predicate\NotBetween;
use ZendAdditionals\Db\Sql\Predicate\NotLike;
use ZendAdditionals\Db\Sql\Predicate\NotIn;

/**
 * Abstract RESTful controller
 *
 * @category   ZendAdditionals
 * @package    ZendAdditionals_Mvc
 * @subpackage Controller
 */
abstract class AbstractRestfulController extends AbstractController
{
    use TraitAuthenticationController;

    const REQUEST_GET       = 'GET',
          REQUEST_POST      = 'POST',
          REQUEST_HEAD      = 'HEAD',
          REQUEST_OPTIONS   = 'OPTIONS',
          REQUEST_PATCH     = 'PATCH',
          REQUEST_PUT       = 'PUT',
          REQUEST_TRACE     = 'TRACE';

    const VALIDATOR_DATE         = 'date',
          VALIDATOR_DATETIME     = 'datetime',
          VALIDATOR_DATE_CUSTOM  = 'date_custom',
          VALIDATOR_EMAIL        = 'email';

    /**
     * The options that can be used. GET, POST, HEAD etc.
     *
     * @var array
     */
    protected $options = array();

    /**
     * @var boolean
     */
    protected $enableTunneling = false;

    /**
     * @var string
     */
    protected $eventIdentifier = __CLASS__;

    protected $parameterFieldsExcludedFromOptionRequest = array(
        'foreign_key' => '',
    );
    
    public function __construct()
    {
        $this->mergeParametersWithOptions();
    }

    /**
     * Return available HTTP methods and other options
     *
     * @return mixed
     */
    public function getOptions($id = null)
    {
        $response       = $this->getResponse();

        // Default request types for Collection and Resource
        $allowedRequestTypes  = array(
            self::REQUEST_GET,
            self::REQUEST_HEAD,
            self::REQUEST_OPTIONS,
        );

        if (empty($id)) { // Collection has POST possibility
            $response->getHeaders()->addHeaderLine('Accept-Ranges', 'resources');
            $allowedRequestTypes[] = self::REQUEST_POST;
        } else { // Resource has PATCH possibility
            $allowedRequestTypes[] = self::REQUEST_PATCH;
        }

        // Set the allowed request types
        $response->getHeaders()->addHeaderLine('Allow', implode(',', $allowedRequestTypes));

        $return = $this->buildOptionsList($allowedRequestTypes);

        if (!empty($return)) {
            return $return;
        }
    }

    /**
     * Build the options to be returned to the client
     *
     * @param array $allowedRequestTypes array('POST', 'GET', 'HEAD')
     * @return array
     */
    protected function buildOptionsList(array $allowedRequestTypes)
    {
        $return = array();

        foreach($allowedRequestTypes as $requestType) {
            if (isset($this->options[$requestType])) {
                $return[$requestType] = $this->options[$requestType];

                // Inject forward controller parameters
                if (isset($return[$requestType]['controller_forward'])) {
                    foreach($return[$requestType]['controller_forward'] as $controller) {
                         $return[$requestType]['parameters'] = array_merge(
                            $return[$requestType]['parameters'], $controller['parameters']
                        );
                    }
                }

                // Strip off the keys which shouldnt be visible for the client
                foreach($return[$requestType]['parameters'] as $key => &$parameter) {
                    if (isset($parameter['internal_usage']) && $parameter['internal_usage'] === true) {
                        unset($return[$requestType]['parameters'][$key]);
                        continue;
                    }

                    $parameter = array_diff_key(
                        $parameter,
                        $this->parameterFieldsExcludedFromOptionRequest
                    );
                }

                // Unset the controller_forward information
                if (isset($return[$requestType]['controller_forward'])) {
                    unset($return[$requestType]['controller_forward']);
                }
            }
        }

        return $return;
    }

    /**
     * Get the parameters based on the request method
     *
     * @return array
     */
    public function getAvailableParameters()
    {
        $method = $this->getRequest()->getMethod();
        
        if (empty($this->options[$method]['parameters'])) {
            return array();
        }

        return $this->options[$method]['parameters'];
    }

    /**
     * @return \Zend\Http\PhpEnvironment\Response
     */
    public function getResponse()
    {
        return parent::getResponse();
    }

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
            return $results[0];
        }
    }

    protected function entityToArray($entity)
    {
        $hydrator = $this->getMapper()->getHydrator();
        $return = $hydrator->extractRecursive($entity);
        // TODO: filter return
        return $return;
    }
    
    protected function validateParameters(&$data)
    {
        $parameters = $this->getAvailableParameters();
        
//        var_dump(get_called_class(), $parameters);
        
        if (empty($parameters)) {
            $this->getResponse()->setStatusCode(412); // Precondition Failed
            return;
        }

        $eventContainer = new \ZendAdditionals\Db\Entity\EventContainer();
        $eventContainer->setData($data);

        $class = get_called_class();
        $method = $this->getRequest()->getMethod();

        // Trigger event on controller::requestMethod
        $this->getEventManager()->trigger("{$class}::{$method}", $this, $eventContainer);

        $errors = array();

        if (isset($this->options[$method]['controller_forward'])) {
            $result = $this->processControllerForwardsForCreate(
                $data,
                $this->options[$method]['controller_forward']
            );

            // Return when controller_forwards has failed
            if ($this->getResponse()->getStatusCode() !== 200) {
                return $result;
            }
        }

        // Start validating parameters given
        foreach($parameters as $field => $parameter) {
            //array_key_exists returns true when a key is set, but the value is null
//            $isSet = array_key_exists($field, $data);
            $isSet = isset($data[$field]);

            // Set Default value when not set and default is available
            if ($isSet === false && isset($parameter['default'])) {
                $data[$field] = $parameter['default'];
            }

            // Validate if the parameter is required and required
            if ($parameter['required'] && $isSet === false) {
                $errors[] = array('error_message'   => "Field {$field} is required!");
                continue;
            }

            // Validate min length if given
            if ($isSet && isset($parameter['minlen']) && strlen($data[$field]) < $parameter['minlen']) {
                $errors[] = array(
                    'error_message' => "Field {$field} minimal length is {$parameter['minlen']}",
                );
                continue;
            }

            // Validate max length if given
            if ($isSet && isset($parameter['maxlen']) && strlen($data[$field]) > $parameter['maxlen']) {
                $errors[] = array(
                    'error_message' => "Field {$field} maximum length is {$parameter['maxlen']}",
                );
                continue;
            }

            // Check if the record match the regex if given
            if ($isSet && isset($parameter['regex'])) {
                $regex = str_replace('/', '\\/', $parameter['regex']);
                if (!preg_match("/{$regex}/", $data[$field])) {
                    $errors[] = array(
                        'error_message' => "Field {$field} does not match {$parameter['regex']}",
                    );
                    continue;
                }
            }

            if ($isSet && isset($parameter['validator'])) {
                // Support arrays if the validator needs extra parameters.
                if ($parameter['validator'] === (array) $parameter['validator']) {
                    $extraParams = array_pop($parameter['validator']);
                    $type        = array_pop($parameter['validator']);
                } else {
                    $type        = $parameter['validator'];
                    $extraParams = array();
                }

                switch($type) {
                    case self::VALIDATOR_DATE:
                        $validator = new Validator\Date(array('format' => 'Y-m-d'));
                        break;
                    case self::VALIDATOR_DATETIME:
                        $validator = new Validator\Date(array('format' => 'Y-m-d H:i:s'));
                        break;
                    case self::VALIDATOR_DATE_CUSTOM:
                        $validator = new Validator\Date($extraParams);
                        break;
                    case self::VALIDATOR_EMAIL:
                        $validator = new Validator\EmailAddress();
                        break;
                    default:
                        throw new \RuntimeException("Validator {$type} is not implemented");
                        break;
                }

                if ($validator->isValid($data[$field]) === false) {
                    $errors[] = array(
                        'error_message' => "Value {$data[$field]} does not match against validator {$type}",
                        'errors' => $validator->getMessages(),
                    );
                    continue;
                }
            }

            if (
                $isSet &&
                isset($parameter['foreign_key']) &&
                empty($errors) // Only do DB interaction when no errors has been occured
            ) {
                $relation = $this->getMapper()->getRelation(
                    $parameter['foreign_key']['mapper'],
                    $parameter['foreign_key']['identifier']
                );

                $mapper = $this->getServiceLocator()->get($parameter['foreign_key']['mapper']);
                if ($mapper->exists($relation['reference'][$field], $data[$field]) === false) {
                    $errors[] = array(
                        'error_message' => "Cannot find foreign record {$field}={$data[$field]}",
                    );
                }
            }

            // Check database if the record already exists
            if (
                $isSet &&
                isset($parameter['unique']) &&
                $parameter['unique'] === true &&
                empty($errors) && // Only do DB interaction when no errors has been occured
                $this->getMapper()->exists($field, $data[$field])
            ) {
                $errors[] = array(
                    'error_message' => "Value {$data[$field]} does already exists",
                );
            }
        }

        if (empty($errors) === false) {
            $this->getResponse()->setStatusCode(412);
            return $errors;
        }
        return true;
    }

    /**
     * Create a new resource
     *
     * @param  array $data
     * @return object|boolean Entity on success | FALSE on failure
     */
    public function create($data)
    {
        $result = $this->validateParameters($data);
        if ($result !== true) {
            return $result;
        }

        $entity     = clone $this->getMapper()->getEntityPrototype();
        $hydrator   = new ClassMethods();
        $hydrator->hydrate($data, $entity);

        try {
            if($this->getMapper()->save($entity) === false) {
                return false;
            }
        } Catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e) {
            $this->getResponse()->setStatusCode(500);
            $errors[] = array(
                'error_message' => $e->getMessage(),
                'sql_message' => $e->getPrevious()->getMessage(),
            );
            return $errors;
        }

        return $hydrator->extract($entity);
    }

    /**
     * Call all the controllers and store the needed data in the data array
     *
     * @param array $data The main data
     * @param array $controllers The forward controllers
     * @return array|boolean array on errors | TRUE on success
     */
    protected function processControllerForwardsForCreate(array &$data, array $controllers)
    {
        foreach ($controllers as $serviceName => $info) {
            // Get data that should be forwarded
            $dataForController  = array_intersect_key($data, $info['parameters']);
            
            // Check if parameters are not only internal
            $onlyInternal = true;
            foreach($dataForController as $field => $parameter) {
                if (empty($info['parameters'][$field]['internal_usage'])) {
                    $onlyInternal = false;
                }
            }
            // If only internal parameters are set, there is no use to forward
            if ($onlyInternal === true) {
                continue;
            }
            
            // Empty data is no use to forward
            if (empty($dataForController)) {
                continue;
            }

            // Strip data from original array
            $data           = array_diff_key($data, $dataForController);
            $controller     = $this->getController($serviceName);
            $result         = $controller->create($dataForController, true);

            /**
             * @TODO create transaction layer for main create.
             *       When one of the controller_forwards fails all the
             *      current inserts/update should be rolled back
             */
            if ($this->getResponse()->getStatusCode() !== 200) {
                return $result;
            }

            foreach ($info['replacements'] as $foreignParam => $parentParam) {
                if (isset($result[$foreignParam]) === false) {
                    $data[$parentParam] = null;
                    continue;
                }

                $data[$parentParam] = $result[$foreignParam];
            }
        }

        return true;
    }

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    public function update($id, $data)
    {
/*/
        $result = $this->validateParameters($data);
        if ($result !== true) {
            return $result;
        }

        $mapper = $this->getServiceLocator()->get(\DatingProfile\Mapper\Registrations::SERVICE_NAME);
        $entity = $mapper->getEntityPrototype();
        $mapper->hydrator->hydrate($data, $entity);
//        $entity     = clone $this->getMapper()->getEntityPrototype();
        try {
            if($mapper->save($entity) === false) {
                return false;
            }
        } Catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e) {
            $this->getResponse()->setStatusCode(500);
            $errors[] = array(
                'error_message' => $e->getMessage(),
                'sql_message' => $e->getPrevious()->getMessage(),
            );
            return $errors;
        }

        return $mapper->hydrator->extract($entity);

/*/
        $result = $this->validateParameters($data);
        if ($result !== true) {
            return $result;
        }
        
        
        $select = $this->getMapper()
            ->search(array(0, 1), array(
                'id' => $id,
            )
        );
        if (!isset($select[0])) {
             return false;
        }
        $entity = $select[0];
        $hydrator   = $this->getMapper()->getHydrator();
        $hydrator->hydrate($data, $entity);                      
        try {
            if($this->getMapper()->save($entity) === false) {
                return false;
            }
        } Catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e) {
            $this->getResponse()->setStatusCode(500);
            $errors[] = array(
                'error_message' => $e->getMessage(),
                'sql_message' => $e->getPrevious()->getMessage(),
            );
            return $errors;
        }

        return $hydrator->extract($entity);
    }

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
        $this->setRouteMatch($routeMatch);

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
            if ($this->verifyAuthentication($routeMatch)) {
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

            if (
                empty($return) &&
                $this->getResponse()->getStatusCode() < 400 &&
                $this->getRequest()->getMethod() != 'OPTIONS'
            ) {
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


            $return = $viewModel;
        }
        return $return;
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

    /**
     * Set the HTTP Request object to the controller
     *
     * @param HTTPRequest $request
     * @return self
     */
    public function setRequest(HTTPRequest $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the HTTP Response object to the controller
     *
     * @param HTTPResponse $response
     * @return self
     */
    public function setResponse(HTTPResponse $response)
    {
        $this->response = $response;
        return $this;
    }
    
    /**
    * This method will merge the parameters within the options
    * 
    * Usage:
    *   //Retrieve default parameter
    *   $options[$method]['parameters'][] = 'parameter_key'
    *   //Retrieve default parameter with exceptions in array
    *   $options[$method]['parameters']['parameter_key'] = array('required'=>true)
    */
    protected function mergeParametersWithOptions()
    {
        if (!isset($this->parameters) || !isset($this->options)) {
            return false;
        }
        foreach ($this->options as $method => $option) {
            foreach ($option['parameters'] as $parameterKey => $parameter) {
                if (!is_array($parameter) && !isset($this->parameters[$parameter])) {
                    if (!is_array($parameter)) {
                        return false;
                    }
                }
                if (!is_array($parameter)) {
                    unset($this->options[$method]['parameters'][$parameterKey]);
                    $this->options[$method]['parameters'][$parameter] = $this->parameters[$parameter];
                    continue;
                }
                $this->options[$method]['parameters'][$parameterKey] = $this->parameters[$parameterKey];
                foreach ($parameter as $key => $customParams) {
                    $this->options[$method]['parameters'][$parameterKey][$key] = $customParams;
                }
            }
        }
    }
}
