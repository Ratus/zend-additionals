Abstract Widget
======
The abstract widget is used to create widgets in a more easy way.


Usses
----------
The Abstract widget makes use of the [Custom Module](https://github.com/Ratus/Custom)


How to use
---------
1.  Create an class and extend the abstract widget (\ZendAdditionals\View\Helper\Widget)
2.  Create an viewhelper in you Module.php (add the function getViewHelperConfig() if needed)

    
        $viewhelperconfig = array(
            'factories' => array(
                'applicationsplash' => function ($sm) {
                    $viewHelper = new \Application\View\Helper\Widget(
                    'profiles_splash', $sm);
                    return $viewHelper;
                },
            ),
        );
        return $viewhelperconfig;

        * In this example 'applicationsplash' will be the function to call and 'profiles_splash' is the type of the widget
    

3.  Create the widget config. This needs to be done in the custom.config.php


        'custom' => array(
            'widgets' => array(
        		'profile_splash' => array(
    				'defaults'      => array(
                            'gender'    => 'v',
                            'age'       => '18-99',
                            'cols'      => 3,
                            'rows'      => 2,
                            'orderby'   => 'new',
                            'renderfile'=> 'widget/splashprofile',
                    ),
                    'recent_female' => array(
                            'orderby'   => 'recent',
                    ),
    			),
            ),
        ),

        * 'custom' is needed to get al the custom config
        * 'widget' has all the widget config in it
        * 'profile_splash' type of the widget (note: check the above example)
        * 'defaults' Each type of widgets has its own defaults
        * 'recent_female' is an specific config. it gets merged with the defaults.
        * 'renderfile' file to render the widget. Specified in the view_manager

4.  Call the widget


        <?php echo $this->applicationsplash(); ?>
        * calls the widget -> widget uses default values;

        <?php echo $this->applicationsplash('recent_female); ?>
        * calls the widget -> uses the values of recent_female (merged with defaults)
    



