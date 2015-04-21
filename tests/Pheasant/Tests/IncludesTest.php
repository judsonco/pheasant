<?php

namespace Pheasant\Tests\Relationships;

use \Pheasant\Tests\Examples\Hero;
use \Pheasant\Tests\Examples\Power;
use \Pheasant\Tests\Examples\SecretIdentity;

class IncludesTest extends \Pheasant\Tests\MysqlTestCase
{
    public function setUp()
    {
        parent::setUp();

        $migrator = new \Pheasant\Migrate\Migrator();
        $migrator
            ->create('hero', Hero::schema())
            ->create('power', Power::schema())
            ->create('secretidentity', SecretIdentity::schema())
            ;

        $this->pheasant
            ->connection()
            ->execute(
                'INSERT INTO sequences (name, id) VALUES (?, ?)',
                array('SECRETIDENTITY_ID_SEQ', 100)
            );

        $spiderman = Hero::createHelper('Spider Man', 'Peter Parker', array(
            'Super-human Strength', 'Spider Senses'
        ));
        $superman = Hero::createHelper('Super Man', 'Clark Kent', array(
            'Super-human Strength', 'Invulnerability'
        ));
        $batman = Hero::createHelper('Batman', 'Bruce Wayne', array(
            'Richness', 'Super-human Intellect'
        ));
    }

    public function testIncludesHitsCache()
    {
        $queries = 0;

        $this->connection()->filterChain()->onQuery(function ($sql) use (&$queries) {
            ++$queries;

            return $sql;
        });

        // the first lookup of SecretIdentity should cache all the rest
        $heros = Hero::all()->includes(array('SecretIdentity'))->toArray();
        $this->assertNotNull($heros[0]->SecretIdentity);

        // these should be from cache
        $queries = 0;
        $this->assertNotNull($heros[1]->SecretIdentity);
        $this->assertNotNull($heros[2]->SecretIdentity);

        $this->assertEquals(0, $queries, "this should have hit the cache");
    }

    public function testHasManyIncludesHitsCache()
    {
        $queries = 0;

        $this->connection()->filterChain()->onQuery(function ($sql) use (&$queries) {
            ++$queries;

            return $sql;
        });

        // the first lookup of SecretIdentity should cache all the rest
        $heros = Hero::all()->includes(array('Powers'))->toArray();
        $this->assertNotNull($heros[0]->Powers->toArray());
        $this->assertEquals(2, $heros[0]->Powers->count());
        $this->assertEquals(2, count($heros[0]->Powers->toArray()));

        // these should be from cache
        $queries = 0;
        $this->assertNotNull($heros[1]->Powers->toArray());
        $this->assertEquals(2, $heros[1]->Powers->count());
        $this->assertEquals(2, count($heros[1]->Powers->toArray()));

        $this->assertNotNull($heros[2]->Powers->toArray());
        $this->assertEquals(2, $heros[2]->Powers->count());
        $this->assertEquals(2, count($heros[2]->Powers->toArray()));

        $this->assertEquals(0, $queries, "this should have hit the cache");
    }

    public function testHasManyThroughIncludesHitsCache()
    {
        $queries = 0;

        $this->connection()->filterChain()->onQuery(function ($sql) use (&$queries) {
            ++$queries;

            return $sql;
        });

        // the first lookup of SecretIdentity should cache all the rest
        $secret = SecretIdentity::all()->includes(array('Powers'))->toArray();
        $this->assertNotNull($secret[0]->Powers->toArray());
        $this->assertEquals(2, $secret[0]->Powers->count());
        $this->assertEquals(2, count($secret[0]->Powers->toArray()));

        // these should be from cache
        $queries = 0;

        // Make sure we got the correct info
        $this->assertEquals('Super-human Strength', $secret[0]->Powers[0]->description);
        $this->assertEquals('Spider Senses', $secret[0]->Powers[1]->description);

        // Make sure we're not returning null
        $this->assertNotNull($secret[1]->Powers->toArray());
        // Make sure the counts are correct
        $this->assertEquals(2, $secret[1]->Powers->count());
        $this->assertEquals(2, count($secret[1]->Powers->toArray()));
        // Make sure the returned information is correct
        $this->assertEquals('Super-human Strength', $secret[1]->Powers[0]->description);
        $this->assertEquals('Invulnerability', $secret[1]->Powers[1]->description);

        // Make sure we're not returning null
        $this->assertNotNull($secret[2]->Powers->toArray());
        // Make sure the counts are correct
        $this->assertEquals(2, $secret[2]->Powers->count());
        $this->assertEquals(2, count($secret[2]->Powers->toArray()));
        // Make sure the returned information is correct
        $this->assertEquals('Richness', $secret[2]->Powers[0]->description);
        $this->assertEquals('Super-human Intellect', $secret[2]->Powers[1]->description);

        // Make sure all of them touched the cache
        $this->assertEquals(0, $queries, "this should have hit the cache");
    }
}
