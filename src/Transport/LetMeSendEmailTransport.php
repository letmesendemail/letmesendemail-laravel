<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel\Transport;

use LetMeSendEmail\Exceptions\ApiException;
use LetMeSendEmail\Laravel\LetMeSendEmail;
use LetMeSendEmail\Requests\Attachment;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class LetMeSendEmailTransport extends AbstractTransport
{
    private const STRUCTURAL_HEADERS = [
        'from', 'to', 'cc', 'bcc', 'reply-to', 'subject',
        'content-type', 'mime-version', 'date', 'message-id',
        'sender', 'return-path',
    ];

    private LetMeSendEmail $client;

    public function __construct(LetMeSendEmail $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    public function __toString(): string
    {
        return 'letmesendemail';
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        if (!$original instanceof Email) {
            throw new TransportException('Only Email messages are supported.');
        }

        $payload = $this->convertToPayload($original);

        if ($payload['from'] === '') {
            throw new TransportException('Email must have a sender.');
        }

        if ($payload['to'] === []) {
            throw new TransportException('Email must have at least one recipient.');
        }

        try {
            $response = $this->client->emails()->send(
                from: $payload['from'],
                to: $payload['to'],
                subject: $payload['subject'],
                html: $payload['html'] ?? null,
                text: $payload['text'] ?? null,
                cc: $payload['cc'] ?? null,
                bcc: $payload['bcc'] ?? null,
                replyTo: $payload['reply_to'] ?? null,
                headers: $payload['headers'] ?? null,
                attachments: $payload['attachments'] ?? null,
                idempotencyKey: $payload['idempotency_key'] ?? null,
            );

            $message->setMessageId($response->getId());
            $original->getHeaders()->addTextHeader('X-LetMeSendEmail-ID', $response->getId());
        } catch (ApiException $e) {
            throw new TransportException(
                $e->getMessage(),
                $e->getHttpStatus() ?? 0,
                $e,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function convertToPayload(Email $email): array
    {
        $fromAddresses = $this->formatAddresses($email->getFrom());
        $payload = [
            'from' => $fromAddresses[0] ?? '',
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject() ?? '',
        ];

        if ($email->getHtmlBody() !== null) {
            $payload['html'] = $email->getHtmlBody();
        }

        if ($email->getTextBody() !== null) {
            $payload['text'] = $email->getTextBody();
        }

        $cc = $email->getCc();
        if ($cc !== []) {
            $payload['cc'] = $this->formatAddresses($cc);
        }

        $bcc = $email->getBcc();
        if ($bcc !== []) {
            $payload['bcc'] = $this->formatAddresses($bcc);
        }

        $replyTo = $email->getReplyTo();
        if ($replyTo !== []) {
            $payload['reply_to'] = $this->formatAddresses($replyTo);
        }

        $headers = [];
        $idempotencyKey = null;
        foreach ($email->getHeaders()->all() as $header) {
            $name = $header->getName();
            $lower = strtolower($name);

            if (in_array($lower, self::STRUCTURAL_HEADERS, true)) {
                continue;
            }

            if ($lower === 'idempotency-key') {
                $idempotencyKey = $header->getBodyAsString();
                continue;
            }

            if ($lower === 'x-letmesendemail-id') {
                continue;
            }

            $headers[$name] = $header->getBodyAsString();
        }

        if ($headers !== []) {
            $payload['headers'] = $headers;
        }

        if ($idempotencyKey !== null) {
            $payload['idempotency_key'] = $idempotencyKey;
        }

        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = Attachment::fromContent(
                name: $attachment->getFilename() ?? 'attachment',
                content: base64_encode($attachment->getBody()),
                contentId: $attachment->getContentId(),
                contentDisposition: $attachment->getDisposition() ?: 'attachment',
            );
        }

        if ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    /**
     * @param Address[] $addresses
     * @return string[]
     */
    private function formatAddresses(array $addresses): array
    {
        return array_map(function (Address $address): string {
            $name = $address->getName();
            return $name !== ''
                ? "{$name} <{$address->getAddress()}>"
                : $address->getAddress();
        }, $addresses);
    }
}
