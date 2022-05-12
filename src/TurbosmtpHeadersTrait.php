<?php

namespace Turbosmtp;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;


trait TurbosmtpHeadersTrait
{
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Message) {
            $this->addTurbosmtpHeaders($message);
        }

        return parent::send($message, $envelope);
    }

    private function addTurbosmtpHeaders(Message $message): void
    {
        $headers = $message->getHeaders();
        $metadata = [];
        $tags = [];

        foreach ($headers->all() as $name => $header) {
            if ($header instanceof TagHeader) {
                $tags[] = $header->getValue();
                $headers->remove($name);
            } elseif ($header instanceof MetadataHeader) {
                $metadata[$header->getKey()] = $header->getValue();
                $headers->remove($name);
            }
        }

        if ($tags) {
            $headers->addTextHeader('X-MC-Tags', implode(',', $tags));
        }

        if ($metadata) {
            $headers->addTextHeader('X-MC-Metadata', json_encode($metadata));
        }
    }
}