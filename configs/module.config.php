<?php
return array(
	'di' => array(
        'definition' => array(
            'class' => array(
                'Zend\Cache\StorageFactory' => array(
                    'instantiator' => 'Zend\Cache\StorageFactory::factory',
                    'methods' => array(
                        'factory' => array(
                            'cfg'  => array('type' => false, 'required' => true),
                        )
                    )
                )
            )
        ),
		'instance' => array(
			'alias' => array(
                // cache
                'spiffy_form_file_cache' => 'Zend\Cache\StorageFactory',
                
                // builders
				'form_builder' => 'SpiffyForm\Form\Builder\Standard',
			),
            'spiffy_form_file_cache' => array(
                'parameters' => array(
                    'cfg' => array(
                        'adapter' => 'Filesystem',
                        'options' => array(/* adapter options */),
                        'plugins' => array('IgnoreUserAbort', 'Serializer'),
                        'options' => array(
                            'cacheDir' => 'data/cache',
                            'ttl'      => 100
                        )
                    )
                )
            ),
		),
	)
);