<?php
namespace ZendAdditionals\View\Helper\Widget;

use Zend\View\Exception;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class AbstractWidget extends AbstractHelper implements 
    ServiceLocatorAwareInterface
{
    use \ZendAdditionals\Config\ConfigExtensionTrait;
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;
    
    /** @var ViewModel $viewModel */
    protected $viewModel;


    /** @var array $data */
    protected $data = array();

    /** @var string */
    protected $defaultskey = 'defaults';

    /** @var string */
    protected $widgetskey  = 'widgets';

    /** 
     * @var array 
     */
    protected $config;
    
    /**
     * @var array
     */
    protected $widgetConfig;
    
    /**
     * @var string
     */
    protected $configIdentifier;

    /**
     * Initialize the data that can be used in the widget
     *
     * @return void
     */
    abstract protected function prepare();

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data that will be used in the widget
     *
     * @param array $data
     * @return MyWidget
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param  array $config
     * @return MyWidget
     */
    public function setConfig(array $config) {
        $this->config = $config;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getConfigIdentifier() {
        return $this->configIdentifier;
    }
    
    /**
     * @param  string $configIdentifier
     * @return MyWidget
     */
    public function setConfigIdentifier($configIdentifier) {
        $this->configIdentifier = $configIdentifier;
        return $this;
    }

    /**
    * Invoke, function called to create the viewhelper
    *
    * @return string The rendered HTML
    */
    public function __invoke(
        $configIdentifier = 'default', 
        array $parameters = null
    ) {
        // Set the configuration identifier
        $this->setConfigIdentifier($configIdentifier);
        
        // Hydrate any given parameter
        $hydrator = new \Zend\Stdlib\Hydrator\ClassMethods();
        if (null !== $parameters) {
            $hydrator->hydrate($parameters, $this);
        }
        
        // Initialize widget config
        $this->initConfig();
        
        // Prepare MyWidget
        $this->prepare();
        
        // Render MyWidget
        return $this->render();
    }

    /**
     * Get the config for this viewhelper (widget).
     * Merges the default with the config identified by config identifier.
     * Sets the $this->config with the default and identified config merged
     *
     * @throws Exception\RuntimeException
     */
    protected function initConfig()
    {
        $configIdentifier = $this->getConfigIdentifier();
        
        $config = $this->getConfig();
        
        if (!is_array($config)) {
            throw new Exception\RuntimeException(
                'Widget configuration not set!'
            );
        }
        
        //Get the defaults of the widget
        if (array_key_exists('default', $config)) {
            $defaultConfig = $config['default'];
        } else {
            throw new Exception\RuntimeException(
                "Default config for widget not set!"
            );
        }

        // Get the specific values for this widget
        if (array_key_exists($configIdentifier, $config)) {
            $identifiedConfig = $config[$configIdentifier];
        } else {
            $identifiedConfig = array();
        }

        if (empty($defaultConfig)) {
            return false;
        }
        $this->widgetConfig = \ZendAdditionals\Stdlib\ArrayUtils::mergeDistinct(
            $defaultConfig, 
            $identifiedConfig
        );
    }
    
    /**
     * Overrides the widget config template
     * 
     * @param string $template
     * 
     * return MyWidget
     */
    protected function setTemplate($template)
    {
        $this->widgetConfig['template'] = $template;
        return $this;
    }

    /**
    * Sets the data to the viewmodel and renders
    *
    * @throws Exception\RuntimeException
    * @return string the rendered view
    */
    protected function render()
    {
        $viewModel    = new ViewModel;
        $widgetConfig = $this->widgetConfig;
        $data         = $this->getData();
        
        if (!isset($widgetConfig['template'])) {
            throw new Exception\RuntimeException(
                'no rendering template was set'
            );
        }
        
        $viewModel->setTemplate($widgetConfig['template']);

        if (!empty($data)) {
           $viewModel->setVariables($data);
        }

        return $this->getView()->render($viewModel);
    }
}
