<?php

declare(strict_types=1);

namespace Collectme\Email;

class EmailTemplateObjectiveAchievedFinal implements EmailTemplate
{

    public function getBodyTemplate(): string
    {
        /* Translators: Override LINK with the link to the tool */
        /* Translators: Available placeholders: {{firstName}} {{lastName}} {{userEmail}} {{groupName}} {{groupSignatureCount}} {{groupSignatureObjective}} */
        return __(
            "Wow {{firstName}},

Yesterday you've reached the highest signature collection goal. This is incredible, thank you so much for your awesome commitment. Celebrate your success.

People like you are really making a change. You can push it even further, if you keep collecting.

An enormous thank you for your commitment 💚
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
            "Incredible, {{firstName}}!",
            'collectme'
        );
    }
}