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

?>


<div class="wrap">

    <h1>
        <?php _e('Collectme Settings', 'collectme') ?>
    </h1>

    <?php foreach($causes as $cause): ?>
    <h2><?php echo $cause->name ?></h2>
    <form method="post">
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row">
                    <?php _e('Goals', 'collectme') ?>
                    <p style="font-weight: normal; color: red;"><?php _e('Caution: Changing the goals after launching the cause will break the user experience.') ?></p>
                </th>
                <td>
                <?php foreach($settings->getObjectives($cause->uuid) as $key => $objective): ?>
                    <h4><?php echo $objective['name']?></h4>
                    <div>
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
                        <label for="objective[<?php echo $key ?>][objective]"><?php _e('Number of signatures to collect.', 'collectme') ?></label>
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
                        <p class="description"><?php _e('Fully qualified URL to the image. Clear to reset to default.', 'collectme') ?></p>
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
                        <label for="objective[<?php echo $key ?>][hot]"><?php _e('Add "hot" ribbon to goal.', 'collectme') ?></label>
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
                        <label for="objective[<?php echo $key ?>][enabled]"><?php _e('Goal is active (visible and selectable)', 'collectme') ?></label>
                    </div>
                <?php endforeach; ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('Overrides', 'collectme') ?>
                </th>

                <td>
                <?php
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

                        foreach($strings as $string){
                            if (empty($stringTemplate->getReferences()->toArray())) {
                                // Skip plugin doc block
                                continue;
                            }

                            $stringKey = base64_encode($string);
                            $rows = strlen($string) > 50 ? 3 : 1;
                            $references = array_reduce(
                                array_keys($stringTemplate->getReferences()->toArray()),
                                static fn($carry, $filePath) => $filePath.':'.implode(',', $stringTemplate->getReferences()->toArray()[$filePath]).' ',
                                ''
                            );
                            $comments = implode('<br>',
                                array_filter(
                                        $stringTemplate->getExtractedComments()->toArray(),
                                        static fn($comment) => 0 === stripos($comment, 'translators:')
                                )
                            );
                            $contextDesc = $stringTemplate->getContext() ? 'Context: '.$stringTemplate->getContext() : '';
                            $contextKey = $stringTemplate->getContext() ?? $defaultContext;
                            $override = $translator->getOverride($cause->uuid, $string, $stringTemplate->getContext()) ?? '';

                            echo '<div style="margin-bottom: 1em;">';
                            echo "<p><label for='override[$contextKey][$stringKey]'>".esc_html($string)."</label> <span style='margin-top: 0; font-size: 0.875em; color: #888888; line-height: 1em;'>".esc_html($contextDesc)."</span></p>";
                            echo "<textarea id='override[$contextKey][$stringKey]' name='override[$contextKey][$stringKey]' rows='$rows' cols='50' class='large-text' placeholder='".esc_attr__($string, 'collectme')."'>$override</textarea>";
                            echo "<p style='margin-top: 0; font-size: 0.875em; line-height: 1em;'>".esc_html($comments)."</p>";
                            echo "<p style='margin-top: 0; font-size: 0.875em; color: #888888; line-height: 1em; float: right; margin-right: 0.5rem;'>$references</p>";
                            echo '</div>';
                        }
                    }
                ?>
                </td>
            </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="hidden" name="_wpnonce" value="<?php echo $nonce ?>">
            <input type="hidden" name="cause" value="<?php echo $cause->uuid ?>">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php printf(esc_attr__('Save %s settings', 'collectme'), $cause->name) ?>">
        </p>
    </form>
    <?php endforeach; ?>
</div>