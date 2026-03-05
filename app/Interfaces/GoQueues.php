<?php

namespace App\Interfaces;

/**
 * Centralized constants for Go worker queue names.
 * Use these constants when calling sendGoTask() to ensure consistency.
 */
interface GoQueues
{
    public const CELERY = 'celery';

    public const ADMIN = 'tasks.admin';

    public const WHATSAPP = 'tasks.whatsapp';

    public const LOGGER = 'tasks.logger';

    public const CACHE_INVALIDATION = 'tasks.cache.invalidation';

    public const NOTIFICATIONS = 'tasks.notifications';

    public const DEFAULT = 'tasks.default';
}
