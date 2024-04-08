
A very, very, very basic handler script for certbot in “handler” mode.  It
It connects to [INWX](https://www.inwx.de/) and relies on the [INWX PHP
Client](https://github.com/inwx/php-client), since that is the only one
able to handle MFA tokens.

“Works for me”, but doesn't catch errors and hasn't been tested in any
serious manner. Use at your own risk.

Authentication information is provided to the script by setting the
following environment variables:

- `CERTBOT_INWX_USERNAME`
- `CERTBOT_INWX_PASSWORD`
- `CERTBOT_INWX_MFATOKEN`

