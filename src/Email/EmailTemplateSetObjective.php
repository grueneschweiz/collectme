<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateSetObjective implements EmailTemplate
{
    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool. Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}}. {{groupSignatureObjective}} resolves to the new, upgraded goal. */
        return __(
            "Hello {{firstName}}

Some time ago you discovered our signature collection tool. This made me happy. Now it's time to come back and set yourself a signature collection goal: LINK

You're then invited to start collecting. Download the signature sheet and the most important arguments here: LINK

Do you have any questions or would you like some hints? Get in touch with me!

A big thank you for your commitment 💚
Happy collecting,
Your Team",
            'collectme'
        );
    }

    public function getSubjectTemplate(): string
    {
        /* Translators: One email subject per line. If more than one is provided, the mailer chooses one randomly. Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}}. {{groupSignatureObjective}} resolves to the new, upgraded goal. */
        return __(
            "Choose your collection target now!
How many signatures will you collect, {{firstName}}?",
            'collectme'
        );
    }
}