# Installing SpiffyForm module for Zend Framework 2
The simplest way to install is to clone the repository into your /modules directory add the 
SpiffyForm key to your modules array.

  1. Install [SpiffyAnnotation](http://www.github.com/SpiffyJr/SpiffyAnnotation) following the [instructions](https://github.com/SpiffyJr/SpiffyAnnotation/blob/master/docs/INSTALL.md) documented.
  2. cd my/project/folder
  3. git clone git://github.com/SpiffyJr/SpiffyForm.git modules/SpiffyForm --recursive
  4. open my/project/folder/configs/application.config.php and add 'SpiffyForm' to your 'modules' parameter.
  5. Optionally install [SpiffyDoctrine](http://www.github.com/SpiffyJr/SpiffyDoctrine) following the [instructions](https://github.com/SpiffyJr/SpiffyDoctrine/blob/master/docs/INSTALL.md) documented.
  
## Tuning for production
SpiffyForm utilizes the Doctrine annotation reader to gather information about properties. By default,
the reader uses an ArrayCache which is not suitable for production. To tune for production use
one of the other Doctrine cache's, most notably, the ApcCache.

In your application config:

    'di' => array(
        'instance' => array(
            'alias' => array(
                'spiffy_annotation_cache' => 'Doctrine\Common\Cache\ApcCache'
            ),
        )
    )
