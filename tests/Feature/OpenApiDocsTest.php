<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class OpenApiDocsTest extends TestCase
{
    public function test_swagger_ui_is_available(): void
    {
        $this->get('/docs')->assertOk();

        $this->assertStringContainsString(
            'swagger-ui-dist',
            file_get_contents(public_path('docs/index.html'))
        );
    }

    public function test_openapi_spec_is_available(): void
    {
        $this->get('/docs/openapi.yaml')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.oai.openapi+yaml');

        $this->assertStringContainsString(
            'openapi: 3.0.3',
            file_get_contents(base_path('openapi/openapi.yaml'))
        );
    }
}
