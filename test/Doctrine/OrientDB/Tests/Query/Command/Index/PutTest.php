<?php

/**
 * CreateTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\Index;

use Doctrine\OrientDB\Query\Command\Index\Put;
use PHPUnit\TestCase;

class PutTest extends TestCase
{
    public function setup() {
        $this->put = new Put('i', 'k', '12:0');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Name'  => array(),
            ':Key'   => array(),
            ':Value' => array(),
        );

        $this->assertTokens($tokens, $this->put->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'INSERT INTO index:i (key,rid) values ("k", #12:0)';

        $this->assertCommandGives($query, $this->put->getRaw());
    }
}
