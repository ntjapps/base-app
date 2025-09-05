<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait LogContext
{
    /**
     * Build standard log context array.
     *
     * @param  mixed  $user
     */
    protected function getLogContext(?Request $request = null, $user = null, array $extras = []): array
    {
        $context = [
            'userId' => $user?->id ?? null,
            'userName' => $user?->name ?? null,
        ];

        if ($request) {
            try {
                $context['route'] = $request->route()?->getName();
                $context['ip'] = $request->ip();
            } catch (\Throwable $e) {
                // ignore if request doesn't support these methods
            }
        }

        return array_merge($context, $extras);
    }
}
