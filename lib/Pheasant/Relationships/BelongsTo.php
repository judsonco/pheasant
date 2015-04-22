<?php

namespace Pheasant\Relationships;

use \Pheasant\Relationship;

/**
 * A BelongsTo relationship represents the weak side of a 1->1 relationship. The
 * local entity has responsibility for the foreign key.
 *
 */
class BelongsTo extends Relationship
{
    private $_property;
    private $_allowEmpty;

    /**
     * Constructor
     *
     */
    public function __construct($class, $local, $foreign=null, $allowEmpty=false)
    {
        parent::__construct($class, $local, $foreign);
        $this->_allowEmpty = $allowEmpty;
    }

    /* (non-phpdoc)
     * @see Relationship::get()
     */
    public function get($object, $key, $cache=null)
    {
        if ($cache) {
            $schema = \Pheasant::instance()->schema($this->class);

            if ($cached = $cache->get(
                $schema->hash($object, array_map(
                  function ($l, $f) {
                      return array($l, $f);
                  },
                  $this->local,
                  $this->foreign
                ))
            )) {
                return $cached;
            }
        }

        $result = array();
        if($query = $this->queryFor($object, $key)){
            $result = $query->execute();
        }

        if(!count($result)) {
            if($this->_allowEmpty) {
                return null;
            } else {
                $foreign = '['.implode(',', $this->foreign).']';
                $local = '['.implode(',', $this->local).']';

                throw new \Pheasant\Exception("Failed to find a {$key} (via {$foreign}={$local}");
            }
        }

        return $this->hydrate($result->row());
    }

    /* (non-phpdoc)
     * @see Relationship::set()
     */
    public function set($object, $key, $value)
    {
        for ($i=0,$c=count($this->local);$i<$c;$i++) {
            $object->set($this->local[$i], $value->{$this->foreign[$i]});
        }
    }
}
