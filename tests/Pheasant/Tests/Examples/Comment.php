<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Types\Sequence;
use \Pheasant\Types\String;

class Comment extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\Sequence(),
            'text' => new Types\String(),
            'user_id' => new Types\Integer(),
            );
    }

    public function relationships()
    {
        return array(
            'User' => User::belongsTo('user_id','userid'),
            );
    }
}
