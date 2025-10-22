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

namespace App\Controller\Api;

use App\Attribute\Areas;
use App\Entity\UserTable;
use App\Entity\UserTableColumn;
use App\Form\UserTableEditType;
use App\Form\UserTableType;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

#[Rest\Route('/usertable')]
#[Api\Resource(
    class: UserTable::class
)]
// 'identification' serializer group is not needed here
#[Rest\View(serializerGroups: ['userTable:public'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
#[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
class UserTableController extends AbstractApiController
{
    #[Rest\Post('/columns')]
    #[Api\Summary('Get columns')]
    #[Api\RequestBody(content: new NA\Model(type: UserTableType::class))]
    #[Api\Response200ArraySubjectGroups(class: UserTableColumn::class, description: 'Returns columns')]
    #[Api\Response400]
    public function columnsAction(Request $request)
    {
        return $this->handleForm(UserTableType::class, $request, function ($object) {
            $tableKey = $object->getTableKey();

            $table = $this->getRepository(UserTable::class)->findOneBy(['tableKey' => $tableKey, 'user' => $this->getUser()]);

            if (!$table) {
                return [];
            }

            return $table->getColumns();
        });
    }

    #[Rest\Post('/columns/edit')]
    #[Api\Summary('Edit columns. Position is defined by place in columns array')]
    #[Api\RequestBody(content: new NA\Model(type: UserTableEditType::class))]
    #[Api\Response200ArraySubjectGroups(class: UserTableColumn::class, description: 'Returns updated columns')]
    #[Api\Response400]
    // Position is defined by place in the array
    public function columnsEditAction(Request $request)
    {
        return $this->handleForm(UserTableEditType::class, $request, function ($templateTable) {
            $tableKey = $templateTable->getTableKey();

            $table = $this->getRepository(UserTable::class)->findOneBy(['tableKey' => $tableKey, 'user' => $this->getUser()]);

            if (!$table) {
                $table = new UserTable();
                $table->setTableKey($tableKey);
                $table->setUser($this->getUser());

                $this->entityManager->persist($table);
            }

            // Create or edit columns that are included in a form
            foreach ($templateTable->getColumns() as $key => $templateTableColumn) {
                $column = $table->getColumns()->get($key);

                if (!$column) {
                    $column = new UserTableColumn();
                    $table->addColumn($column);
                }

                $column->setName($templateTableColumn->getName());
                $column->setVisible($templateTableColumn->getVisible());
                $column->setPosition($key + 1);

                $this->entityManager->persist($column);
            }

            // Remove columns that are not included in a form
            $templateTableColumnKeys = $templateTable->getColumns()->getKeys();
            $tableColumnKeys = $table->getColumns()->getKeys();
            // Start from last key to avoid issues due to mutating $columns collection in $table
            foreach (array_reverse($tableColumnKeys) as $tableColumnKey) {
                if (in_array($tableColumnKey, $templateTableColumnKeys)) {
                    continue;
                }

                $column = $table->getColumns()->get($tableColumnKey);
                $table->removeColumn($column);
                $this->entityManager->remove($column);
            }

            $this->entityManager->flush();

            return $table->getColumns();
        });
    }
}
