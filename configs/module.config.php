<?php
return array(
	'di' => array(
		'instance' => array(
			'alias' => array(
				'spiffy_form'          => 'SpiffyForm\Form\Manager',
				'spiffy_form_doctrine' => 'SpiffyForm\Form\ManagerDoctrine'
			),
			'spiffy_form' => array(
                'parameters' => array(
                    'reader' => 'spiffy_annotation_cached_reader'
                )
            ),
            'spiffy_form_doctrine' => array(
                'parameters' => array(
                    'reader' => 'spiffy_annotation_cached_reader',
                    'em'     => 'doctrine_em'
                )
            )
		)
	)
);