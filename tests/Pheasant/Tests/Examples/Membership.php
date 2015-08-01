<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Types\Sequence;
use \Pheasant\Types\StringType;

class Membership extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\Sequence(),
            'group_id' => new Types\IntegerType(),
            'user_id' => new Types\IntegerType(),
            );
    }

    public function relationships()
    {
        return array(
            'User' => User::belongsTo('user_id','userid'),
            'Group' => Group::belongsTo('group_id','id'),
            );
    }
}
