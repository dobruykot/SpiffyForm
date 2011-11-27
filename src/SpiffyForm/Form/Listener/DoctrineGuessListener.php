<?php
namespace SpiffyForm\Form\Listener;
use Doctrine\ORM\Mapping,
    SpiffyForm\Form\Guess\Guess,
    Zend\EventManager\Event;

class DoctrineGuessListener implements Listener
{
    public function load(Event $e)
    {
        $guesses     = array();
        $annotations = $e->getParam('property')->getAnnotations();

        foreach($annotations as $annotation) {        
            if ($annotation instanceof Mapping\Column) {
                switch($annotation->type) {
                    case 'array':
                    case 'object':
                        ; // not supported
                        break;
                    case 'boolean':
                        $guesses[] = new Guess('checkbox', Guess::HIGH);
                        break;
                    case 'datetime':
                    case 'datetimetz':
                    case 'date':
                    case 'time':
                        $guesses[] = new Guess('text', Guess::LOW);
                        break;
                    case 'float':
                    case 'bigint':
                    case 'decimal':
                    case 'integer':
                    case 'smallint':
                        $guesses[] = new Guess('text', Guess::MEDIUM);
                        break;
                    case 'text':
                        $guesses[] = new Guess('textarea', Guess::MEDIUM);
                        break;
                    case 'string':
                        $guesses[] = new Guess('text', Guess::HIGH);
                        break;
                }
            }
    
            if ($annotation instanceof Mapping\ManyToOne) {
                $guesses[] = new Guess('entity', Guess::HIGH);
            }
        }

        return $guesses;
    }
}
