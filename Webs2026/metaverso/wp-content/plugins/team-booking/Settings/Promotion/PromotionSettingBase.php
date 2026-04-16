<?php

namespace VSHM\Settings\Promotion;

use VSHM\Providers\Promotions;
use VSHM\Settings\PropertyBase;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic service setting.
 */
abstract class PromotionSettingBase extends PropertyBase
{
    public const CONTEXT = 'promotions';

    public static function subscribe(): void
    {
        parent::subscribe();

        add_filter('vshm_default_promotion_settings', static function ($defaults, $slug) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID ] = static::default(NULL);
            }

            return $defaults;
        }, 10, 2);
    }

    public static function onBeforeGet($resourceId): void
    {
        if (!vshm()->settings->resourceExists(self::CONTEXT, $resourceId)) {
            $promotion = Promotions::provideBy(['id' => $resourceId], TRUE);
            if ($promotion) {
                vshm()->settings->hydrateResource(
                    $promotion,
                    self::CONTEXT,
                    $resourceId
                );
            }
        }
    }
}
