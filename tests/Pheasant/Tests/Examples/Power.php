<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Types\Sequence;
use \Pheasant\Types\StringType;

class Power extends DomainObject
{
    public function properties()
    {
        return array(
            'id' => new Types\Sequence(),
            'description' => new Types\StringType(),
            'heroid' => new Types\IntegerType()
            );
    }

    public function relationships()
    {
        return array(
            'Hero' => Hero::belongsTo('heroid','id'),
            'SecretIdentity' => SecretIdentity::hasOne()->through('Hero', 'Powers'),
            );
    }
}
