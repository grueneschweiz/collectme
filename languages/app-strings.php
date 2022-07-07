<?php

declare(strict_types=1);

return [
    'General' => [
        'Error' => [
            'unspecificTitle' => __('Nooo... 🙈', 'collectme'),
            'blameTheGoblins' => __('Some goblins blocked the data flow. Please try again.', 'collectme'),
            'tryAgain' => __('Try again', 'collectme'),
        ],
    ],
    'HomeView' => [
        'MyContribution' => [
            'title' => __('My contribution to the cause', 'collectme'), // todo: overwrite
            'singInMsg' => __('Please sign-in to enter your personal signatures.', 'collectme'),
            'signInBtn' => __('Sign in', 'collectme'),
            'noPasswordRequired' => __('No password required', 'collectme'),

            'MyContributionStepConnected' => [
                'connected' => __('Successfully connected', 'collectme'),
                'hello' => __('Hello {firstName}', 'collectme'),
            ],

            'MyContributionStepObjective' => [
                'goalSet' => __('Goal set', 'collectme'),
                'setGoal' => __('Set a goal', 'collectme'),
                'goal' => __('You pledged {count} signatures on {date}.', 'collectme'), // todo: overwrite
                'setGoalBtn' => __('Set Goal', 'collectme'),
            ],
        ],
        'TheLogin' => [
            'title' => __('Full Access', 'collectme'),
            'loginMsg' => __(
                'Sign-in with your email address (no password required) to enter signatures and see the current state of the collection.',
                'collectme'
            ),
            'emailLabel' => __('E-Mail', 'collectme'),
            'emailHelpText' => __('The same e-mail address where you receive our newsletter.', 'collectme'),
            'emailInvalid' => __('E-Mail address is not valid.', 'collectme'),
            'firstNameLabel' => __('First Name', 'collectme'),
            'firstNameInvalid' => __('First name not valid.', 'collectme'),
            'lastNameLabel' => __('Last Name', 'collectme'),
            'lastNameInvalid' => __('Last name not valid.', 'collectme'),
            'signIn' => __('Sign in', 'collectme'),
            'submitByline' => __("You'll receive an e-mail with a link that signs you in.", 'collectme'),
        ],
        'ActivityFeed' => [
            'title' => __('Activity Feed', 'collectme'),
            'noActivity' => __('No activity yet.', 'collectme'),
            'pledge' => __('{firstName} promised {count} signatures.', 'collectme'),
            'personalSignature' => __('{firstName} collected {count} signatures.', 'collectme'),
            'organizationSignature' => __('{count} signatures were entered for {organization}.', 'collectme'),
            'personalGoalAchieved' => __('{firstName} just achieved its goal of {count} signatures.', 'collectme'),
            'personalGoalRaised' => __('{firstName} is going to collect {count} signatures.', 'collectme'),
            'loadMore' => __('Load more', 'collectme'),
        ],
        'TheObjectiveSetter' => [
            'title' => __('My Goal for the Cause', 'collectme'), // todo: overwrite
            'intro' => __('Every signature strengthens our force. <strong>Choose your collection target</strong> and contribute to the cause.', 'collectme'), // todo: overwrite
            'ribbonHot' => __('Hot', 'collectme'),
            'ribbonDone' => __('Achieved', 'collectme'),
            'ribbonSelected' => __('Selected', 'collectme'),

            'TheObjectiveSetterCard' => [
                'subline' => __('I promise {count} signatures.', 'collectme'),
                'saving' => __('Saving...', 'collectme'),
            ],
        ],
    ],
];