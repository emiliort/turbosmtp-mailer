# turboSMTP mailer

Provides turboSMTP integration for Symfony Mailer.

## Transport Setup

Configure MAILER_DSN enviroment variable:

`MAILER_DSN="turbosmtp://USERNAME:PASSWORD@default"`

If you want to override the default host for a provider change default value. For example, using european turboSMTP servers:

`MAILER_DSN="turbosmtp://USERNAME:PASSWORD@api.eu.turbo-smtp.com"`

## License

Autor: [emiliort](https://github.com/emiliort).
Licensed under the terms of the [MIT license](LICENSE).
