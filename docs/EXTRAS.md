# Extra goodies included with SpiffyForm
The items listed below are entirely optional.

## SpiffyForm\Form\Element\Entity
The entity element allows you to automatically include doctrine foreign entities in your form. To 
use the entity element simply add the field name in the form manager. 

Options available:
 - em, the entity manager to use. By default, this is set to doctrine_em from SpiffyDoctrine
 - class,  the entity class to use. This is assigned automatically by the form manager.
 - queryBuilder, a closure for a custom query to use, e.g. function(EntityRepository $er) { return $er->createQueryBuilder('q'); }
 - property, the property used to create the label. If this is not set then __toString() is used instead.
 - emptyValue, the empty value to display.
 - expanded, used with multiple to specify the element type.
 - multiple, used with expanded to specify the element type.

Multiple/Expanded types:
    Multiple Expanded Element
    -        -        Select (default)
    Yes      No       Multi-select
    No       Yes      Radio button
    Yes      Yes      Multi-checkbox
  