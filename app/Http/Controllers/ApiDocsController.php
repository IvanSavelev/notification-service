<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ApiDocsController extends Controller
{
    public function swaggerUi(): BinaryFileResponse
    {
        return response()->file(public_path('docs/index.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function openApiSpec(): BinaryFileResponse
    {
        return response()->file(base_path('openapi/openapi.yaml'), [
            'Content-Type' => 'application/vnd.oai.openapi+yaml',
        ]);
    }
}
