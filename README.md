# turboSMTP mailer

Provides turboSMTP integration for Symfony Mailer.

## Instalation

composer require emiliort/turbosmtp-mailer

## Transport Setup

Add custom trasport to config\services.yaml

    mailer.transport_factory.custom:
        class: Turbosmtp\TurbosmtpTransportFactory
        parent: mailer.transport_factory.abstract
        tags:
        - {name: mailer.transport_factory}

Configure MAILER_DSN enviroment variable:

    MAILER_DSN="turbosmtp://USERNAME:PASSWORD@default"

If you want to override the default host for a provider change default value. For example, using european turboSMTP servers:

    MAILER_DSN="turbosmtp://USERNAME:PASSWORD@api.eu.turbo-smtp.com"

## License

Autor: [emiliort](https://github.com/emiliort).
Licensed under the terms of the [MIT license](LICENSE).
