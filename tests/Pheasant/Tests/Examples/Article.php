<?php

namespace Pheasant\Tests\Examples;

use Pheasant\DomainObject;
use Pheasant\Types;
use Pheasant\Mapper\RowMapper;

class Article extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\IntegerType(11, 'unsigned primary auto_increment'),
            'title' => new Types\StringType(255),
            );
    }

    public function relationships()
    {
        return array(
            'images' => Image::morphMany('attachable'),
            );
    }
}
