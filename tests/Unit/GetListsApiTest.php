<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

class GetListsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure the .env file is loaded for tests
        if (file_exists(base_path('propeller-crm-client/.env'))) {
            \Dotenv\Dotenv::createImmutable(base_path('propeller-crm-client/'))->load();
        }
    }

    /**
     * Test successful retrieval of lists.
     *
     * @return void
     */
    public function testSuccessfulListsRetrieval()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/lists' => Http::response([
                'lists' => [
                    ['id' => 'list-1', 'name' => 'List One'],
                    ['id' => 'list-2', 'name' => 'List Two'],
                ],
            ], 200),
        ]);

        Artisan::call('crm:get-lists');

        $output = Artisan::output();
        $this->assertStringContainsString('Available Subscriber Lists:', $output);
        $this->assertStringContainsString('Name: List One, ID: list-1', $output);
        $this->assertStringContainsString('Name: List Two, ID: list-2', $output);
    }

    /**
     * Test handling of no lists found.
     *
     * @return void
     */
    public function testNoListsFound()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/lists' => Http::response([
                'lists' => [],
            ], 200),
        ]);

        Artisan::call('crm:get-lists');

        $output = Artisan::output();
        $this->assertStringContainsString('No lists found.', $output);
    }

    /**
     * Test handling of unauthorized error.
     *
     * @return void
     */
    public function testUnauthorizedError()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/lists' => Http::response([
                'error' => 'UNAUTHORIZED',
                'message' => 'You must provide an access token to access this resource.',
            ], 401),
        ]);

        Artisan::call('crm:get-lists');

        $output = Artisan::output();
        $this->assertStringContainsString('Error: Invalid access token. Please verify your CRM_API_TOKEN.', $output);
    }

    /**
     * Test handling of network/connection error.
     *
     * @return void
     */
    public function testNetworkConnectionError()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/lists' => function () {
                throw new ConnectException('Connection refused', new Request('GET', 'test'));
            },
        ]);

        Artisan::call('crm:get-lists');

        $output = Artisan::output();
        $this->assertStringContainsString('Error: Failed to connect to the server:', $output);
    }

    /**
     * Test handling of generic API error.
     *
     * @return void
     */
    public function testGenericApiError()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/lists' => Http::response([
                'error' => 'UNKNOWN_ERROR',
                'message' => 'Something went wrong on the server.',
            ], 500),
        ]);

        Artisan::call('crm:get-lists');

        $output = Artisan::output();
        $this->assertStringContainsString('Error: Something went wrong on the server.', $output);
    }
}
