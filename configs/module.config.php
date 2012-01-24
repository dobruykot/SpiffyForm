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
                'spiffyform_file_cache' => 'Zend\Cache\StorageFactory',
                
                // builders
				'spiffyform_builder'       => 'SpiffyForm\Form\Builder\Standard',
				'spiffyform_builder_orm'   => 'SpiffyForm\Form\Builder\DoctrineORM',
				'spiffyform_builder_mongo' => 'SpiffyForm\Form\Builder\DoctrineMongoODM',
			),
			'spiffyform_builder' => array(
				'parameters' => array(
					'cache' => 'spiffyform_file_cache'
				)
			),
            'spiffyform_builder_orm' => array(
                'parameters' => array(
                    'em'    => 'doctrine_em',
                    'cache' => 'spiffyform_file_cache'
                )
            ),
            'spiffyform_builder_mongo' => array(
                'parameters' => array(
                    'dm'    => 'mongo_dm',
                    'cache' => 'spiffyform_file_cache'
                )
            ),
            'spiffyform_file_cache' => array(
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