<?php
namespace ZendAdditionals\View\Helper;

use Zend\View\Model\ViewModel;
use Zend\View\Helper\AbstractHelper;
use Custom\View\Helper\Custom as CustomViewHelper;

/**
*   @author  Dennis Duwel <dennis@ratus.nl>
*   @version 0.1 <initial>
*   @since   29-10-2012
*   changed  29-10-2012
*
*   @uses Custom\View\Helper\Custom
*   @uses Zend\View\Model\ViewModel
*   @uses Zend\View\Helper\AbstractHelper
*
*   @todo build in cache
*   @todo merge recursive
*/
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
    protected $data = array();


    private $defaultskey = 'defaults';
    private $widgetskey  = 'widgets';
    private $sm;


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
        $this->sm   = $sm;

        // Create an viewmodel and set the config variables
        $this->vmodel = new ViewModel();
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
            error_log($e->getMessage());
        }

        // Initialize the widget
        $this->init();

        try {
            // Render the widget
            return $this->render();
        } catch (\Exception $e) {
            error_log($e->getMessage());
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
    *
    * @todo add cache
    */
    private function widgetConfig()
    {
        // Get and create the config from the custom
        $config   = $this->sm->getServiceLocator()->get('config');
        $custom   = !(empty($config['custom'])) ? $config['custom'] : array();
        parent::__construct($custom);

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

        // Update the defaults with the widgetconfig
        $this->mergeRecursive($defaults, $widget);
        //$this->checkParams($defaults, $config['musthaves']);

        $this->config = $defaults;
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

        if ( !empty($this->data) ) {
            $this->vmodel->setVariables($this->data);
        }

        if ( !empty($this->config) ) {
            $this->vmodel->setVariables($this->config);
        }
        $this->vmodel->setVariable('type', $this->type);
        $this->vmodel->setVariable('name', $this->name);

        $rendered = $this->getView()->render($this->vmodel);


        if (empty($rendered)) {
            error_log("Nothing renderd");
        }

        return $rendered;
    }

//    private function checkParams(&$params, $musthaves)
//    {
//        foreach( $params as $key => &$value ) {
//            if ( is_array($value) && !empty($value) ) {
//                $this->checkParams($value, $musthaves);
//            } else {
//                if ()
//            }
//        }
//    }

    private function mergeRecursive(&$main, &$sub)
    {
        foreach( $main as $key => $value ) {
            if (is_array($value) && !empty($value)) {
                $this->mergeRecursive($main[$key], $sub[$key]);
            } else if (isset($sub[$key])) {
                $main[$key] = $sub[$key];
            }
        }
    }
}