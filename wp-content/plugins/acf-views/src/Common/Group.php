<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common;

use org\wplake\acf_views\vendors\LightSource\AcfGroups\AcfGroup;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;

defined('ABSPATH') || exit;

abstract class Group extends AcfGroup
{
    const GROUP_NAME_PREFIX = 'local_' . ViewsCpt::NAME . '_';

    // to keep back compatibility
    const FIELD_NAME_PREFIX = '';
    const TEXT_DOMAIN = 'acf-views';

    public function resetProFields(?Group $originInstance): void
    {
        $fieldsInfo = static::getFieldsInfo();

        foreach ($fieldsInfo as $fieldInfo) {
            $fieldName = $fieldInfo->getName();
            $isPro = (bool)($fieldInfo->getArguments()['a-pro'] ?? false);
            $newValue = $this->{$fieldName};
            /**
             * @var mixed $originValue
             */
            $originValue = $originInstance ?
                $originInstance->{$fieldName} :
                ($fieldInfo->getArguments()['default_value'] ?? null);

            $isGroup = $newValue instanceof self;
            $isGroupArray = is_array($newValue) &&
                count($newValue) > 0 &&
                $newValue[0] instanceof self;
            $isPlainType = !$isGroup &&
                !$isGroupArray;

            if (!$isPro &&
                $isPlainType) {
                continue;
            }

            // default value is not available
            if (is_null($originValue) &&
                $isPlainType) {
                continue;
            }

            if ($isPro &&
                !$isGroup &&
                !$isGroupArray) {
                $this->{$fieldName} = $originValue;
                continue;
            }

            if ($isGroup) {
                /**
                 * @var Group $newValue
                 */
                $newValue->resetProFields($originValue);
                continue;
            }

            // group array

            $itemsCount = count($newValue);

            /**
             * @var Group[] $newValue
             */
            for ($i = 0; $i < $itemsCount; $i++) {
                $newValue[$i]->resetProFields($originValue[$i] ?? null);
            }
        }
    }
}
