<?php

namespace Pheasant\Types;

/**
 * A date and time type
 */
class DateTime extends Base
{
    /* (non-phpdoc)
     * @see \Pheasant\Type::columnSql
     */
    public function columnSql($column, $platform)
    {
        return $platform->columnSql($column, 'datetime', $this->options());
    }

    /* (non-phpdoc)
     * @see \Pheasant\Type::unmarshal
     */
    public function unmarshal($value)
    {
        if(!($value instanceof \DateTime)){
            return new \DateTime($value);
        } else {
            if($value instanceof \DateTime) return $value;
        }
    }

    /* (non-phpdoc)
     * @see \Pheasant\Type::marshal
     */
    public function marshal($value)
    {
        return parent::marshal($value->format("c"));
    }
}
