# SpiffyForm module for Zend Framework 2 examples

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

## Building an automatic form from a Doctrine entity
Building a form using a Doctrine entity is identical to using an annotated object but the information
is read from @ORM\Columns so you do not need to specify @Form\Element.

        $manager = new \SpiffyForm\Form\Manager('My\Doctrine\Entity');
        return array('form' => $manager->build()->getForm());    

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