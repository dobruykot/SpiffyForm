<?php
namespace SpiffyForm\Form\Element;
use Closure,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository,
    Zend\Form\Element\Multi;

class Entity extends Multi
{
    protected $_expanded = false;
    
    protected $_multiple = false;
    
    protected $_class;
    
    protected $_em;
    
    protected $_qb;
    
    protected $_property;
    
    protected $_emptyValue;
    
    protected $helpers = array(
        'default'          => 'formSelect',
        'multiple'         => 'formSelect',
        'expanded'         => 'formRadio',
        'multipleExpanded' => 'formMultiCheckbox',
    );
    
    public function setMultiple($multiple)
    {
        $this->_multiple = $multiple;
    }
    
    public function setExpanded($expanded)
    {
        $this->_expanded = $expanded;
    }
    
    public function setEmptyValue($emptyValue)
    {
        $this->_emptyValue = $emptyValue;
    }
    
    public function setProperty($property)
    {
        $this->_property = $property;
    }
    
    public function setClass($class)
    {
        $this->_class = $class;
    }
    
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->_em = $entityManager;
    }
    
    public function setQueryBuilder($queryBuilder)
    {
        if (!is_callable($queryBuilder)) {
            throw new \InvalidArgumentException('query builder must be callable');
        }
        
        $this->_qb = $queryBuilder;
    }
    
    public function init()
    {
        if ($this->_multiple && $this->_expanded) {
            $this->helper = $this->helpers['multipleExpanded'];
        } else if ($this->_multiple) {
            $this->helper = $this->helpers['multiple'];
        } else if ($this->_expanded) {
            $this->helper = $this->helpers['expanded'];
        } else {
            $this->helper = $this->helpers['default'];
        }
        
        $this->validateParams();
        $this->load();
    }
    
    protected function validateParams()
    {
        if (null === $this->_em) {
            throw new \InvalidArgumentException('no entity manager was set');
        }
        if (null === $this->_class) {
            throw new \InvalidArgumentException('no class was set');
        }
    }
    
    protected function load()
    {
        $em         = $this->_em;
        $qb         = $this->_qb;
        $class      = $this->_class;
        $property   = $this->_property;
        $mdata      = $em->getClassMetadata($class);
        $identifier = $mdata->getIdentifierFieldNames();
        
        if ($qb) {
            $entities = $qb->getQuery()->execute();
        } else {
            $entities = $em->getRepository($class)->findAll();
        }
        
        // empty value?
        if ($this->_emptyValue && !$this->_multiple && !$this->_expanded) {
            $this->options[null] = $this->_emptyValue;
        }
        
        foreach($entities as $key => $entity) {
            if ($property) {
                if (!isset($mdata->reflFields[$property])) {
                    throw new \RuntimeException(sprintf(
                        'property "%s" could not be found in entity "%s"',
                        $property,
                        $class
                    ));
                }
                $reflProp = $mdata->reflFields[$property];
                
                $value = $reflProp->getValue($entity);
            } else {
                if (!method_exists($entity, '__toString')) {
                    throw new \RuntimeException(
                        'entities must have a "__toString()" method defined if you have not set ' . 
                        'a "property" option.'
                    );
                }
                $value = (string) $entity;
            }
            
            if (count($identifier) > 1) {
                $id = $key;
            } else {
                $id = current($mdata->getIdentifierValues($entity));
            }
            
            $this->options[$id] = $value;
        }
    }
}
