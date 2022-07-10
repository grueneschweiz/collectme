<?php

declare(strict_types=1);

/**
 * @var Gettext\Translations $stringTemplates
 * @var string $nonce
 * @var Cause[] $causes
 * @var Translator $translator
 */

use Collectme\Misc\Translator;
use Collectme\Model\Entities\Cause;

?>


<div class="wrap">

    <h1>
        <?php _e('Collectme Settings', 'collectme') ?>
    </h1>

    <?php foreach($causes as $cause): ?>
    <form method="post">
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row">
                    <?php printf(__('Overrides for %s', 'collectme'), $cause->name) ?>
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
                            $key = base64_encode($string);
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
                            $context = $stringTemplate->getContext() ? '['.$stringTemplate->getContext().']' : '';
                            $override = $translator->getOverride($cause->uuid, $string, $stringTemplate->getContext()) ?? '';

                            echo '<div style="margin-bottom: 1em;">';
                            echo "<p><label for='override[$key]$context'>".esc_html($string)."</label> <span style='margin-top: 0; font-size: 0.875em; color: #888888; line-height: 1em;'>$context</span></p>";
                            echo "<textarea name='override[$key]$context' rows='$rows' cols='50' class='large-text' placeholder='".esc_attr__($string, 'collectme')."'>$override</textarea>";
                            echo "<p style='margin-top: 0; font-size: 0.875em; line-height: 1em;'>$comments</p>";
                            echo "<p style='margin-top: 0; font-size: 0.875em; color: #888888; line-height: 1em;'>$references</p>";
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
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save', 'collectme') ?>">
        </p>
    </form>
    <?php endforeach; ?>
</div>