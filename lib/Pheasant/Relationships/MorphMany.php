<?php

namespace Pheasant\Relationships;

use \Pheasant;
use \Pheasant\Collection;
use \Pheasant\Database\Binder;
use \Pheasant\Relationship;
use \Pheasant\Identity;
use \Pheasant\PropertyReference;

/**
 * A polymorphic HasMany relationship.
 */
class MorphMany extends Relationship
{
    /**
     * Prefix for the keys by which to find related objects.
     * @var string
     */
    protected $prefix = '';

    /**
     * Constructor
     */
    public function __construct($class, $key_prefix)
    {
        $primary = array_keys(($class)::schema()
            ->primary())[0];

        $foreign = $key_prefix . '_id';

        parent::__construct($class, $primary, $foreign);

        $this->prefix = $key_prefix;
    }

    /* (non-phpdoc)
     * @see Relationship::get()
     */
    public function get($object, $key, $cache=null)
    {
        $idColumn = $this->prefix . '_id';
        $typeColumn = $this->prefix . '_type';

        $primary = array_keys($object->schema()
            ->primary())[0]
            ;

        $id = $object->{$primary};
        $type = get_class($object);

        $query = $this->queryFor($object, $key, [
            $idColumn => $id,
        ]);

        $query->andWhere($typeColumn . ' = ?', $type);

        return new Collection($this->class, $query);
    }

    /* (non-phpdoc)
     * @see Relationship::add()
     */
    public function add($object, $value)
    {
        $savedAfter = false;
        array_map(
            function ($local, $foreign) use ($object, $value, &$savedAfter) {
                $newValue = $object->{$local};

                if($newValue instanceof PropertyReference && !$savedAfter){
                    $savedAfter = true;
                    $object->saveAfter($value);
                }

                $value->set($foreign, $newValue);
            },
            $this->local,
            $this->foreign
        );
    }
}
