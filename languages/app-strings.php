<?php

declare(strict_types=1);

return [
    'HomeView' => [
        'MyContribution' => [
            'title' => __('My contribution to the cause', 'collectme'),
            'body' => __('Please sign-in to enter your personal signatures.', 'collectme')
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
        ]
    ],
];