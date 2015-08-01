<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Types\Sequence;
use \Pheasant\Types\StringType;

class SecretIdentity extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\Sequence(),
            'realname' => new Types\StringType(),
            );
    }

    public function relationships()
    {
        return array(
            'Hero' => Hero::hasOne('id', 'identityid'),
            'Powers' => Power::hasMany()->through('Hero'),
            );
    }
}
