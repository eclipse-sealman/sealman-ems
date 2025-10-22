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

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    /**
     * * {any} allows any routing not defined in Symfony to be handled by React.
     * Priority allows it to be loaded last (-100 is just an arbitrary value).
     */
    #[Route('/{any}', requirements: ['any' => '.+'], priority: -100)]
    public function app(null|string $any = null): Response
    {
        return $this->render('app.html.twig');
    }
}
