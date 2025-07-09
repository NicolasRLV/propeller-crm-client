<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SendEnquiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:send-enquiry
        {--subscriber-id= : Subscriber ID (required)}
        {--message= : Enquiry message (required)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit an enquiry for a subscriber in the Propeller CRM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Prepare data from command options
        $data = [
            'subscriberId' => $this->option('subscriber-id'),
            'message' => $this->option('message'),
        ];

        $validator = Validator::make($data, [
            'subscriberId' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->error("Error: {$field}: {$message}");
                }
            }
            return 1;
        }

        try {
            // Send POST request to the CRM API with bearer token authentication
            $response = Http::withToken(config('services.crm_api.token'))
                ->post(config('services.crm_api.url') . "/api/subscriber/{$data['subscriberId']}/enquiry", [
                    'message' => $data['message'],
                ]);

            if ($response->successful()) {
                $enquiry = $response->json()['enquiry'];
                $subscriber = $response->json()['subscriber'];
                $name = trim("{$subscriber['firstName']} {$subscriber['lastName']}") ?: 'subscriber';
                $this->info("Enquiry submitted successfully for {$name}. Enquiry ID: {$enquiry['id']}");
            } else {
                // Handle API errors
                $jsonResponse = $response->json();
                if (is_null($jsonResponse)) {
                    logger()->error('API Error: Non-JSON response or empty body', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $this->error("Error: The API returned an unexpected response (Status: {$response->status()}).");
                } else {
                    logger()->error('API Error Response:', ['response' => $jsonResponse]);
                    $this->error($this->formatError($jsonResponse));
                }
            }
        } catch (\Exception $e) {
            $this->error("Error: Failed to connect to the server: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    /**
     * Formats API error responses into simplified messages.
     *
     * @param array $error The error response from the API.
     * @return string The formatted error message.
     */
    protected function formatError($error)
    {
        $message = $error['message'] ?? 'An unknown error occurred.';
        $errorCode = $error['error'] ?? 'UNKNOWN';

        return match ($errorCode) {
            'VALIDATION_ERROR' => 'Error: ' . implode('; ', array_map(
                fn($field) => "{$field['field']}: {$field['message']}",
                $error['fields'] ?? []
            )),
            'UNAUTHORIZED' => 'Error: Invalid access token. Please verify your CRM_API_TOKEN.',
            'ACCESS_DENIED' => 'Error: Access denied. Please check your permissions.',
            'NOT_FOUND' => 'Error: Subscriber not found. Please verify the subscriber ID.',
            default => "Error: {$message}",
        };
    }
}
