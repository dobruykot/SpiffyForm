<?php
return array(
	'di' => array(
		'instance' => array(
			'alias' => array(
				'spiffy_form'          => 'SpiffyForm\Form\Manager',
				'spiffy_form_doctrine' => 'SpiffyForm\Form\ManagerDoctrine'
			),
            'spiffy_form_doctrine' => array(
                'parameters' => array(
                    'em' => 'doctrine_em'
                )
            )
		),
	)
);