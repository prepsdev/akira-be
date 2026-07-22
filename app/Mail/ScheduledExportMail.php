<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $frequency;
    public $categories;
    public $documentCount;
    public $dateRange;
    public $filePath;
    public $format;

    public function __construct($frequency, $categories, $documentCount, $dateRange, $filePath, $format)
    {
        $this->frequency = $frequency;
        $this->categories = $categories;
        $this->documentCount = $documentCount;
        $this->dateRange = $dateRange;
        $this->filePath = $filePath;
        $this->format = $format;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OCR Export Report - ' . ucfirst($this->frequency),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scheduled-export',
            with: [
                'frequency' => $this->frequency,
                'categories' => $this->categories,
                'documentCount' => $this->documentCount,
                'dateRange' => $this->dateRange,
            ]
        );
    }

    public function attachments(): array
    {
        if ($this->filePath && file_exists(storage_path('app/' . $this->filePath))) {
            return [
                Attachment::fromPath(storage_path('app/' . $this->filePath))
                    ->as('export.' . $this->format)
                    ->withMime('application/' . ($this->format === 'xlsx' ? 'vnd.openxmlformats-officedocument.spreadsheetml.sheet' : $this->format))
            ];
        }

        return [];
    }
}
