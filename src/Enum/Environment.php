<?php
declare(strict_types=1);

namespace ViteHelper\Enum;

/**
 * The plugin environment mode
 */
enum Environment: string
{
    case PRODUCTION = 'prod';
    case DEVELOPMENT = 'dev';
    case FROM_DETECTOR = 'detector';
}
