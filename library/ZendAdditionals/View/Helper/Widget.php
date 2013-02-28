<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceManager;
use Zend\View\Exception;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Model\ViewModel;

/**
*   @author     Dennis Duwel <dennis@ratus.nl>
*   @package    ZendAdditionals
*   @category   ZendAdditionals
*   @subpackage View\Helper
*
*   @todo build in cache
*   @todo merge recursive
*/
abstract class Widget extends AbstractHelper
{
    /** @var ViewModel $viewModel */
    protected $viewModel;

    /** @var string $name */
    protected $name;

    /** @var string $type */
    protected $type;

    /** @var array $config */
    protected $config;

    /** @var array $data */
    protected $data = array();

    /** @var string */
    protected $defaultskey = 'defaults';

    /** @var string */
    protected $widgetskey  = 'widgets';

    /** @var ServiceManager */
    protected $serviceManager;

    /** 
     * @var array 
     */
    protected $widgetConfig;

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param ServiceManager $serviceManager
     * @return Widget
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    /**
     * Return the config of the widget
     *
     * @return array | null
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $name The name of the widget
     * @return Widget
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the name of the widget
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $type
     * @return Widget
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the data that will be used in the widget
     *
     * @param array $data
     * @return Widget
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getWidgetConfig() {
        return $this->widgetConfig;
    }

    /**
     * @param  array $widgetConfig
     * @return Widget
     */
    public function setWidgetConfig(array $widgetConfig) {
        $this->widgetConfig = $widgetConfig;
        return $this;
    }

    
    /**
     * @return ViewModel
     */
    public function getViewModel()
    {
        if ($this->viewModel === null) {
            $this->viewModel = new ViewModel();
        }

        return $this->viewModel;
    }

    /**
     * Initialize the data that can be used in the widget
     *
     * @return void
     */
    abstract protected function init();

    /**
    * Invoke, function called to create the viewhelper
    *
    * @return string The rendered HTML
    */
    public function __invoke($name = null, $default = '')
    {

        $this->setName($name);

        // Get the custom config
        $this->initWidgetConfig();

        // Initialize the widget
        $this->init();

        // Render the widget
        return $this->render();
    }

    /**
    * Set the rendertemplate for the widget
    *
    * @param String $file filename corresponding to the name given in the viewmanager
    * @throws \Exception 'File is empty'
    */
    public function setTemplate($file)
    {
        if ( empty( $file ) ) {
            throw new Exception\InvalidArgumentException("File is empty");
        }

        $this->getViewModel()->setTemplate($file);
    }

    /**
    * Get the config for this viewhelper (widget).
    * Merges the default with the widgetname.
    * Sets the $this->config with the default and widgetname config merged
    *
    * @throws Exception\RuntimeException
    */
    protected function initWidgetConfig()
    {
        $widgetType       = $this->getType();
        $configIdentifier = $this->getName();

        $config = $this->getWidgetConfig();
        
        if (!is_array($config)) {
            throw new Exception\RuntimeException(
                'Widget configuration not set for widget: ' . $widgetType
            );
        }

        //Get the defaults of the widget
        if (array_key_exists($this->defaultskey, $config)) {
            $defaults = $config[$this->defaultskey];
        } else {
            throw new Exception\RuntimeException(
                "Element '{$this->defaultskey}' was not" . 
                "found for the widgettype '{$widgetType}'"
            );
        }

        // Get the specific values for this widget
        if (array_key_exists($configIdentifier, $config)) {
            $widget = $config[$configIdentifier];
        } else {
            $widget = array();
        }

        if( empty($defaults) ) {
            return false;
        }

        // Update the defaults with the widgetconfig
        $this->mergeRecursive($defaults, $widget);

        $this->config = $defaults;
    }

    /**
    * Sets the data to the viewmodel and renders
    *
    * @throws Exception\RuntimeException
    * @return string the rendered view
    */
    private function render()
    {
        $view           = $this->getViewModel();
        $template       = $view->getTemplate();
        $configtemplate = $this->config['renderfile'];
        $config         = $this->getConfig();
        $data           = $this->getData();

        if ( empty($template) ) {
            if (!empty($configtemplate)) {
                $view->setTemplate($configtemplate);
            } else {
                throw new Exception\RuntimeException("no render template was set");
            }
        }

        if (empty($data) === false) {
           $view->setVariables($data);
        }

        if (empty($config) === false) {
            $view->setVariables($config);
        }

        $view->setVariable('type', $this->type);
        $view->setVariable('name', $this->name);

        $rendered = $this->getView()->render($view);

        return $rendered;
    }

    /**
     * Merge the settings recursive
     *
     * @param array $main
     * @param array $sub
     * @return void
     */
    private function mergeRecursive(array &$array1, array &$array2)
    {
        foreach( $array1 as $key => $value ) {
            if (is_array($value) && !empty($value)) {
                if (!isset($array2[$key])) {
                    $array2[$key] = array();
                }

                $this->mergeRecursive($array1[$key], $array2[$key]);
            } else if (isset($array2[$key])) {
                $array1[$key] = $array2[$key];
            }
        }
    }
}
