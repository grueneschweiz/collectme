<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateContinueCollecting implements EmailTemplate
{

    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool. Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        return __(
            "Hello {{firstName}}
            
Some time ago you promised {{groupSignatureCount}} signatures, thank you so much! You've already discovered the collection tool and entered {{groupSignatureCount}} signatures.

Keep collecting and enter your new signatures now. Every signature gets you one step closer to your goal of {{groupSignatureObjective}} signatures: LINK 

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
        /* Translators: One email subject per line. If more than one is provided, the mailer chooses one randomly. Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        return __(
            "Continue collecting and entering signatures!
{{firstName}}, enter signatures now - it's worth it!
{{firstName}}, only with you we can make a change!",
            'collectme'
        );
    }
}