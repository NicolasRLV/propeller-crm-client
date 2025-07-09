<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class SendEnquiryApiTest extends TestCase
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
     * Test successful enquiry submission.
     *
     * @return void
     */
    public function testSuccessfulEnquirySubmission()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber/test-subscriber-id-1/enquiry' => Http::response([
                'enquiry' => ['id' => 'enquiry-id-1', 'message' => 'Test message'],
                'subscriber' => ['firstName' => 'Test', 'lastName' => 'User'],
            ], 200),
        ]);

        Artisan::call('crm:send-enquiry', [
            '--subscriber-id' => 'test-subscriber-id-1',
            '--message' => 'This is a test enquiry.',
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Enquiry submitted successfully for Test User. Enquiry ID: enquiry-id-1', $output);
    }

    /**
     * Test successful enquiry submission for a subscriber without first/last name.
     *
     * @return void
     */
    public function testSuccessfulEnquirySubmissionNoNameSubscriber()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber/test-subscriber-id-2/enquiry' => Http::response([
                'enquiry' => ['id' => 'enquiry-id-2', 'message' => 'Another test message'],
                'subscriber' => ['firstName' => null, 'lastName' => null],
            ], 200),
        ]);

        Artisan::call('crm:send-enquiry', [
            '--subscriber-id' => 'test-subscriber-id-2',
            '--message' => 'Another test enquiry.',
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Enquiry submitted successfully for subscriber. Enquiry ID: enquiry-id-2', $output);
    }

    /**
     * Test enquiry submission with validation error (missing message).
     *
     * @return void
     */
    public function testEnquirySubmissionValidationErrorMissingMessage()
    {
        Artisan::call('crm:send-enquiry', [
            '--subscriber-id' => 'test-subscriber-id-3',
            '--message' => '', // Missing message
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Error: message: The message field is required.', $output);
    }

    /**
     * Test enquiry submission with unauthorized error.
     *
     * @return void
     */
    public function testEnquirySubmissionUnauthorizedError()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber/test-subscriber-id-4/enquiry' => Http::response([
                'error' => 'UNAUTHORIZED',
                'message' => 'You must provide an access token to access this resource.',
            ], 401),
        ]);

        Artisan::call('crm:send-enquiry', [
            '--subscriber-id' => 'test-subscriber-id-4',
            '--message' => 'Unauthorized enquiry.',
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Error: Invalid access token. Please verify your CRM_API_TOKEN.', $output);
    }

    /**
     * Test enquiry submission with subscriber not found error.
     *
     * @return void
     */
    public function testEnquirySubmissionSubscriberNotFound()
    {
        Http::fake([
            config('services.crm_api.url') . '/api/subscriber/non-existent-id/enquiry' => Http::response([
                'error' => 'NOT_FOUND',
                'message' => 'Subscriber not found.',
            ], 404),
        ]);

        Artisan::call('crm:send-enquiry', [
            '--subscriber-id' => 'non-existent-id',
            '--message' => 'Enquiry for non-existent subscriber.',
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Error: Subscriber not found. Please verify the subscriber ID.', $output);
    }
}
