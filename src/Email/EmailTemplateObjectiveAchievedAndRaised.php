<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateObjectiveAchievedAndRaised implements EmailTemplate
{

    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool */
        /* Translators: Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        /* Translators: {{groupSignatureObjective}} resolves to the new, upgraded goal. */
        return __(
            "Hello {{firstName}}

Yesterday you've reached your signature collection goal. Fantastic, congratulations. 

That you've already chosen to upgrade your goal makes us even happier. Kudos to you, {{firstName}}! Now it's time to keep at it and continue collecting. Enter more signatures and get one step closer to your new goal: LINK 

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
            "Kudos, {{firstName}}: You're awesome!
Congrats, {{firstName}}: You're a true chief collector.",
            'collectme'
        );
    }
}