<?php

/*
 * This file is part of the Orient package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Domain;

use Doctrine\ODM\OrientDB\Mapping\Annotations as ODM;

/**
* @ODM\Document(oclass="Address")
*/
class Address
{
    /**
     * @ODM\Property(type="string")
     */
    public $street;
}
