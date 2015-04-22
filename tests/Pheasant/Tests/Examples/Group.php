<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Types\SequenceType;
use \Pheasant\Types\StringType;

class Group extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\SequenceType(),
            'name' => new Types\StringType(),
            );
    }

    public function relationships()
    {
        return array(
            'Memberships' => Membership::hasMany('id','group_id'),
            'Users' => User::hasMany()->through('Memberships'),
            'Comments' => Comment::hasMany()->through('Users', 'User'),
            );
    }
}
