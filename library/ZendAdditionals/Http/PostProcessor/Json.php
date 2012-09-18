<?php

namespace ZendAdditionals\Http\PostProcessor;

/**
 *
 */
class Json extends AbstractPostProcessor
{
	public function process()
	{
		$result = \Zend\Json\Encoder::encode($this->getVariables());

		$this->getResponse()->setContent($result);

		$headers = $this->getResponse()->getHeaders();
		$headers->addHeaderLine('Content-Type', 'application/json');
		$this->getResponse()->setHeaders($headers);
	}
}

