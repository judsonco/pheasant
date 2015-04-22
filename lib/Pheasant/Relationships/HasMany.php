<?php

namespace Pheasant\Relationships;

use \Pheasant\Collection;
use \Pheasant\Relationship;

/**
 * A HasMany relationship represents a 1 to N relationship.
 */
class HasMany extends Relationship
{
    /**
     * Constructor
     */
    public function __construct($class, $local=null, $foreign=null)
    {
        parent::__construct($class, $local, $foreign);
    }

    /* (non-phpdoc)
     * @see Relationship::get()
     */
    public function get($object, $key, $cache=null)
    {

        return $this->collectionFor($object, $key);
    }

    /* (non-phpdoc)
     * @see Relationship::add()
     */
    public function add($object, $value)
    {
        $savedAfter = false;
        array_map(
            function ($local, $foreign) use (&$object, &$value) {
                $newValue = $object->{$local};

                if($newValue instanceof PropertyReference && !$savedAfter){
                    $savedAfter = true;
                    $value->saveAfter($object);
                }

                $value->set($foreign, $newValue);
            },
            $this->local,
            $this->foreign
        );
    }
}
