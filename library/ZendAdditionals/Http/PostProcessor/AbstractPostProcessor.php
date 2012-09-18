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
	protected $vars;

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

    public function setVars($vars)
    {
		$this->vars = $vars;
        return $this;
    }

    public function getVars()
    {
        return $this->vars;
    }

	/**
	 * @abstract
	 */
	abstract public function process();
}

