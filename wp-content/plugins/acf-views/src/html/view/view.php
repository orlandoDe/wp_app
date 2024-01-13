<?php

$view = $view ?? [];
$content = $view['content'] ?? '';
$bemName = $view['bemName'] ?? '';
$tag = $view['tag'] ?? '';

$newLine = "\r\n";

// not necessary if the bemName is defined
$idClass = 'acf-view' === $bemName ?
    ' ' . sprintf('%s--id--{{ _view.id }}', esc_html($bemName)) :
    '';

printf(
    "<%s class=\"{{ _view.classes }}%s %s--object-id--{{ _view.object_id }}\">",
    esc_html($tag),
    esc_html($bemName . $idClass),
    esc_html($bemName)
);
echo esc_html($newLine);
// no escaping for $content, because it's an HTML code (of other things, that have escaped variables)
echo $content;
printf("</%s>", esc_html($tag));
echo esc_html($newLine);

