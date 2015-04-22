<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Types\SequenceType;
use \Pheasant\Types\StringType;

class Comment extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\SequenceType(),
            'text' => new Types\StringType(),
            'user_id' => new Types\IntegerType(),
            );
    }

    public function relationships()
    {
        return array(
            'User' => User::belongsTo('user_id','userid'),
            );
    }
}
