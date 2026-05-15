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

use FOS\RestBundle\Controller\Annotations\View as ViewAnnotation;
use FOS\RestBundle\FOSRestBundle;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

trait ViewHandlerTrait
{
    protected ViewHandlerInterface $viewHandler;

    #[Required]
    public function setViewHandler(ViewHandlerInterface $viewHandler)
    {
        $this->viewHandler = $viewHandler;
    }

    /**
     * Creates View based on $request and $data.
     *
     * Simulates FOS\RestBundle\EventListener\ViewResponseListener::onKernelView() behavior
     */
    protected function getAnnotatedView(Request $request, $data): View
    {
        $view = new View($data);

        /** @var ViewAnnotation|null $configuration */
        $configuration = $request->attributes->get(FOSRestBundle::VIEW_ATTRIBUTE);

        if ($configuration instanceof ViewAnnotation) {
            if (null !== $configuration->getStatusCode() && (null === $view->getStatusCode() || Response::HTTP_OK === $view->getStatusCode())) {
                $view->setStatusCode($configuration->getStatusCode());
            }

            $context = $view->getContext();
            if ($configuration->getSerializerGroups()) {
                if (null === $context->getGroups()) {
                    $context->setGroups($configuration->getSerializerGroups());
                } else {
                    $context->setGroups(array_merge($context->getGroups(), $configuration->getSerializerGroups()));
                }
            }
            if (true === $configuration->getSerializerEnableMaxDepthChecks()) {
                $context->enableMaxDepth();
            } elseif (false === $configuration->getSerializerEnableMaxDepthChecks()) {
                $context->disableMaxDepth();
            }
        }

        if (null === $view->getFormat()) {
            $view->setFormat($request->getRequestFormat());
        }

        return $view;
    }
}
