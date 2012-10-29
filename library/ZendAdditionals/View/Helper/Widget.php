<?php
namespace ZendAdditionals\View\Helper;

use Zend\View\Model\ViewModel;
use Zend\View\Helper\AbstractHelper;
use Custom\View\Helper\Custom as CustomViewHelper;


abstract class Widget extends CustomViewHelper
{
    /** @var ViewModel $vmodel */
    protected $vmodel;

    /** @var String $name */
    protected $name;

    /** @var String $type */
    protected $type;

    /** @var Array $config */
    protected $config;

    /** @var Array $data */
    protected $data;

    private $defaultskey = 'defaults';
    private $widgetskey  = 'widgets';


    /**
    * Constructor
    *
    * @param String         $type   type of the widget, corresponding to the custom config
    * @param ServiceManager $sm     Servicemanager
    *
    * @return Widget
    */
    public function __construct($type, $sm)
    {
        $this->type = $type;

        // Get and create the config from the custom
        $config   = $sm->getServiceLocator()->get('config');
        $custom   = !(empty($config['custom'])) ? $config['custom'] : array();
        parent::__construct($custom);

        // Create an viewmodel and set the config variables
        $this->vmodel = new ViewModel();

        return $this;
    }

    /**
    * Invoke, function called to create the viewhelper
    *
    */
    public function __invoke()
    {
        $args = func_get_args();
        // First arugment must alwasy be the name of the widget
        $this->name = array_shift($args);

        try {
            // Get the custom config
            $this->widgetConfig();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        // Initialize the widget
        $this->init();

        try {
            // Render the widget
            return $this->render();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
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
            throw new \Exception("File is empty");
        }

        $this->vmodel->setTemplate($file);
    }

    /**
    * Get the config for this viewhelper (widget).
    * Merges the default with the widgetname.
    * Sets the $this->config with the default and widgetname config merged
    *
    * @throws \Exception '[widgets] not found in config'
    * @throws \Exception '[defaults] not found in config'
    * @throws \Exception '[key] not found in config'
    */
    private function widgetConfig()
    {
        // The widgets config
        $config = $this->get($this->widgetskey);
        if ( empty($config) ) {
            throw new \Exception("Element 'widgets' was not found in the custom config or element is empty.");
        }

        // Get the config for the type widget
        if (array_key_exists($this->type, $config)) {
            $config = $config[$this->type];
        } else {
            throw new \Exception("Element '{$this->type}' was not found in ".
            "subarray of '{$this->widgetskey}' in the custom config.");
        }

        //Get the defaults of the widget
        if (array_key_exists($this->defaultskey, $config)) {
            $defaults = $config[$this->defaultskey];
        } else {
            throw new \Exception("Element '{$this->defaultskey}' was not"
            ."found for the widgettype '{$this->type}'");
        }

        // Get the specific values for this widget
        if (array_key_exists($this->name, $config)) {
            $widget = $config[$this->name];
        } else {
            $widget = array();
        }

        if( empty($defaults) ) {
            return false;
        }

        $this->config = array_merge($defaults, $widget);
    }

    /**
    * Sets the data to the viewmodel and renders
    *
    * @throws \Exception no template
    * @return String - Rendered Template on can render | FALSE on no render
    */
    private function render()
    {
        $template       = $this->vmodel->getTemplate();
        $configtemplate = $this->config['renderfile'];

        if ( empty($template) ) {
            if (!empty($configtemplate)) {
                $this->vmodel->setTemplate($configtemplate);
            } else {
                throw new \Exception("no render template was set");
            }
        }

        $this->vmodel->setVariables($this->data);

        $rendered = $this->getView()->render($this->vmodel);
        if (empty($rendered)) {
            return false;
        }

        return $rendered;
    }
}