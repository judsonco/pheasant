<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Types\SequenceType;
use \Pheasant\Types\StringType;

class User extends DomainObject
{
    public function properties()
    {
        return array(
            'userid' => new Types\SequenceType(),
            'firstname' => new Types\StringType(),
            'lastname' => new Types\StringType(),
            'group' => new Types\StringType(),
            );
    }

    public function relationships()
    {
        return array(
            'UserPrefs' => UserPref::hasMany('userid'),
            'Memberships' => Membership::hasMany('userid', 'user_id'),
            'Comments' => Comment::hasMany('userid', 'user_id'),
            'Groups' => Group::hasMany()->through('Memberships'),
            );
    }

    public static function createHelper($name, $group, $comments=array())
    {
        $user = null;
        \Pheasant::instance()->transaction(function() use(&$user, $name, $group, $comments) {
            $name = explode(' ', $name);
            $user = new User(array('firstname'=>$name[0], 'lastname'=>$name[1]));
            $user->save();

            $group = new Group(array('name' => $group));
            $group->save();

            $membership = new Membership(array('group_id'=>$group->id, 'user_id' => $user->userid));
            $membership->save();

            foreach ($comments as $comment) {
              $comment = new Comment(array('text'=>$comment));
              $comment->user_id = $user->userid;
              $comment->save();
            }

            $user->save();
        });

        return $user;
    }
}
