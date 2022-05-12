<?php
namespace Turbosmtp;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class TurbosmtpTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $password = $this->getPassword($dsn);
        $host = 'default' === $dsn->getHost() ? TurbosmtpApiTransport::DEFAULT_SERVER : $dsn->getHost();
        $port = $dsn->getPort();

        if ('turbosmtp+https' === $scheme || 'turbosmtp' === $scheme) {
            return (new TurbosmtpApiTransport($user, $password, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port);
        }

        throw new UnsupportedSchemeException($dsn, 'turbosmtp', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['turbosmtp', 'turbosmtp+https'];
    }
}