<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class AddSubscriberApiTest extends TestCase
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
     * Test successful subscriber creation with minimal data.
     *
     * @return void
     */
    public function testSuccessfulSubscriberCreationMinimalData()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber' => Http::response([
                'subscriber' => [
                    'id' => 'test-id-1',
                    'emailAddress' => 'test1@example.com',
                    'marketingConsent' => true,
                    'firstName' => null,
                    'lastName' => null,
                    'dateOfBirth' => '2000-01-01',
                    'lists' => [],
                ],
            ], 200),
        ]);

        Artisan::call('crm:add-subscriber', [
            '--email' => 'test1@example.com',
            '--dob' => '2000-01-01',
            '--marketing-consent' => true,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Subscriber created successfully. ID: test-id-1', $output);
    }

    /**
     * Test successful subscriber creation with all data including lists.
     *
     * @return void
     */
    public function testSuccessfulSubscriberCreationAllDataWithLists()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber' => Http::response([
                'subscriber' => [
                    'id' => 'test-id-2',
                    'emailAddress' => 'test2@example.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'dateOfBirth' => '1990-05-15',
                    'marketingConsent' => true,
                    'lists' => [
                        ['id' => 'list-london', 'name' => 'London'],
                        ['id' => 'list-birmingham', 'name' => 'Birmingham'],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('crm:add-subscriber', [
            '--email' => 'test2@example.com',
            '--first-name' => 'John',
            '--last-name' => 'Doe',
            '--dob' => '1990-05-15',
            '--marketing-consent' => true,
            '--lists' => 'list-london,list-birmingham',
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('John Doe created successfully. ID: test-id-2', $output);
    }

    /**
     * Test subscriber creation with validation error (missing email).
     *
     * @return void
     */
    public function testSubscriberCreationValidationErrorMissingEmail()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber' => Http::response([
                'error' => 'VALIDATION_ERROR',
                'message' => 'Some fields failed validation.',
                'fields' => [
                    ['field' => 'emailAddress', 'message' => 'The email address field is required.'],
                ],
            ], 400),
        ]);

        Artisan::call('crm:add-subscriber', [
            '--dob' => '2000-01-01',
            '--marketing-consent' => true,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Error: emailAddress: The email address field is required.', $output);
    }

    /**
     * Test subscriber creation with validation error (under 18).
     *
     * @return void
     */
    public function testSubscriberCreationValidationErrorUnder18()
    {
        // Calculate a DOB that is less than 18 years ago
        $dobUnder18 = now()->subYears(17)->format('Y-m-d');

        Artisan::call('crm:add-subscriber', [
            '--email' => 'minor@example.com',
            '--dob' => $dobUnder18,
            '--marketing-consent' => true,
        ]);

        $output = trim(Artisan::output());
        $this->assertMatchesRegularExpression('/Error: dateOfBirth: The date of birth field must be a date before or equal to \d{4}-\d{2}-\d{2}\.$/', $output);
    }

    /**
     * Test subscriber creation with unauthorized error.
     *
     * @return void
     */
    public function testSubscriberCreationUnauthorizedError()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber' => Http::response([
                'error' => 'UNAUTHORIZED',
                'message' => 'You must provide an access token to access this resource.',
            ], 401),
        ]);

        Artisan::call('crm:add-subscriber', [
            '--email' => 'unauthorized@example.com',
            '--dob' => '2000-01-01',
            '--marketing-consent' => true,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Error: Invalid access token. Please verify your CRM_API_TOKEN.', $output);
    }
}
