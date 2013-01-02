<?php

namespace ZendAdditionals\Http\PostProcessor;

/**
 *
 */
abstract class AbstractPostProcessor
{
    /**
     * @var array
     */
    protected $variables;

    /**
     * @var \Zend\Http\Response
     */
    protected $response;

    public function setResponse(\Zend\Http\Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return \Zend\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function setVariables($variables)
    {
        $this->variables = $variables;
        return $this;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @abstract
     */
    abstract public function process();
}

