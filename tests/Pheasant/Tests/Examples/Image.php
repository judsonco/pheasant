<?php

namespace Pheasant\Tests\Examples;

use Pheasant\DomainObject;
use Pheasant\Types;
use Pheasant\Mapper\RowMapper;

class Image extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\IntegerType(11, 'unsigned primary auto_increment'),
            'comment' => new Types\StringType(255),
            'attachable_id' => new Types\IntegerType(11, 'unsigned'),
            'attachable_type' => new Types\StringType(255),
            );
    }

    public function relationships()
    {
        return array(
            'attachable' => static::morphTo(),
            );
    }
}
