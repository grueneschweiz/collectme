<?php

declare(strict_types=1);

return [
    'General' => [
        'Error' => [
            'unspecificTitle' => __('Nooo... 🙈', 'collectme'),
            'blameTheGoblins' => __('Some goblins blocked the data flow. Please try again.', 'collectme'),
            'blameTheDevTitle' => __('Shame on me!', 'collectme'),
            'blameTheDev' => __('I must have made a terrible mistake. Please send me a screenshot of this error message: {email}', 'collectme'),
            'tryAgain' => __('Try again', 'collectme'),
            'unauthenticated' => __('Not authenticated. Please login.', 'collectme'),
            'invalidData' => __('Invalid data.', 'collectme'),
            'invalidFields' => __('Please double check the following fields before resubmitting the form: {fields}', 'collectme'),
        ],
    ],
    'HomeView' => [
        'MyContribution' => [
            /* Translators: Override with "My contribution to the cause" */
            'title' => __('My contribution', 'collectme'),
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
                /* Translators: Override with "You pledged {count} signatures for the cause on {date}." */
                'goal' => __('You pledged {count} signatures on {date}.', 'collectme'),
                'setGoalBtn' => __('Set Goal', 'collectme'),
            ],

            'MyContributionStepCollected' => [
                'titleDone' => __('First signatures collected', 'collectme'),
                'titlePending' => __('Collect your fist signatures', 'collectme'),
                'collectedBtn' => __('Mark done', 'collectme'),
                'collectedMsg' => __('Glorious. Keep going...', 'collectme'),
            ],

            'MyContributionStepEntered' => [
                'title' => __('Register signatures', 'collectme'),
                'enterFirst' => __('Enter now', 'collectme'),
                'enterMoreMsg' => __('Enter more signatures in the next step.', 'collectme'),
            ],

            'MyContributionStepAchieved' => [
                'titleNone' => __('Achieve goal', 'collectme'),
                'titleSome' => __('Achieved {percent}% of goal', 'collectme'),
                'captionWip' => __("You've already collected and registered <strong>{count} out of {goal}</strong> signatures.", 'collectme'),
                'captionDone' => __("Congratulations! You've achieved your goal and <strong>collected {count}</strong> signatures.", 'collectme'),
                'captionPlaceholder' => __("Complete the previous steps, and you will see your achievements here.", 'collectme'),
                /* Translators: Override with "The cause gives thanks!" */
                'thank' => __('Thank you!', 'collectme'),
                'registerSignaturesBtn' => __('Register signatures', 'collectme'),
                'registerMoreSignaturesBtn' => __('Register more signatures', 'collectme'),
                'upgradeObjectiveBtn' => __('Upgrade goal', 'collectme'),
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
            'submitByline' => __("You'll receive an e-mail with a link that signs you in. Keep this window open.", 'collectme'),
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
            /* Translators: Override with "My Goal for the Cause" */
            'title' => __('My Goal', 'collectme'),
            /* Translators: Override with "Every signature strengthens our force. <strong>Choose your collection target</strong> and contribute to the cause." */
            'intro' => __('Every signature strengthens our force. <strong>Choose your collection target</strong> and contribute.', 'collectme'),
            'ribbonHot' => __('Hot', 'collectme'),
            'ribbonDone' => __('Achieved', 'collectme'),
            'ribbonSelected' => __('Selected', 'collectme'),

            'TheObjectiveSetterCard' => [
                'subline' => __('I promise {count} signatures.', 'collectme'),
                'saving' => __('Saving...', 'collectme'),
            ],
        ],

        'TheSignatureAdder' => [
            'title' => __('Register signatures', 'collectme'),
            'intro' => __("Enter the number of <strong>new signatures</strong> you've collected. They will be added to the already registered signatures.", 'collectme'),
            'input' => __('Number of new signatures', 'collectme'),
            'helpText' => __("Number of recent collected signatures, that haven't been registered yet.", 'collectme'),
            'invalid' => __('Invalid number.', 'collectme'),
            'submit' => __('Register Signatures', 'collectme'),
            'saving' => __('Saving...', 'collectme'),
            'saved' => __('{count} signatures added.', 'collectme'),
            'back' => __('Back without registering signatures', 'collectme'),
        ],
    ],
];