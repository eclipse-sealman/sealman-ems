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

namespace App\EventListener;

use App\Model\UploadInterface;
use App\Service\UploadManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class UploadListener implements EventSubscriber
{
    /**
     * @var UploadManager
     */
    private $uploadManager;

    public function __construct(UploadManager $uploadManager)
    {
        $this->uploadManager = $uploadManager;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof UploadInterface) {
            foreach ($entity->getUploadFields() as $field) {
                $this->uploadManager->upload($entity, $field);
            }

            $args->getObjectManager()->persist($entity);
            $args->getObjectManager()->flush();
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof UploadInterface) {
            foreach ($entity->getUploadFields() as $field) {
                if ($args->hasChangedField($field)) {
                    $this->uploadManager->upload($entity, $field, $args->getOldValue($field));
                }
            }
        }
    }
}
