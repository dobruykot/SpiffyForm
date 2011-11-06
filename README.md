# SpiffyForm module for Zend Framework 2
The SpiffyForm module includes a FormBuilder and Form class to generate and help generate forms
from objects (and Doctrine entities if SpiffyDoctrine is installed). The following features are 
intended to work out of the box: 

  - Automatic form creation by passing an annotated object.
  - Automatic form creation by passing a Doctrine entity.
  - Manual form creation by passing a form type that specifies the elements to build.
  - Automatic binding of data to objects.
 
## Requirements
  - Zend Framework 2
  - SpiffyAnnotation (http://www.github.com/SpiffyJr/SpiffyAnnotation)