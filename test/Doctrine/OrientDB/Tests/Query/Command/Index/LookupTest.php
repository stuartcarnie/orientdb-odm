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

use Doctrine\OrientDB\Query\Command\Index\Lookup;
use PHPUnit\TestCase;

class LookupTest extends TestCase
{
    public function setup() {
        $this->lookup = new Lookup('dictionary');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Index' => array(),
            ':Where' => array(),
        );

        $this->assertTokens($tokens, $this->lookup->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'SELECT FROM index:dictionary';

        $this->assertCommandGives($query, $this->lookup->getRaw());
    }

    public function testSettingWhereCondition() {
        $query = 'SELECT FROM index:dictionary WHERE key = "luke"';
        $this->lookup->where('key = ?', 'luke');

        $this->assertCommandGives($query, $this->lookup->getRaw());
    }
}
