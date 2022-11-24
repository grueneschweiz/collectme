<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateObjectiveRaised implements EmailTemplate
{

    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool. Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}}. {{groupSignatureObjective}} resolves to the new, upgraded goal. */
        return __(
            "Hello {{firstName}}

Yesterday you've upgraded your signature collection goal. Fantastic, congratulations. You're a true pro!

Keep collecting. Every signature gets you one step closer to your new goal of {{groupSignatureObjective}} signatures: LINK 

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
        /* Translators: {{groupSignatureObjective}} resolves to the new, upgraded goal. */
        return __(
            "Goal upgraded! Awesome, {{firstName}}!
Good choice, {{firstName}}!
{{groupSignatureObjective}} for the win",
            'collectme'
        );
    }
}