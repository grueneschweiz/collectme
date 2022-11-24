<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Misc\Settings;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;

class LoginEmail implements Mailable
{
    public User $user;
    public PersistentSession $session;
    public string $appUrl;
    public string $causeUuid;

    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    public function getToAddr(): string
    {
        return $this->user->email;
    }

    public function getSubject(): string
    {
        /* Translators: Override with "Login link for the cause" */
        return _x('Login link', 'Email subject', 'collectme');
    }

    public function getMessage(): string
    {
        $fromName = $this->settings->getEmailConfig($this->causeUuid)['fromName'];

        $loginButton = $this->getButton(
            __('Confirm login', 'collectme'),
            $this->getActivationUrl(),
        );

        /* Translators: %1$s will be replaced with the users first name, %2$s the login link, %3$s the sender name. Keep the empty lines. */
        $template = __(
            'Hello %1$s

Click the following link to login:

%2$s

Please note, that you will be logged-in in the browser of the device you triggered this email. This may be different from the browser that opens when you click this link.

Feel free to reply to this email, if you have any questions.
Yours sincerely,
%3$s',
            'collectme'
        );

        return wpautop(
            sprintf($template, $this->user->firstName, $loginButton, $fromName)
        );
    }

    private function getButton(string $text, string $link): string
    {
        /** @noinspection HtmlDeprecatedAttribute */
        /** @noinspection HtmlUnknownTarget */
        $buttonTemplate = <<<'EOF'
<table border="0" cellpadding="0" cellspacing="0" width="100%%" style="min-width: 100%%;border-collapse: collapse;mso-table-lspace: 0;mso-table-rspace: 0;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;">
    <tbody>
        <tr>
            <td style="padding: 0 18px 18px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;" valign="top" align="center">
                <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate !important;border-radius: 4px;background-color: #E6007E;mso-table-lspace: 0;mso-table-rspace: 0;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;">
                    <tbody>
                        <tr>
                            <td align="center" valign="middle" style="font-family: Arial, sans-serif;font-size: 16px;padding: 18px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;">
                                <a title="%1$s" href="%2$s" target="_blank" style="font-weight: bold;letter-spacing: normal;line-height: 100%%;text-align: center;text-decoration: none;color: #FFFFFF;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;display: block;">%1$s</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
EOF;

        return sprintf($buttonTemplate, $text, $link);
    }

    private function getActivationUrl(): string
    {
        return add_query_arg(
            [
                'action' => 'activate',
                'session' => $this->session->uuid,
                'token' => $this->session->activationSecret,
            ],
            $this->appUrl
        );
    }
}