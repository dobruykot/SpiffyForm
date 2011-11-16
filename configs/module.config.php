<?php
return array(
	'di' => array(
		'instance' => array(
			'alias' => array(
				'spiffy_form_manager' => 'SpiffyForm\Form\Manager'
			),
			'spiffy_form_manager' => array(
                'parameters' => array(
                    'reader' => 'spiffy_annotation_cached_reader'
                )
            )
		)
	)
);