<?php

declare(strict_types=1);

/**
 * @var Gettext\Translations $stringTemplates
 * @var string $nonce
 * @var Cause[] $causes
 * @var Translator $translator
 * @var string $defaultContext
 * @var Settings $settings
 */

use Collectme\Misc\Settings;
use Collectme\Misc\Translator;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumMessageKey;

?>


<div class="wrap">

    <h1>
        <?php _e('Collectme Settings', 'collectme') ?>
    </h1>

    <h2><?php _e('Causes', 'collectme') ?></h2>

    <table class="form-table" role="presentation">
        <tbody>
        <?php foreach ($causes as $cause): ?>
            <tr>
                <th scope="row"><?php echo esc_html($cause->name); ?></th>
                <td>
                    <p>
                        <?php _e('Shortcode', 'collectme') ?>
                    </p>
                    <code>[collectme name="<?php echo esc_attr($cause->name); ?>" causeuuid="<?php echo esc_attr(
                            $cause->uuid
                        ); ?>"]</code>
                    <p class="description">
                        <?php _e(
                            'Paste the shortcode into the post or page where you want the collectme app to run.',
                            'collectme'
                        ) ?>
                    </p>
                    <form method="post" onsubmit="return confirm('<?php printf(
                        esc_attr__(
                            'Do you really want to delete %s and all its associated data? Only do this if the cause is long past or was never launched.',
                            'collectme'
                        ),
                        $cause->name
                    ) ?>');">
                        <input type="hidden" name="_wpnonce" value="<?php echo $nonce ?>">
                        <input type="hidden" name="delete-cause" value="<?php echo $cause->uuid ?>">
                        <p><input type="submit" name="submit" id="submit"
                                  style="color: #b32d2e; text-decoration: underline; cursor: pointer; padding: 0; border: none; background: none;"
                                  value="<?php printf(esc_attr__('Delete %s', 'collectme'), $cause->name) ?>"
                            ></p>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th scope="row"><?php _e('Create new cause', 'collectme'); ?></th>
            <td>
                <form method="post">
                    <input type="hidden" name="_wpnonce" value="<?php echo $nonce ?>">
                    <label for="create-cause"><?php _e('Name', 'collectme') ?></label><br>
                    <input type="text" class="regular-text" id="create-cause" name="create-cause" value=""
                           maxlength="45" minlength="2">
                    <p class="description"><?php _e(
                            'The name is only for internal use and not visible to the public. Keep it short.',
                            'collectme'
                        ) ?></p>
                    <p><input type="submit" name="submit" id="submit" class="button button-primary"
                              value="<?php esc_attr_e('Create cause', 'collectme') ?>"></p>
                </form>
            </td>
        </tr>
        </tbody>
    </table>

    <?php foreach ($causes as $cause): ?>
        <h2><?php echo $cause->name ?></h2>
        <form method="post">
            <table class="form-table" role="presentation">
                <tbody>

                <tr>
                    <th scope="row"><?php echo esc_html($cause->name) . ' ' . __('Signatures', 'collectme') ?></th>
                    <td>
                        <div>
                            <label for="signatures[objective]">
                                <?php _e('Number of signatures needed', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="signatures[objective]"
                                    id="signatures[objective]"
                                    type="number"
                                    class="regular-text"
                                    max="10000000"
                                    min="0"
                                    step="1"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getSignatureSettings($cause->uuid)['objective']) ?>"
                            >
                            <p class="description"><?php _e(
                                    "The number of signatures to be registered. For an initiative this would typically be 105'000.",
                                    'collectme'
                                ) ?></p>
                        </div>
                        <div style="margin-top: 1rem;">
                            <label for="signatures[offset]">
                                <?php _e('Additional signatures collected', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="signatures[offset]"
                                    id="signatures[offset]"
                                    type="number"
                                    class="regular-text"
                                    max="10000000"
                                    min="0"
                                    step="1"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getSignatureSettings($cause->uuid)['offset']) ?>"
                            >
                            <p class="description"><?php _e(
                                    "This number is added to the sum of the registered signatures. Typically you'd enter the number of signatures received by mailings (and thus not yet registered in this tool) here.",
                                    'collectme'
                                ) ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php echo esc_html($cause->name) . ' ' . __('Pledges', 'collectme') ?></th>
                    <td>
                        <div>
                            <label for="pledges[objective]">
                                <?php _e('Number of signatures to be pledged', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="pledges[objective]"
                                    id="pledges[objective]"
                                    type="number"
                                    class="regular-text"
                                    max="10000000"
                                    min="0"
                                    step="1"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getPledgeSettings($cause->uuid)['objective']) ?>"
                            >
                            <p class="description"><?php _e(
                                    "The number of signatures you'd expect people to pledge for. It is possibly equal to the number of signatures needed.",
                                    'collectme'
                                ) ?></p>
                        </div>
                        <div style="margin-top: 1rem;">
                            <label for="pledges[offset]">
                                <?php _e('Additional pledges', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="pledges[offset]"
                                    id="pledges[offset]"
                                    type="number"
                                    class="regular-text"
                                    max="10000000"
                                    min="0"
                                    step="1"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getPledgeSettings($cause->uuid)['offset']) ?>"
                            >
                            <p class="description"><?php _e(
                                    "This number is added to the total of the pledges. Typically you'd enter the number of signatures expected to collect by mailings or other organizations.",
                                    'collectme'
                                ) ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html($cause->name) . ' ' . __('Timings', 'collectme') ?>
                    </th>
                    <td>
                        <div>
                            <label for="timings[start]">
                                <?php _e('Collection start date', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="timings[start]"
                                    id="timings[start]"
                                    type="date"
                                    class="date"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getTimings($cause->uuid)['start']?->format('Y-m-d')) ?>"
                            >
                        </div>
                        <div style="margin-top: 1rem;">
                            <label for="timings[stop]">
                                <?php _e('Collection end date', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="timings[stop]"
                                    id="timings[stop]"
                                    type="date"
                                    class="date"
                                    maxlength="80"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getTimings($cause->uuid)['stop']?->format('Y-m-d')) ?>"
                            >
                            <p class="description"><?php _e(
                                    'The last day you plan to collect signatures.',
                                    'collectme'
                                ) ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html($cause->name) . ' ' . __('Scheduled E-Mails', 'collectme') ?>
                    </th>
                    <td>
                        <div>
                            <label for="mail_delays[<?php echo EnumMessageKey::COLLECTION_REMINDER->value ?>]">
                                <?php _e('Collection reminder', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="mail_delays[<?php echo EnumMessageKey::COLLECTION_REMINDER->value ?>]"
                                    id="mail_delays[<?php echo EnumMessageKey::COLLECTION_REMINDER->value ?>]"
                                    type="number"
                                    class="num"
                                    step="1"
                                    min="1"
                                    max="999"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getMailDelays($cause->uuid)[EnumMessageKey::COLLECTION_REMINDER->value]?->format('%d')) ?>"
                            > <?php _e('days', 'collectme') ?>
                            <p class="description"><?php _e(
                                    'Number of days after which the user receives an email that reminds him to collect and enter signatures.',
                                    'collectme'
                                ) ?>
                                <?php _e('Resent every X days.', 'collectme') ?>
                                <?php _e(
                                    'No reminders are sent if left blank.',
                                    'collectme'
                                ) ?>
                            </p>
                        </div>
                        <div style="margin-top: 1rem;">
                            <label for="mail_delays[<?php echo EnumMessageKey::OBJECTIVE_CHANGE->value ?>]">
                                <?php _e('Goal added / updated / achieved', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="mail_delays[<?php echo EnumMessageKey::OBJECTIVE_CHANGE->value ?>]"
                                    id="mail_delays[<?php echo EnumMessageKey::OBJECTIVE_CHANGE->value ?>]"
                                    type="number"
                                    class="num"
                                    step="1"
                                    min="0"
                                    max="999"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getMailDelays($cause->uuid)[EnumMessageKey::OBJECTIVE_CHANGE->value]?->format('%h')) ?>"
                            > <?php _e('hours', 'collectme') ?>
                            <p class="description"><?php _e(
                                    "Number of hours after which the user receives a thank you email for reaching and/or updating its goal.",
                                    'collectme'
                                ) ?>
                                <?php _e(
                                    'No email is sent if left blank.',
                                    'collectme'
                                ) ?>
                            </p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html($cause->name) . ' ' . __('E-Mail', 'collectme') ?>
                    </th>
                    <td>
                        <div>
                            <label for="email[fromName]">
                                <?php _e('Sender name', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="email[fromName]"
                                    id="email[fromName]"
                                    type="text"
                                    class="regular-text"
                                    maxlength="80"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getEmailConfig($cause->uuid)['fromName']) ?>"
                            >
                            <p class="description"><?php _e(
                                    'The sender name for emails sent by this cause (e.g. login emails).',
                                    'collectme'
                                ) ?></p>
                        </div>
                        <div style="margin-top: 1rem;">
                            <label for="email[fromAddress]">
                                <?php _e('Sender address', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="email[fromAddress]"
                                    id="email[fromAddress]"
                                    type="email"
                                    class="regular-text"
                                    maxlength="80"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getEmailConfig($cause->uuid)['fromAddress']) ?>"
                            >
                            <p class="description"><?php _e(
                                    'The sender address for emails sent by this cause (e.g. login emails).',
                                    'collectme'
                                ) ?></p>
                        </div>
                        <div style="margin-top: 1rem;">
                            <label for="email[replyToAddress]">
                                <?php _e('Reply-To address', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="email[replyToAddress]"
                                    id="email[replyToAddress]"
                                    type="email"
                                    class="regular-text"
                                    maxlength="80"
                                    autocomplete="off"
                                    value="<?php echo esc_attr($settings->getEmailConfig($cause->uuid)['replyToAddress']) ?>"
                            >
                            <p class="description"><?php _e(
                                    'The destination address if people reply.',
                                    'collectme'
                                ) ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html($cause->name) . ' ' . __('Goals', 'collectme') ?>
                        <p style="font-weight: normal; color: red;"><?php _e(
                                'Caution: Changing the goals after launching the cause will break the user experience.',
                                'collectme'
                            ) ?></p>
                    </th>
                    <td>
                        <?php foreach ($settings->getObjectives($cause->uuid) as $key => $objective): ?>
                            <h4><?php echo $objective['name'] ?></h4>
                            <div style="margin-top: 1em;">
                                <p>
                                    <?php _e('Goal', 'collectme') ?>
                                </p>
                                <input
                                        name="objective[<?php echo $key ?>][objective]"
                                        id="objective[<?php echo $key ?>][objective]"
                                        type="number"
                                        min="1"
                                        max="1000000"
                                        value="<?php echo esc_attr($objective['objective']) ?>"
                                        class="small-text"
                                >
                                <label for="objective[<?php echo $key ?>][objective]"><?php _e(
                                        'Number of signatures to collect.',
                                        'collectme'
                                    ) ?></label>
                            </div>
                            <div style="margin-top: 1em;">
                                <label for="objective[<?php echo $key ?>][img]">
                                    <?php _e('Image URL', 'collectme') ?>
                                </label>
                                <br>
                                <input
                                        name="objective[<?php echo $key ?>][img]"
                                        id="objective[<?php echo $key ?>][img]"
                                        type="text"
                                        class="large-text"
                                        value="<?php echo esc_attr($objective['img']) ?>"
                                >
                                <p class="description"><?php _e(
                                        'Fully qualified URL to the image. Clear to reset to default.',
                                        'collectme'
                                    ) ?></p>
                            </div>
                            <div style="margin-top: 1em;">
                                <p>
                                    <?php _e('Hot', 'collectme') ?>
                                </p>
                                <input
                                        name="objective[<?php echo $key ?>][hot]"
                                        id="objective[<?php echo $key ?>][hot]"
                                        type="checkbox"
                                        value="1"
                                    <?php echo $objective['hot'] ? 'checked="checked"' : '' ?>
                                >
                                <label for="objective[<?php echo $key ?>][hot]"><?php _e(
                                        'Add "hot" ribbon to goal.',
                                        'collectme'
                                    ) ?></label>
                            </div>
                            <div style="margin-top: 1em;">
                                <p>
                                    <?php _e('Active', 'collectme') ?>
                                </p>
                                <input
                                        name="objective[<?php echo $key ?>][enabled]"
                                        id="objective[<?php echo $key ?>][enabled]"
                                        type="checkbox"
                                        value="0"
                                    <?php echo $objective['enabled'] ? 'checked="checked"' : '' ?>
                                >
                                <label for="objective[<?php echo $key ?>][enabled]"><?php _e(
                                        'Goal is active (visible and selectable)',
                                        'collectme'
                                    ) ?></label>
                            </div>
                        <?php endforeach; ?>
                        <h4><?php _e('Default', 'collectme') ?></h4>
                        <p class="description"><?php _e(
                                'This is shown before any goal is selected.',
                                'collectme'
                            ) ?></p>
                        <div style="margin-top: 1em;">
                            <label for="defaultObjective[img]">
                                <?php _e('Image URL', 'collectme') ?>
                            </label>
                            <br>
                            <input
                                    name="defaultObjective[img]"
                                    id="defaultObjective[img]"
                                    type="text"
                                    class="large-text"
                                    value="<?php echo esc_attr($settings->getDefaultObjective($cause->uuid)['img']) ?>"
                            >
                            <p class="description"><?php _e(
                                    'Fully qualified URL to the image. Clear to reset to default.',
                                    'collectme'
                                ) ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Custom CSS', 'collectme') ?>
                    </th>
                    <td>
                        <textarea style="resize: both;" name="customCss" id="customCss-<?php echo $cause->uuid ?>"><?php echo $settings->getCustomCss($cause->uuid) ?></textarea>
                        <p class="description">
                            <label for="customCss-<?php echo $cause->uuid ?>"><?php _e('CSS entered here will be applied to every page this cause is displayed.', 'collectme') ?></label>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html($cause->name) . ' ' . __('Overrides', 'collectme') ?>
                    </th>

                    <td>
                        <?php
                        if (function_exists('pll_current_language') && pll_current_language('locale')) {
                            switch_to_locale(pll_current_language('locale'));
                        }

                        /** @var Gettext\Translation $stringTemplate */
                        foreach ($stringTemplates as $stringTemplate) {
                            if ($stringTemplate->isDisabled()) {
                                continue;
                            }

                            unset($strings);
                            $strings[] = $stringTemplate->getOriginal();
                            if ($stringTemplate->getPlural()) {
                                $strings[] = $stringTemplate->getPlural();
                            }

                            foreach ($strings as $string) {
                                if (empty($stringTemplate->getReferences()->toArray())) {
                                    // Skip plugin doc block
                                    continue;
                                }

                                $stringKey = base64_encode($string);
                                $rows = strlen($string) > 50 ? 3 : 1;
                                $references = array_reduce(
                                    array_keys($stringTemplate->getReferences()->toArray()),
                                    static fn($carry, $filePath) => $filePath . ':' . implode(
                                            ',',
                                            $stringTemplate->getReferences()->toArray()[$filePath]
                                        ) . ' ',
                                    ''
                                );
                                $comments = implode(
                                    '<br>',
                                    array_filter(
                                        $stringTemplate->getExtractedComments()->toArray(),
                                        static fn($comment) => 0 === stripos($comment, 'translators:')
                                    )
                                );
                                $contextDesc = $stringTemplate->getContext(
                                ) ? 'Context: ' . $stringTemplate->getContext() : '';
                                $contextKey = $stringTemplate->getContext() ?? $defaultContext;
                                $override = $translator->getOverride(
                                        $cause->uuid,
                                        $string,
                                        $stringTemplate->getContext()
                                    ) ?? '';
                                $default = esc_attr__($string, 'collectme');

                                echo '<div style="margin-bottom: 1em;">';
                                echo "<p><label for='override[$contextKey][$stringKey]'>" . esc_html(
                                        $string
                                    ) . "</label> <span style='margin-top: 0; font-size: 0.875em; color: #888888; line-height: 1em;'>" . esc_html(
                                        $contextDesc
                                    ) . "</span></p>";
                                echo "<textarea id='override[$contextKey][$stringKey]' name='override[$contextKey][$stringKey]' rows='$rows' cols='50' class='large-text' placeholder='$default'>$override</textarea>";
                                echo "<p style='margin-top: 0; font-size: 0.875em; line-height: 1em;'>" . esc_html(
                                        $comments
                                    ) . "</p>";
                                echo "<p style='margin-top: 0; font-size: 0.875em; color: #888888; line-height: 1em; float: right; margin-right: 0.5rem;'>$references</p>";
                                echo '</div>';
                            }
                        }

                        if (function_exists('pll_current_language') && pll_current_language('locale')) {
                            restore_previous_locale();
                        }
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="hidden" name="_wpnonce" value="<?php echo $nonce ?>">
                <input type="hidden" name="cause" value="<?php echo $cause->uuid ?>">
                <input type="submit" name="submit" id="submit" class="button button-primary"
                       value="<?php printf(esc_attr__('Save %s settings', 'collectme'), $cause->name) ?>">
            </p>
        </form>
    <?php endforeach; ?>
</div>