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

use App\Service\UploadManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends AbstractController
{
    #[Route('/web/tus/upload', name: 'app_api_upload_post')]
    #[Route('/web/tus/upload/{token?}', name: 'app_api_upload_token', requirements: ['token' => '.+'])]
    public function uploadAction(Request $request)
    {
        $tusServer = UploadManager::getTusServer($request->get('token'));
        $response = $tusServer->serve();

        return $response;
    }
}
