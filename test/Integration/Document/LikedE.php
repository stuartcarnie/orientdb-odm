<?php

namespace Integration\Document;

/**
 * @Relationship(oclass="LikedE")
 */
class LikedE extends Edge
{
    /**
     * @Property(type="string")
     * @var string
     */
    public $description;
}