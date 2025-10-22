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

namespace App\Service\Helper;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

trait ViewHandlerTrait
{
    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    #[Required]
    public function setViewHandler(ViewHandlerInterface $viewHandler)
    {
        $this->viewHandler = $viewHandler;
    }

    protected function getAnnotatedView(Request $request, $data): View
    {
        $view = new View($data);

        $viewAnnotation = $request->attributes->get('_template');

        if ($viewAnnotation->getVars()) {
            $view->setVars($viewAnnotation->getVars());
        }
        if (null !== $viewAnnotation->getStatusCode() && (null === $view->getStatusCode() || Response::HTTP_OK === $view->getStatusCode())) {
            $view->setStatusCode($viewAnnotation->getStatusCode());
        }

        $context = $view->getContext();
        if ($viewAnnotation->getSerializerGroups()) {
            if (null === $context->getGroups()) {
                $context->setGroups($viewAnnotation->getSerializerGroups());
            } else {
                $context->setGroups(array_merge($context->getGroups(), $viewAnnotation->getSerializerGroups()));
            }
        }

        if ($viewAnnotation->getSerializerEnableMaxDepthChecks()) {
            $context->setMaxDepth(0, false);
        }

        if (true === $viewAnnotation->getSerializerEnableMaxDepthChecks()) {
            $context->enableMaxDepth();
        } elseif (false === $viewAnnotation->getSerializerEnableMaxDepthChecks()) {
            $context->disableMaxDepth();
        }

        return $view;
    }
}
