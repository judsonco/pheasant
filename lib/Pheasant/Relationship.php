<?php

namespace Pheasant;

class Relationship
{
    public $alias, $class, $local, $foreign;
    protected $_through, $_source;

    public function __construct($class, $local=null, $foreign=null)
    {
        $this->class = $class;
        $this->local = is_array($local) ? $local : array($local);
        $this->foreign = empty($foreign) ? $this->local : (is_array($foreign) ? $foreign : array($foreign));
    }

    public function get($object, $key)
    {
        throw new \BadMethodCallException(
            "Get not supported on ".get_class($this));
    }

    public function set($object, $key, $value)
    {
        throw new \BadMethodCallException(
            "Set not supported on ".get_class($this));
    }

    public function add($object, $value)
    {
        throw new \BadMethodCallException(
            "Add not supported on ".get_class($this));
    }

    /**
     * Delegates to the finder for querying
     * @return Query
     */
    protected function query($sql, $params)
    {
        return \Pheasant::instance()->finderFor($this->class)
            ->query(new \Pheasant\Query\Criteria($sql, $params))
            ;
    }

    /**
     * Delegates to the schema for hydrating
     * @return DomainObject
     */
    protected function hydrate($row)
    {
        return \Pheasant::instance()
            ->schema($this->class)->hydrate($row);
    }

    /**
     * Helper function that creates a closure that calls the add function
     * @return Closure
     */
    protected function adder($object)
    {
        $rel = $this;

        return function($value) use ($object, $rel) {
            return $rel->add($object, $value);
        };
    }

    // -------------------------------------
    // delegate double dispatch calls to type

    public function getter($key, $cache=null)
    {
        $rel = $this;

        return function($object) use ($key, $rel, $cache) {
            return $rel->get($object, $key, $cache);
        };
    }

    public function setter($key)
    {
        $rel = $this;

        return function($object, $value) use ($key, $rel) {
            return $rel->set($object, $key, $value);
        };
    }

    // -------------------------------------
    // static helpers

    /**
     * Takes either a flat array of relationships or a nested key=>value array and returns
     * it as a nested format
     * @return array
     */
    public static function normalizeMap($array)
    {
        $nested = array();

        foreach ((array) $array as $key=>$value) {
            if (is_numeric($key)) {
                $nested[$value] = array();
            } else {
                $nested[$key] = $value;
            }
        }

        return $nested;
    }

    /**
     * Adds a join clause to the given query for the given schema and relationship. Optionally
     * takes a nested list of relationships that will be recursively joined as needed.
     * @return void
     */
    public static function addJoin($query, $parentAlias, $schema, $relName, $nested=array(), $joinType='inner', $ons='')
    {
        if (!in_array($joinType, array('inner','left','right'))) {
            throw new \InvalidArgumentException("Unsupported join type: $joinType");
        }

        list($relName, $alias) = self::parseRelName($relName);
        $rel = $schema->relationship($relName);

        // TODO: Figure out a better place to do this
        $on = str_replace('??', $parentAlias, is_array($ons) ? array_shift($ons) : $ons);

        // look up schema and table for both sides of join
        $instance = \Pheasant::instance();
        $localTable = $instance->mapperFor($schema->className())->table();
        $remoteSchema = $instance->schema($rel->class);
        $remoteTable = $instance->mapperFor($rel->class)->table();

        $joinMethod = $joinType.'Join';
        $queryString = implode(' AND ', array_filter(array_map(
          function ($local, $foreign) use ($parentAlias, $alias, $schema, $remoteSchema) {
                /*
                 * Because it's possible to have a relationship that depends on a computed
                 * property of a DomainObject, we should make sure that the property exists
                 * on the schema before trying to join on it.
                 *
                 * TODO: Possibly a better way to do this?
                 */
                if ($schema->hasAttribute($local) && $remoteSchema->hasAttribute($foreign)) {
                    return sprintf('`%s`.`%s`=`%s`.`%s`',
                        $parentAlias,
                        $local,
                        $alias,
                        $foreign
                      );
                }
            },
            $rel->local,
            $rel->foreign
        )));

        $query->$joinMethod(
            $remoteTable->name()->table,
            'ON '.$queryString.($on ? ' AND '.$on : ''),
            $alias
        );

        foreach (self::normalizeMap($nested) as $relName=>$nested) {
            self::addJoin($query, $alias, $remoteSchema, $relName, $nested, $joinType, $ons);
        }
    }

    /**
     * Parses `RelName r1` as array('RelName', 'r1') or `Relname` as array('RelName','RelName')
     * @return array
     */
    public static function parseRelName($relName)
    {
        $parts = explode(' ', $relName, 2);

        return isset($parts[1]) ? $parts : array($parts[0], $parts[0]);
    }

    public function through($through=null, $source=null){
        if (!$through) {
            return $this->_through;
        } else {
            if (!($this instanceof \Pheasant\Relationships\HasMany ||
                  $this instanceof \Pheasant\Relationships\HasOne)) {
                $class = get_class($this);
                throw new \Pheasant\Exception("`Through` not supported on {$class}");
            }

            $this->_through = $through;
            $this->_source  = $source ? $source : $through;

            return $this;
        }
    }

    public function source($source=null)
    {
        if (!$source) {
            return $this->_source;
        } else {
            if (!($this instanceof \Pheasant\Relationships\HasMany ||
                  $this instanceof \Pheasant\Relationships\HasOne)) {
                $class = get_class($this);
                throw new \Pheasant\Exception("`Through` not supported on {$class}");
            }
            $this->_source = $source;

            return $this;
        }
    }

    public function finalForObject($object){
        if (!$this->through()) return $this;

        $relationships = $object->schema()->relationships();
        $final = $relationships[$this->through()];
        $final->alias = $this->through();

        while ($final->through()) {
            # Select a new through relation
            $alias = $final->through();
            $final = $relationships[$final->through()];
            $final->alias = $alias;
        }

        return $final;
    }

    public static function joinsFor($object, $key)
    {
        $class = get_class($object);
        $relationships = $class::schema()->relationships();
        $final = $relationships[$key];

        if (!$final->through()) return array();

        $joins = $tmpJoins = array();
        while ($final->through()) {
            // The class of the source relationship
            $sourceClass = $final->class;
            // All the relationships of the source class
            $sourceRels = $sourceClass::schema()->relationships();
            // The source relationship
            $sourceRel = $sourceRels[$final->source()];
            // The name of the join
            $sourceJoin = $final->source();

            // While the source is a through relationship,
            // we should continue to traverse the relationship
            // graph until we find something we can join.
            while ($sourceRel->through()) {
                if (!$sourceRels[$sourceRel->through()]->through()) {
                    $sourceJoin = $sourceRel->through();
                    break;
                }
                $sourceRel = $sourceRels[$sourceRel->through()];
            }

            $tmpJoins []= $sourceJoin;
            $final = $relationships[$final->through()];
        }

        # Build a graph of dependant joins
        for ($i=count($tmpJoins); $i>0; $i--) $joins
            ? $joins = array($tmpJoins[$i-1] => $joins)
            : $joins []= $tmpJoins[$i-1];

        return $joins;
    }

    protected function queryFor($object, $key, $params=array())
    {
        $class = $this->class;

        $final = $this;
        $alias = $final->alias;
        if ($this->through()) {
            // Make sure the alias is set on this relationship
            $this->alias = $key;

            // Create an array that will hold the join graph
            $final = $this->finalForObject($object);
            $joins = static::joinsFor($object, $key);

            if ($joins) {
                $alias = reset($joins);
                while (is_array($alias)) {
                    $alias = reset($alias);
                }
            }
        }

        // If there still isn't an alias, then we should use the
        // key as an alias.
        $alias = $alias ? $alias : $key;

        if (!$params) {
            $params = array_map(
                function ($k) use ($object) {
                    return $object->{$k};
                },
                $final->local
            );
        }

        $queryString = array_map(
            function ($foreign) use ($alias){
                return (!empty($alias)
                    ? "`{$alias}`."
                    : "")."`{$foreign}`=?";
            },
            $final->foreign
        );

        $foreignCount = count($final->foreign);
        // We test for this special case so that the generated sql
        // uses `column IN (val,val) instead of `column=val or column=val`
        $params = $foreignCount === 1 ? array($params) : $params;

        $paramString = implode(
            ' OR ',
            array_fill(
                0,
                count($params)/$foreignCount,
                implode(' AND ', $queryString)
            )
        );

        $mapper = \Pheasant::instance()
            ->mapperFor(new $class);

        if (!$this->through()) {
            return $mapper->query(
                new \Pheasant\Query\Criteria($paramString, $params), $alias);
        } else {
            $query = $mapper->query(
                new \Pheasant\Query\Criteria($paramString, $params),
                $this->alias); // Use the through alias ($this->alias)
        }

        $schemaAlias = $this->alias
            ? $this->alias
            : $object->schema()->alias();

        # Select the row we're looking for, and foreign keys
        # TODO: Alias foreign keys
        $query->select(
          "`{$schemaAlias}`.*, ".
          implode(',', array_map(function($k) use($alias){
            return "`{$alias}`.`{$k}` as `{$k}_foreign`";
          }, $final->foreign))
        );

        $joinType = 'inner';
        foreach (Relationship::normalizeMap($joins) as $alias => $nested) {
            Relationship::addJoin($query,
                $schemaAlias, $class::schema(), $alias, $nested, $joinType);
        }

        return $query;
    }

    public function collectionFor($object, $key, $params=array())
    {
        $class = $this->class;
        $schema = clone $class::schema();

        return new Collection($this->class, $this->queryFor($object, $key, $params), $this->adder($object), $schema->setAlias($key));
    }
}
