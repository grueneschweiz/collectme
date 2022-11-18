<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateObjectiveAdded implements EmailTemplate
{

    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool */
        /* Translators: Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        return __(
            "Hello {{firstName}}

Yesterday you've added a signature collection goal. Fantastic, congratulations. This is the first step to making a difference. Now it's time to start collecting. Enter signatures and get one step closer to your goal: LINK 

Reminder: You can download the signature sheet and the most important arguments here: LINK

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
            "You're making a change, {{firstName}}!",
            'collectme'
        );
    }
}