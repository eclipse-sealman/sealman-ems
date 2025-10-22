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

namespace App\Service;

use Carve\ApiBundle\Attribute\AddRoleBasedSerializerGroups;
use Carve\ApiBundle\Service\ApiResourceManager;
use FOS\RestBundle\Controller\Annotations\View;

class CarveApiResourceManager extends ApiResourceManager
{
    /**
     * Override the getSerializerGroups method to include role-based serializer groups with ROLE_DOCS_* attributes.
     * If certain role is not granted but corresponding ROLE_DOCS_* is granted, include the serializer groups.
     * This is required for API documentation to have correct schemas shown.
     */
    public function getRoleBasedSerializerGroups(\ReflectionClass $reflection): array
    {
        $serializerGroups = [];
        foreach ($reflection->getAttributes(AddRoleBasedSerializerGroups::class) as $attribute) {
            $attributeInstance = $attribute->newInstance();
            $roleAttribute = $attributeInstance->getAttribute();

            if ($roleAttribute && $this->isGrantedWithRoleDocs($roleAttribute)) {
                $serializerGroups = array_merge($serializerGroups, $attributeInstance->getSerializerGroups());
            }
        }

        return array_unique($serializerGroups);
    }

    /**
     * Override the getSerializerGroups method to include role-based serializer groups.
     */
    public function getSerializerGroups(\ReflectionClass|\ReflectionMethod $reflection): ?array
    {
        if ($reflection instanceof \ReflectionMethod) {
            foreach ($reflection->getAttributes(View::class) as $attribute) {
                $attributeInstance = $attribute->newInstance();
                $serializerGroups = $attributeInstance->getSerializerGroups();

                return $this->mergeSerializerGroups($serializerGroups, []);
            }

            $reflection = $reflection->getDeclaringClass();
        }

        $roleBasedSerializerGroups = $this->getRoleBasedSerializerGroups($reflection);

        foreach ($reflection->getAttributes(View::class) as $attribute) {
            $attributeInstance = $attribute->newInstance();
            $serializerGroups = $attributeInstance->getSerializerGroups();

            return $this->mergeSerializerGroups($serializerGroups, $roleBasedSerializerGroups);
        }

        // Additionally check parent class
        $reflectionParent = $reflection->getParentClass();
        $roleBasedSerializerGroups = $this->getRoleBasedSerializerGroups($reflectionParent);
        foreach ($reflectionParent->getAttributes(View::class) as $attribute) {
            $attributeInstance = $attribute->newInstance();
            $serializerGroups = $attributeInstance->getSerializerGroups();

            return $this->mergeSerializerGroups($serializerGroups, $roleBasedSerializerGroups);
        }

        return null;
    }

    /**
     * Helper method.
     * Merge serializer groups with role-based serializer groups or return null if both are empty.
     */
    protected function mergeSerializerGroups(array $serializationGroups, array $roleBasedSerializerGroups): ?array
    {
        if (count($roleBasedSerializerGroups) > 0) {
            return count($serializationGroups) > 0 ? array_unique(array_merge($serializationGroups, $roleBasedSerializerGroups)) : $roleBasedSerializerGroups;
        }

        return count($serializationGroups) > 0 ? $serializationGroups : null;
    }

    /**
     * Helper method.
     * Check if the user has the required role or a corresponding ROLE_DOCS_* role.
     * This is used to correctly show role-based schemas on API docs when regular roles are not available.
     * ROLE_DOCS_* roles are assigned only when API doc route is accessed.
     */
    protected function isGrantedWithRoleDocs(string $attribute): bool
    {
        if ($this->security->isGranted($attribute)) {
            return true;
        }

        switch ($attribute) {
            case 'ROLE_ADMIN':
                return $this->security->isGranted('ROLE_DOCS_ADMIN');
            case 'ROLE_ADMIN_VPN':
                return $this->security->isGranted('ROLE_DOCS_ADMIN_VPN');
            case 'ROLE_ADMIN_SCEP':
                return $this->security->isGranted('ROLE_DOCS_ADMIN_SCEP');
            case 'ROLE_SMARTEMS':
                return $this->security->isGranted('ROLE_DOCS_SMARTEMS');
            case 'ROLE_VPN':
                return $this->security->isGranted('ROLE_DOCS_VPN');
            default:
                return false;
        }
    }
}
