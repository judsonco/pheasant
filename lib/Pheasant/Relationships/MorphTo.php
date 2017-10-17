<?php

namespace Pheasant\Relationships;

use \Pheasant;
use \Pheasant\PropertyReference;
use \Pheasant\Relationship;

/**
 * A polymorphic BelongsTo relationship.
 */
class MorphTo extends Relationship
{
    public function __construct($class)
    {
        parent::__construct($class, null, null);
    }

    /* (non-phpdoc)
     * @see Relationship::get()
     */
    public function get($object, $key)
    {
        $local = $key . '_id';
        $foreignClass = $object->{$key . '_type'};

        return ($foreignClass)::byId($object->{$local});
    }

    /* (non-phpdoc)
     * @see Relationship::set()
     */
    public function set($object, $key, $value)
    {
        //
    }
}
