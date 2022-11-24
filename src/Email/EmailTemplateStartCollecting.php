<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateStartCollecting implements EmailTemplate
{

    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool. Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        return __(
            "Hello {{firstName}}
            
Some time ago you promised {{groupSignatureObjective}} signatures, thank you so much!

Now it's time to start collecting! Do you have any questions or would you like some hints? Get in touch with me!

Have you already discovered our great collection tool? Enter your collected signatures now and get one step closer to your goal: LINK

A big thank you for your commitment 💚
Happy collecting,
Your Team",
            'collectme'
        );
    }

    public function getSubjectTemplate(): string
    {
        /* Translators: One email subject per line. If more than one is provided, the mailer chooses one randomly. */
        /* Translators: Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        return __(
            "Collect and enter signatures now!
{{firstName}}, enter signatures now - it's worth it!
{{firstName}}, only with you we can make a change!",
            'collectme'
        );
    }
}