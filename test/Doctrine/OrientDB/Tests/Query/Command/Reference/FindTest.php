<?php

/**
 * RevokeTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\Reference;

use Doctrine\OrientDB\Query\Command\Reference\Find;
use PHPUnit\TestCase;

class FindTest extends TestCase
{
    public function setUp() {
        $this->find = new Find('12:1');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Rid'       => array(),
            ':ClassList' => array(),
        );

        $this->assertTokens($tokens, $this->find->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'FIND REFERENCES 12:1';

        $this->assertCommandGives($query, $this->find->getRaw());
    }

    public function testAddingACLass() {
        $query = 'FIND REFERENCES 12:1 [Class]';

        $this->assertCommandGives($query, $this->find->in(array('Class'))->getRaw());
    }

    public function testUnappedingAClass() {
        $query = 'FIND REFERENCES 12:1 [Class]';

        $this->find->in(array('myClasses'));

        $this->assertCommandGives($query, $this->find->in(array('Class'), false)->getRaw());
    }

    public function testUsingTheFluentInterface() {
        $query = 'FIND REFERENCES 12:1 [Class, myClass]';

        $this->assertCommandGives($query, $this->find->in(array('Class'))->in(array('myClass'))->getRaw());
    }
}
