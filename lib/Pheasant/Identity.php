<?php

namespace Pheasant;

class Identity implements \IteratorAggregate
{
    private $_class, $_properties, $_object;

    public function __construct($class, $properties, $object)
    {
        $this->_class = $class;
        $this->_properties = $properties;
        $this->_object = $object;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_properties);
    }

    public function toArray()
    {
        $array = array();

        foreach($this->_properties as $property)
            $array[$property->name] = $this->_object->get($property->name);

        return $array;
    }

    public function toCriteria()
    {
        return new Query\Criteria($this->toArray());
    }

    public function equals($other)
    {
        return $this->toArray() == $other->toArray();
    }

    public function __toString()
    {
        return static::identityStringFromParams($this->_class, $this->toArray());
    }

    static function identityStringFromParams($class, $params)
    {
        $keyValues = array_map(
            function ($k) use ($params) {
                return sprintf('%s=%s', $k, $params[$k]);
            },
            array_keys($params)
        );

        return sprintf('%s[%s]', $class, implode(',', $keyValues));
    }
}
