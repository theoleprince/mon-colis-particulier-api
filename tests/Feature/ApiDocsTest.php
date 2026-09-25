<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    public function test_docs_are_closed_outside_local_by_default(): void
    {
        config(['moncolis.docs_public' => false]);

        $this->get('/docs/api.json')->assertForbidden();
        $this->get('/docs/swagger')->assertForbidden();
    }

    public function test_docs_can_be_opened_with_the_flag(): void
    {
        config(['moncolis.docs_public' => true]);

        $this->get('/docs/swagger')->assertOk()->assertSee('swagger-ui', false);

        $spec = $this->getJson('/docs/api.json')->assertOk()->json();

        $this->assertSame('MonColis Particulier API', $spec['info']['title']);
        $this->assertArrayHasKey('/login_check', $spec['paths']);
        $this->assertArrayHasKey('/profile/places', $spec['paths']);
        $this->assertArrayHasKey('clientAppCode', $spec['components']['securitySchemes']);
        // Public route: only the client-app-code header, no bearer token.
        $this->assertSame([['clientAppCode' => []]], $spec['paths']['/login_check']['post']['security']);
    }
}
