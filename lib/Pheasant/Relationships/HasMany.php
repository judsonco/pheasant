<?php

namespace Pheasant\Relationships;

use \Pheasant\Collection;
use \Pheasant\Relationship;
use \Pheasant\Identity;

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
        if ($cache) {
            $schema = \Pheasant::instance()->schema($this->class);
            $final = $this->finalForObject($object);
            $ids = array_map(
                function($v) use($object) {
                    return $object->{$v};
                },
                is_array($final->local) ? $final->local : array($final->local)
            );
            $identity = Identity::identityStringFromParams(
                $this->class,
                is_array($final->foreign)
                    ? array_combine($final->foreign, $ids)
                    : array($rel->foreign, current($ids))
            );

            if ($cached = $cache->get($identity)) {
                return $cached;
            }
        }

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
