<?php

$view = $view ?? [];
$isShort = $view['isShort'] ?? false;
$shortcodeName = $view['shortcodeName'] ?? '';
$viewId = $view['viewId'] ?? '';
$isSingle = $view['isSingle'] ?? false;
$description = $view['description'] ?? '';
$idArgument = $view['idArgument'] ?? '';
$entryName = $view['entryName'] ?? '';
$typeName = $view['typeName'] ?? '';

$type = $isShort ?
    'short' :
    'full';
?>
<av-shortcodes class="av-shortcodes av-shortcodes--type--<?php
echo esc_attr($type) ?>">
    <span class='av-shortcodes__code av-shortcodes__code--type--short'>[<?php
        echo esc_html($shortcodeName) ?> name="<?php
        echo esc_html($entryName) ?>" <?php
        echo esc_html($idArgument) ?>="<?php
        echo esc_html($viewId) ?>"]</span>

    <?php
    if (!$isShort) { ?>
        <button class="av-shortcodes__copy-button button button-primary button-large"
                data-target=".av-shortcodes__code--type--short"><?php
            echo __('Copy to clipboard', 'acf-views'); ?>
        </button>
        <span><?php
            // don't escape, contains HTML
            echo $description ?></span>
        <?php
    } ?>
</av-shortcodes>