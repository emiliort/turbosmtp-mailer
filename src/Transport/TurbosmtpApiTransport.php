<?php
namespace Turbosmtp\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Email;

class TurbosmtpApiTransport extends AbstractApiTransport
{
    use TurbosmtpHeadersTrait;

    public const DEFAULT_SERVER = 'api.turbo-smtp.com';

    public function __construct(string $user, string $password, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->authuser = $user;
        $this->authpass = $password;

        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        return sprintf('turbosmtp+https://%s', $this->getEndpoint());
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {

        $recipients=$this->buildRecipients($email, $envelope);
        $options = [
                    'authuser' => $this->authuser,
                    'authpass' => $this->authpass,
                    'from'=>$envelope->getSender()->getAddress(),                
                    'to' => $recipients['to'],
                    'cc'=> $recipients['cc'],
                    'bcc'=> $recipients['bcc'],
                    'subject'=>$email->getSubject(),
                    'content'=>$email->getTextBody(),
                    'html_content'=>$email->getHtmlBody(),
                    'custom_headers'=>$this->buildHeaders($email),
                    // 'mime_raw'
                    'attachments'=>$this->buildAttachments($email),
                ];

        $this->getLogger()->debug('TurbosmtpApiTransport send', $options);

        $response = $this->client->request('POST', 'https://'.$this->getEndpoint().'/api/v2/mail/send', $options);

        try {
            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw new HttpTransportException('Unable to send an email: '.$response->getContent(false).sprintf(' (code %d).', $statusCode), $response);
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the remote Turbosmtp server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            if ('error' === ($result['message'] ?? false)) {
                $this->getLogger()->error('TurbosmtpApiTransport error response', $result['errors']);
                throw new HttpTransportException('Unable to send an email. Errors: '.implode(' ; ',$result['errors']), $response ) ;
            }
            throw new HttpTransportException('Unable to send an email.', $response);
        }

        return $response;
    }


    private function getEndpoint(): ?string
    {
        return ($this->host ?: self::DEFAULT_SERVER).($this->port ? ':'.$this->port : '');
    }

    protected function buildRecipients(Email $email, Envelope $envelope): array
    {
        $recipients = [];
        foreach ($envelope->getRecipients() as $recipient) {
            $type = 'to';
            if (\in_array($recipient, $email->getBcc(), true)) {
                $type = 'bcc';
            } elseif (\in_array($recipient, $email->getCc(), true)) {
                $type = 'cc';
            }
            $recipients[$type][]=$recipient->getAddress();
        }
        return [
            'to'=>implode(',',$recipients['to']),
            'cc'=>implode(',',$recipients['cc']),
            'bcc'=>implode(',',$recipients['bcc']),
        ];
    }

    private function buildAttachments(Email $email): string
    {
        $result = [];
        foreach ($email->getAttachments() as $attachment) {
            $file = $attachment->getPreparedHeaders()->get('Content-Disposition');
            $type = $attachment->getPreparedHeaders()->get('Content-Type');
            $result[] = [
                'name' => $file->getParameter('filename'),
                'type' => $type->getValue(),
                'content' => base64_encode($attachment->getBody()),
            ];
        }

        return json_encode($result);
    }

    private function buildHeaders (Email $email): string {
        $headers= $email->getPreparedHeaders()->toArray();
        return json_encode($headers);
    }


}