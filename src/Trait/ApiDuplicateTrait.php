<?php

// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace App\Trait;

use App\Deny\AbstractApiDuplicateObjectDeny;
use App\Entity\Traits\CreatedAtEntityInterface;
use App\Entity\Traits\CreatedByEntityInterface;
use App\Entity\Traits\UpdatedAtEntityInterface;
use App\Entity\Traits\UpdatedByEntityInterface;
use App\Service\Helper\FileManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\String\Inflector\EnglishInflector;

trait ApiDuplicateTrait
{
    use FileManagerTrait;

    #[Rest\Get('/{id}/duplicate', requirements: ['id' => '\d+'])]
    #[Api\Summary('Duplicate {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to duplicate')]
    #[Api\Response200SubjectGroups('Returns duplicated {{ subjectLower }}')]
    #[Api\Response404Id]
    public function duplicateAction(int $id)
    {
        $object = $this->find($id, AbstractApiDuplicateObjectDeny::DUPLICATE);

        return $this->processDuplicate($object);
    }

    protected function processDuplicate($object)
    {
        $duplicatedObject = $this->getDuplicatedObject($object);

        $this->entityManager->persist($duplicatedObject);
        $this->entityManager->flush();

        $this->modifyResponseObject($duplicatedObject);

        return $duplicatedObject;
    }

    protected function getUniqueCopiedString($object, string $field, string $prefix = 'COPY ', int $length = 255): string
    {
        $getter = 'get'.ucfirst($field);
        $baseCopiedString = mb_substr($prefix.$object->$getter(), 0, $length);

        $suffixCounter = 0;
        do {
            $copiedString = $baseCopiedString;
            if ($suffixCounter > 0) {
                $suffix = '-'.$suffixCounter;
                $copiedString = mb_substr($copiedString, 0, $length - strlen($suffix));
                $copiedString = $copiedString.$suffix;
            }

            $results = $this->getRepository(get_class($object))->count([$field => $copiedString]);
            ++$suffixCounter;
        } while ($results > 0);

        return $copiedString;
    }

    protected function duplicateUploadedFile($object, $duplicatedObject, $field): void
    {
        $getter = 'get'.ucfirst($field);
        $originalFilepath = $object->$getter();
        if (!$originalFilepath) {
            return;
        }

        $uploadDir = $object->getUploadDir($field);
        $duplicatedFilepath = $this->fileManager->move($originalFilepath, $duplicatedObject->getUploadDir($field), true);

        $setter = 'set'.ucfirst($field);
        $duplicatedObject->$setter($duplicatedFilepath);
    }

    protected function getDuplicatedObject($object): object
    {
        $duplicatedObject = clone $object;

        if ($duplicatedObject instanceof CreatedAtEntityInterface) {
            $duplicatedObject->setCreatedAt(null);
        }

        if ($duplicatedObject instanceof CreatedByEntityInterface) {
            $duplicatedObject->setCreatedBy(null);
        }

        if ($duplicatedObject instanceof UpdatedAtEntityInterface) {
            $duplicatedObject->setUpdatedAt(null);
        }

        if ($duplicatedObject instanceof UpdatedByEntityInterface) {
            $duplicatedObject->setUpdatedBy(null);
        }

        return $duplicatedObject;
    }

    protected function getCollectionDuplicatedObject($object): object
    {
        return $this->getDuplicatedObject($object);
    }

    protected function duplicateCollection($object, $duplicatedObject, string $collectionField, ?string $addItemFunction = null): void
    {
        $getter = 'get'.ucfirst($collectionField);

        if (null === $addItemFunction) {
            $inflector = new EnglishInflector();
            // There can be multiple singular variations
            $singulars = $inflector->singularize($collectionField);

            foreach ($singulars as $singular) {
                $addItemFunction = 'add'.ucfirst($singular);
                if (method_exists($duplicatedObject, $addItemFunction)) {
                    break;
                }
            }
        }

        foreach ($object->$getter() as $item) {
            $duplicatedItem = $this->getCollectionDuplicatedObject($item);

            $duplicatedObject->$addItemFunction($duplicatedItem);
        }
    }
}
