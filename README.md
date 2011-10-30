# SpiffyForm module for Zend Framework 2
The SpiffyForm module includes a FormBuilder and Form class to generate and help generate forms
from objects (and Doctrine entities if SpiffyDoctrine is installed). The following features are 
intended to work out of the box: 

  - Annotation driven form elements, either via @Form\Element or @ORM\Column.
  - Annotation driven validators and filters provided by SpiffyAnnotation.
 
## Requirements
  - Zend Framework 2
  - SpiffyAnnotation (http://www.github.com/SpiffyJr/SpiffyAnnotation)
 
## Installation
The simplest way to install is to clone the repository into your /modules directory add the 
SpiffyForm key to your modules array.

  1. cd my/project/folder
  2. git clone https://SpiffyJr@github.com/SpiffyJr/SpiffyForm.git modules/SpiffyForm --recursive
  3. open my/project/folder/configs/application.config.php and add 'SpiffyForm' to your 'modules' parameter.
  
## Usage
### Sample test object
Assume the following class:
    <?php
    namespace Application\Test;
    use SpiffyAnnotation\Assert,
        SpiffyAnnotation\Form,
        SpiffyAnnotation\Filter;
        
    class Object 
    {
        /**
         * @Form\Element(type="text",options={"label"="Email"})
         * @Filter\StringTrim
         * @Assert\EmailAddress
         */
        protected $email;
    }
    
### Standard object use case with form builder
You can generate a form using the builder with:
    $form = new \SpiffyForm\Form\Builder(array('dataObject' => 'Application\Test\Object'));
    
On form validation, you can retrieve the object (populated with values) by using $form->getDataObject().
    
### Building forms manually with AbstractForm
In some cases you may want to generate a form from an object and not include all the properties as
form elements.

    <?php
    namespace Application\Test;
    use SpiffyForm\Form\AbstractForm;
    
    class ManualForm extends AbstractForm
    {
        public function init()
        {
            // all examples will use filters/validators from annotations
            $this->add('email'); // adds email from object using type guesser
            $this->add('email', 'text') // adds email from object and forces text type
            $this->add('email', 'text', array(...)); // specify options using an array() as last argument 
        }
        
        public function getDefaultOptions()
        {
            return array('data_class' => 'Application\Test\Object');
        }
    }

### Building forms from Doctrine entities
Coming soon...