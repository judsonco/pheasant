<?php

namespace Pheasant\Relationships;

use \Pheasant\PropertyReference;
use \Pheasant\Relationship;

/**
 * A HasOne relationship represents a 1->1 relationship. The local object owns
 * the primary key, the foreign object has the foreign key.
 *
 * An example of this type of relationship would be a Hero HasOne SecretIdentity.
 * Hero owns the primary key of heroid, and SecretIdentity has a foreign key
 * of heroid.
 */
class HasOne extends Relationship
{
    private $_allowEmpty;

    /**
     * Constructor
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
                $schema->hash(
                    $object,
                    array_map(
                        function ($l, $f) {
                            return array($l, $f);
                        },
                        $this->local,
                        $this->foreign
                    )
                )
            )) {
                return $cached;
            }
        }

        $result = array();
        if($query = $this->queryFor($object, $key)){
            $result = $query->execute();
        }

        if (!count($result)) {
            if ($this->_allowEmpty) {
                return null;
            } else {
                $foreign = '['.implode(',', $this->foreign).']';
                $local = '['.implode(',', $this->local).']';

                throw new \Pheasant\Exception("Failed to find a {$key} (via {$foreign}={$local}");
            }
        }

        return $this->hydrate($result->row());
    }

    protected function queryFor($object, $key, $params=array())
    {
        if (!$params) {
            $params = array_map(
                function ($local) use (&$hasNull, &$object) {
                    return $object->{$local};
                },
                $this->local
            );
        }

        $foreignCount = count($this->foreign);
        // We test for this special case so that the generated sql
        // uses `column IN (val,val) instead of `column=val or column=val`
        $params = $foreignCount === 1 ? array($params) : $params;

        $queryString = array_map(
            function ($foreign) {
                return "{$foreign}=?";
            },
            $this->foreign
        );

        $paramString = implode(
            ' OR ',
            array_fill(
                0,
                count($params)/$foreignCount,
                implode(' AND ', $queryString)
            )
        );

        return $this->query($paramString, $params);
    }

    /* (non-phpdoc)
     * @see Relationship::set()
     */
    public function set($object, $key, $value)
    {
        $savedAfter = false;
        for ($i=0,$c=count($this->local);$i<$c;$i++) {
            $newValue = $object->{$this->local[$i]};

            if(!$savedAfter && ($newValue instanceof PropertyReference))
              $object->saveAfter($value);

            $value->set($this->foreign[$i], $newValue);
        }
    }
}
