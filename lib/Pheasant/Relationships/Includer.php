<?php

namespace Pheasant\Relationships;

use \Pheasant\Query\Criteria;
use \Pheasant\Cache\ArrayCache;

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

    public function loadCache()
    {
        $this->_cache = new ArrayCache();
        $rel = $this->_rel;
        $columnString = array_map(function($l)use($rel){ return (!empty($rel->alias)?$rel->alias.'.':'').$l; }, $this->_rel->local);
        $ids = iterator_to_array(
            $this->_query->select(implode(',', $columnString))->groupBy(implode(',', $columnString))->execute()->column()
        );

        $queryString = array_map(function($f)use($rel){ return $f.'=?'; }, $this->_rel->foreign);
        $relatedObjects = \Pheasant::instance()
            ->finderFor($this->_rel->class)
            ->find($this->_rel->class, new Criteria(
                implode(' AND ', $queryString), array($ids))
            )
            ->includes($this->_nested);

        foreach ($relatedObjects as $obj) {
            $this->_cache->add($obj);
        }
    }

    public function get($object, $key)
    {
        if(!isset($this->_cache)) {
            $this->loadCache();
        }

        return $this->_rel->get($object, $key, $this->_cache);
    }
}
