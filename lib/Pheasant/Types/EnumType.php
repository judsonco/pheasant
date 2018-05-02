<?php

namespace Pheasant\Types;

class EnumType extends BaseType
{
    private $_enum;

    /**
     * @constructor
     */
    public function __construct($enum = array(), $options=null)
    {
        if (empty($enum)) {
            throw new \Pheasant\Exception("Enum requires at least 1 value, none given.");
        }

        parent::__construct($options);
        $this->_enum = $enum;
    }

    /* (non-phpdoc)
     * @see \Pheasant\Type::columnSql
     */
    public function columnSql($column, $platform)
    {
        return $platform->columnSql($column, "enum('" . implode("','", $this->_enum) . "')", $this->options());
    }

    /* (non-phpdoc)
     * @see \Pheasant\Type::unmarshal
     */
    public function unmarshal($value)
    {
        return $value;
    }

    /* (non-phpdoc)
     * @see \Pheasant\Type::marshal
     */
    public function marshal($value)
    {
        if (! in_array($value, $this->_enum) &&
            ! in_array((int) $value, $this->acceptedIndexes())) {
            throw new \InvalidArgumentException("Attempting to insert invalid enum value {$value}");
        }
        return $value;
    }

    /**
     * @return array accepted indexes for an enum
     */
    protected function acceptedIndexes()
    {
        return array_map(function ($key) {
            return $key += 1;
        }, array_keys($this->_enum));
    }
}
