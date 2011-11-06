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
 
## Installation
The simplest way to install is to clone the repository into your /modules directory add the 
SpiffyForm key to your modules array.

  1. Install SpiffyAnnotation following the instructions documented.
  2. cd my/project/folder
  3. git clone https://SpiffyJr@github.com/SpiffyJr/SpiffyForm.git modules/SpiffyForm --recursive
  4. open my/project/folder/configs/application.config.php and add 'SpiffyForm' to your 'modules' parameter.
  
## Annotating your objects
In order for SpiffyForm to know what to do with your objects you must annotate them by adding
use SpiffyAnnotation\Form and adding @Form\Element(type="type") to the element. For example,

    <?php
    namespace My;
    use SpiffyAnnotation\Form;
    
    class Test
    {
        /**
         * @Form\Element(type="string")
         */
        public $string;
        
        /**
         * @Form\Element(type="boolean", options={"label"="My Boolean"})
         */
        public $boolean;
    }
    
Annotations require a "type" but "options" can be specified manually. Certain things, such as a label,
will be added for you if one is not given.

*Note: It is possible to build a form without annotations but you lose all automatic type guessing.*

## Building an automatic form from an annotated object
Using the object in "Annotation your objects" you can build a form automatically using the form 
manager. In your controller:

        $manager = new \SpiffyForm\Form\Manager('My\Test');
        return array('form' => $manager->build()->getForm());

This would build a form with a text fields, a checkbox, and a submit button.

## Customizing a form using a form definition
In some cases you may not want to build a form that matches an object entirely. Using a form definition
gives you the power to customize a form to your liking.

    <?php
    namespace My;
    use SpiffyForm\Form\Definition;
    
    class FormDefinition extends Definition
    {
        public function build(Manager $m)
        {
            $m->add('string')
              ->add('boolean')
              ->add('something', 'text') // no automatic type guessing because no property exists in My\Test
              ->add('thedate', 'date')   // no automatic type guessing because no property exists in My\Test
              ->add('submit');
        }
        
        public function getOptions()
        {
            return array('dataClass' => 'My\Test');
        }
    }
    
The "getOptions()" method is required and tells the form manager what data object to bind to this
form definition. The build method is passed the manager and you can use the add() method to add fields.
You could build the form from a controller by using:

    $manager = new \SpiffyForm\Form\Manager('My\FormDefinition');
    return array('form' => $manager->build()->getForm());
    
The manager is smart enough to realize that your using a form definition and will automatically generate
a new data object for you. The form built will contain two text fields, a checkbox, a date box, and
a submit element.

## Binding a populated object to a form
Edits are common among forms and the form manager allows you to bind populated data objects and will
set the defaults of your form for you. In your controller:

    $object = new \My\Test;
    $object->string = 'my string value';
    $object->boolean = true;

    // without a form definition    
    $manager = new \SpiffyForm\Form\Manager($object);
    $form = $manager->build()->getForm();
    
    // with a form definition
    $manager = new SpiffyForm\Form\Manager('My\FormDefinition', $dataObject);
    $form = $manager->build()->getForm();

