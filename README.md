# SpiffyForm module for Zend Framework 2
The SpiffyForm module includes a Manager and Definition class to generate and help generate forms
from objects (and Doctrine entities if SpiffyDoctrine is installed). The following features are 
intended to work out of the box: 

  - Automatic form creation by passing an annotated object.
  - Automatic form creation by passing a Doctrine entity.
  - Manual form creation by passing a form definiton that specifies the form elements to build.
  - Building forms within forms (subforms) by including form defintions inside another form definiton.
  - Automatic binding of data to objects.
 
## Requirements
  - [Zend Framework 2](http://www.github.com/zendframework/zf2)
  - [SpiffyAnnotation](http://www.github.com/SpiffyJr/SpiffyAnnotation)
  - [SpiffyDoctrine](http://www.github.com/SpiffyJr/SpiffyDoctrine) (optional)
