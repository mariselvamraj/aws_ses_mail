<?php

namespace App\Http\Controllers;

use App\Models\QueuedEmail;
use Aws\Sqs\SqsClient;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailQueueController extends Controller
{
    protected $sqsClient;
    protected $sesClient;
    protected $queueUrl;
    protected $sourceEmail;

    public function __construct()
    {
        // SQS Client Initialization
        $this->sqsClient = new SqsClient([
            'region' => config('services.sqs.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.sqs.key'),
                'secret' => config('services.sqs.secret'),
            ],
        ]);

        // SES Client Initialization
        $this->sesClient = new SesClient([
            'region' => config('services.ses.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);

        $this->queueUrl = config('services.sqs.queue_url'); // Queue URL from config
        $this->sourceEmail = env('AWS_SES_SOURCE_EMAIL');  // Source email from .env
    }

    /**
     * Display all queued messages.
     */
    public function index()
    {
        $messages = QueuedEmail::all(); // Retrieve all queued emails
        return view('email_queue', compact('messages'));
    }

    /**
     * Add email to database and AWS SQS.
     */
    public function addToQueue(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        // Save email to database
        $queuedEmail = QueuedEmail::create($validated);

        // Push email to SQS queue
        try {
            $this->sqsClient->sendMessage([
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => json_encode([
                    'email' => $validated['email'],
                    'subject' => $validated['subject'],
                    'body' => $validated['body'],
                    'db_id' => $queuedEmail->id, // Include database ID for reference
                ]),
            ]);
            $this->processQueue();

            return redirect()->route('email-queue')->with('success', 'Email added to queue successfully!');
           
        } catch (AwsException $e) {
            // Log the error and return a failure message
            Log::error('SQS Error: ' . $e->getMessage());
            return redirect()->route('email-queue')->with('error', "Failed to add email to SQS: {$e->getMessage()}");
        }
    }

    /**
     * Send email using AWS SES and delete from database.
     */
    public function processQueue()
    {
        $allMessagesProcessed = true;

        try {
            while (true) {
                // Receive messages from the queue
                $result = $this->sqsClient->receiveMessage([
                    'QueueUrl' => $this->queueUrl,
                    'MaxNumberOfMessages' => 3, // Adjust as needed
                    'WaitTimeSeconds' => 5,    // Long polling
                ]);

                // Check if there are messages
                if (!empty($result['Messages'])) {
                    foreach ($result['Messages'] as $message) {
                        $emailData = json_decode($message['Body'], true);

                        try {
                            // Send email via SES
                            $this->sesClient->sendEmail([
                                'Source' => $this->sourceEmail,
                                'Destination' => [
                                    'ToAddresses' => [$emailData['email']],
                                ],
                                'Message' => [
                                    'Subject' => [
                                        'Data' => "Hello, {$emailData['subject']}",
                                    ],
                                    'Body' => [
                                        'Text' => [
                                            'Data' => "Hi {$emailData['body']}",
                                        ],
                                    ],
                                ],
                            ]);

                            Log::info("Email sent to: {$emailData['email']}");

                            // Delete the message from the queue
                            $this->sqsClient->deleteMessage([
                                'QueueUrl' => $this->queueUrl,
                                'ReceiptHandle' => $message['ReceiptHandle'],
                            ]);
                        } catch (AwsException $e) {
                            $allMessagesProcessed = false;
                            Log::error("Error sending email to {$emailData['email']}: {$e->getMessage()}");
                        }
                    }
                } else {
                    // No messages left in the queue
                    break;
                }
            }

            if ($allMessagesProcessed) {
                return response()->json(['message' => 'All emails processed successfully'], 200);
            } else {
                return response()->json(['message' => 'Some emails failed to process'], 500);
            }
        } catch (AwsException $e) {
            Log::error("Error processing queue: {$e->getMessage()}");
            return response()->json(['error' => 'Failed to process the queue'], 500);
        }
    }


}
