<?php

namespace Doctrine\ODM\OrientDB\Mapping\Driver;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\ODM\OrientDB\Mapping\Annotations\AbstractDocument;
use Doctrine\ODM\OrientDB\Mapping\Annotations\ChangeTrackingPolicy;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Document;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Edge;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EdgeBag;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Embedded;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedDocument;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedList;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedMap;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedPropertyBase;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedSet;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Link;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkList;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkMap;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkPropertyBase;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkSet;
use Doctrine\ODM\OrientDB\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Property;
use Doctrine\ODM\OrientDB\Mapping\Annotations\PropertyBase;
use Doctrine\ODM\OrientDB\Mapping\Annotations\RID;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Version;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Vertex;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata as MD;
use Doctrine\ODM\OrientDB\Mapping\MappingException;

class AnnotationDriver extends AbstractAnnotationDriver
{
    protected $entityAnnotationClasses = [
        Document::class         => true,
        MappedSuperclass::class => true,
        EmbeddedDocument::class => true,
        Edge::class             => true,
        Vertex::class           => true,
    ];

    /**
     * Registers annotation classes to the common registry.
     *
     * This method should be called when bootstrapping your application.
     */
    public static function registerAnnotationClasses() {
        AnnotationRegistry::registerFile(__DIR__ . '/../Annotations/DoctrineAnnotations.php');
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string        $className
     * @param ClassMetadata $metadata
     *
     * @throws MappingException
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata) {
        /** @var \Doctrine\ODM\OrientDB\Mapping\ClassMetadata $metadata */
        $classAnnotations = $this->reader->getClassAnnotations($metadata->getReflectionClass());
        if (count($classAnnotations) === 0) {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        $documentAnnot = null;
        foreach ($classAnnotations as $annot) {

            if ($annot instanceof AbstractDocument) {
                if ($documentAnnot !== null) {
                    throw MappingException::duplicateDocumentAnnotation($className);
                }
                $documentAnnot = $annot;
                continue;
            }

            switch (true) {
                case $annot instanceof ChangeTrackingPolicy:
                    $metadata->setChangeTrackingPolicy(constant('Doctrine\\ODM\\OrientDB\\Mapping\\ClassMetadata::CHANGETRACKING_' . $annot->value));
            }
        }

        $isDocument = false;
        switch (true) {
            case $documentAnnot instanceof Document:
                $isDocument = true;
                break;

            case $documentAnnot instanceof Vertex:
                $isDocument          = true;
                $metadata->graphType = MD::GRAPH_TYPE_VERTEX;
                break;

            case $documentAnnot instanceof Edge:
                $isDocument          = true;
                $metadata->graphType = MD::GRAPH_TYPE_EDGE;
                break;

            case $documentAnnot instanceof EmbeddedDocument:
                $isDocument                   = true;
                $metadata->isEmbeddedDocument = true;
                break;
            case $documentAnnot instanceof MappedSuperclass:
                $metadata->isMappedSuperclass = true;
                break;
        }

        if ($isDocument) {
            $metadata->isAbstract = $documentAnnot->abstract;
            $metadata->setOrientClass($documentAnnot->oclass);
        }

        foreach ($metadata->reflClass->getProperties() as $property) {
            if (($metadata->isMappedSuperclass && !$property->isPrivate())
                ||
                $metadata->isInheritedField($property->name)
            ) {
                continue;
            }

            $pas = $this->reader->getPropertyAnnotations($property);
            foreach ($pas as $ann) {
                $mapping = [
                    'fieldName' => $property->getName(),
                    'nullable'  => false,
                ];

                if ($ann instanceof PropertyBase) {
                    if (!$ann->name) {
                        $ann->name = $property->getName();
                    }
                    $mapping['name'] = $ann->name;
                }

                switch (true) {
                    case $ann instanceof Property:
                        $mapping = $this->propertyToArray($property->getName(), $ann);
                        $metadata->mapField($mapping);
                        continue;

                    case $ann instanceof RID:
                        $metadata->mapRid($property->getName());
                        continue;

                    case $ann instanceof Version:
                        $metadata->mapVersion($property->getName());
                        continue;

                    case $ann instanceof Link:
                        $this->mergeLinkToArray($mapping, $ann);
                        $mapping['nullable'] = $ann->nullable;
                        $metadata->mapLink($mapping);
                        continue;

                    case $ann instanceof LinkList:
                        $this->mergeLinkToArray($mapping, $ann);
                        $metadata->mapLinkList($mapping);
                        continue;

                    case $ann instanceof LinkSet:
                        $this->mergeLinkToArray($mapping, $ann);
                        $metadata->mapLinkSet($mapping);
                        continue;

                    case $ann instanceof LinkMap:
                        $this->mergeLinkToArray($mapping, $ann);
                        $metadata->mapLinkMap($mapping);
                        continue;

                    case $ann instanceof Embedded:
                        $this->mergeEmbeddedToArray($mapping, $ann);
                        $mapping['nullable'] = $ann->nullable;
                        $metadata->mapEmbedded($mapping);
                        continue;

                    case $ann instanceof EmbeddedList:
                        $this->mergeEmbeddedToArray($mapping, $ann);
                        $metadata->mapEmbeddedList($mapping);
                        continue;

                    case $ann instanceof EmbeddedSet:
                        $this->mergeEmbeddedToArray($mapping, $ann);
                        $metadata->mapEmbeddedSet($mapping);
                        continue;

                    case $ann instanceof EmbeddedMap:
                        $this->mergeEmbeddedToArray($mapping, $ann);
                        $metadata->mapEmbeddedMap($mapping);
                        continue;

                    case $ann instanceof EdgeBag:
                        $this->mergeEdgeBagToArray($mapping, $ann);
                        $metadata->mapEdgeLinkBag($mapping);
                        continue;
                }
            }
        }
    }

    public function &propertyToArray($fieldName, Property $prop) {
        $mapping = [
            'fieldName' => $fieldName,
            'name'      => $prop->name,
            'type'      => $prop->type,
            'nullable'  => $prop->nullable,
        ];

        return $mapping;
    }

    private function mergeLinkToArray(array &$mapping, LinkPropertyBase $link) {
        $mapping['cascade']       = $link->cascade;
        $mapping['targetDoc']     = $link->targetDoc;
        $mapping['orphanRemoval'] = $link->orphanRemoval;

        if (!empty($link->parentProperty)) {
            $mapping['parentProperty'] = $link->parentProperty;
        }
        if (!empty($link->childProperty)) {
            $mapping['childProperty'] = $link->childProperty;
        }
    }

    private function mergeEmbeddedToArray(array &$mapping, EmbeddedPropertyBase $embed) {
        $mapping['targetDoc'] = $embed->targetDoc;
    }

    private function mergeEdgeBagToArray(array &$mapping, EdgeBag $edge) {
        $mapping['targetDoc'] = $edge->targetDoc;
        $mapping['oclass']    = $edge->oclass;
        $mapping['direction'] = $edge->direction;
    }

    /**
     * Factory method for the Annotation Driver
     *
     * @param array|string $paths
     * @param Reader       $reader
     *
     * @return AnnotationDriver
     */
    public static function create($paths = [], Reader $reader = null) {
        if ($reader === null) {
            $reader = new AnnotationReader();
        }

        return new self($reader, $paths);
    }
}