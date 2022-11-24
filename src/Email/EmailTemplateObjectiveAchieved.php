<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateObjectiveAchieved implements EmailTemplate
{

    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool */
        /* Translators: Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        return __(
            "Hello {{firstName}}

Yesterday you've reached your signature collection goal. Fantastic, congratulations. 

Upgrade your collection goal now and become a true chief collector: LINK 

Do you have any questions or would you like some hints? Get in touch with me!

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
            "Kudos, {{firstName}}: You made it!
Congrats, {{firstName}}: Goal achieved.",
            'collectme'
        );
    }
}