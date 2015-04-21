<?php

namespace Pheasant\Relationships;

use \Pheasant\Query\Criteria;
use \Pheasant\Cache\ArrayCache;
use \Pheasant\Identity;

/**
 * Finds all possible objects in a relationship that might exist in a query
 * and queries them in one shot for future hydration
 * @see http://stackoverflow.com/questions/97197/what-is-the-n1-selects-issue
 */
class Includer
{
    private
        $_query,
        $_rel,
        $_nested,
        $_cache
        ;

    public function __construct($query, $rel, $nested=array())
    {
        $this->_query = $query;
        $this->_rel = $rel;
        $this->_nested = $nested;
    }

    public function loadCache($object, $key, $alias)
    {
        $this->_cache = new ArrayCache();
        $rel = $this->_rel->finalForObject($object);


        $aliasedQueryString = implode(',',
            array_map(
                function($local) use($alias) {
                    return ($alias
                        ? "{$alias}."
                        :'').$local;
                },
                $rel->local
            )
        );
        $results = $this->_query
            ->select($aliasedQueryString)
            ->execute();

        $ids = array();
        foreach ($results->toArray() as $result) {
            foreach ($rel->local as $local) $ids []= $result[$local];
        }

        $relatedObjects = $this->_rel->collectionFor($object, $key, $ids)
                                     ->includes($this->_nested);

        if (!($this->_rel instanceof HasMany)) {
            foreach ($relatedObjects as $obj) {
                $this->_cache->add($obj);
            }
        } else {
            $relatedObjects->restrictBy(
                array_map(
                    function ($local, $foreign) {
                        return array($local, $foreign);
                    },
                    $rel->local,
                    $rel->foreign
                )
            );

            foreach ($ids as $id) {
                $identity = Identity::identityStringFromParams(
                    $this->_rel->class,
                    is_array($rel->foreign)
                        ? array_combine($rel->foreign, (is_array($id) ? $id : array($id)))
                        : array($rel->foreign, current($id))
                );

                $collection = clone $relatedObjects;
                $collection->restrictTo($id);
                $this->_cache->add($collection, $identity);
            }
        }
    }

    public function get($object, $key, $alias=null)
    {
        if (!isset($this->_cache)) {
            $this->loadCache($object, $key, $alias);
        }

        return $this->_rel->get($object, $key, $this->_cache);
    }
}
