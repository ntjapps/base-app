<script setup lang="ts">
import { AxiosError } from 'axios';

// eslint-disable-next-line no-undef
const toast = useToast();

export type ToastDisplay = {
    // Accept legacy shapes and raw Axios errors at runtime
    toastDisplay: (detailData: toastData | unknown) => void;
};

type Severity = 'success' | 'info' | 'warning' | 'warn' | 'error' | undefined;

type toastData = {
    severity: Severity;
    title?: string;
    summary?: string; // tolerate AppAxios' key
    detail?: string;
    response?: unknown;
    icon?: string | undefined;
};

const normalizeSeverity = (s: Severity): 'success' | 'info' | 'warning' | 'error' | undefined => {
    if (s === 'warn') return 'warning';
    return s as 'success' | 'info' | 'warning' | 'error' | undefined;
};

const isAxiosErrorLike = (val: unknown): val is AxiosError => {
    const o = val as Record<string, unknown> | null;
    return !!o && typeof o === 'object' && ('isAxiosError' in o || 'response' in o);
};

// Main entry point used across the app
const toastDisplay = (input: toastData | unknown) => {
    // If a raw Axios error was passed directly
    if (isAxiosErrorLike(input)) {
        handleAxiosError(input as AxiosError);
        return;
    }

    const detailData = input as toastData;

    // If severity is error and an Axios error is provided under response, use error handler
    if (
        detailData.severity === 'error' &&
        detailData.response &&
        isAxiosErrorLike(detailData.response)
    ) {
        handleAxiosError(detailData.response as AxiosError);
        return;
    }

    const color = normalizeSeverity(detailData.severity);
    const title = detailData.title ?? detailData.summary ?? 'Info';
    const description = detailData.detail ?? '';

    toast.add({
        color,
        title,
        description,
        icon: detailData.icon ?? 'i-lucide-bell-ring',
    });
};

type ErrorPayload = {
    title?: string;
    message?: string;
    errors?: Record<string, string[] | string>;
};

const handleAxiosError = (error: AxiosError) => {
    const status = error.response?.status;
    // Prefer response.data; if absent, use response object itself as payload
    const resp = error.response as unknown;
    let payload: unknown;
    if (resp && typeof resp === 'object' && 'data' in (resp as Record<string, unknown>)) {
        payload = (resp as Record<string, unknown>).data as unknown;
    } else {
        payload = resp;
    }

    // Unwrap payload to the innermost { message, errors } if it is nested under .response.data
    let data: Partial<ErrorPayload> | undefined;
    if (typeof payload === 'string') {
        // Try to parse JSON string first; fallback to plain message
        try {
            const parsed = JSON.parse(payload) as unknown;
            if (parsed && typeof parsed === 'object') {
                const pobj = parsed as Record<string, unknown>;
                data = {
                    message:
                        typeof pobj.message === 'string' ? (pobj.message as string) : undefined,
                    errors:
                        (pobj.errors as Record<string, string[] | string> | undefined) ?? undefined,
                    title: typeof pobj.title === 'string' ? (pobj.title as string) : undefined,
                };
            } else {
                data = { message: payload };
            }
        } catch {
            data = { message: payload };
        }
    } else if (payload && typeof payload === 'object') {
        // If payload is an AxiosError-like with .response.data, use that inner data
        if ('response' in (payload as Record<string, unknown>)) {
            const innerResp = (payload as unknown as { response?: { data?: unknown } }).response;
            if (innerResp && 'data' in innerResp) {
                payload = innerResp.data as unknown;
            }
        }

        const obj = payload as Record<string, unknown>;
        // If payload itself looks like Laravel validation {message, errors}
        const message = typeof obj.message === 'string' ? (obj.message as string) : undefined;
        const errors = obj.errors as Record<string, string[] | string> | undefined;
        const title = typeof obj.title === 'string' ? (obj.title as string) : undefined;
        if (message || errors || title) {
            data = { message, errors, title };
        } else {
            data = payload as Partial<ErrorPayload>;
        }
    } else {
        data = undefined;
    }

    if (typeof error.response === 'undefined') {
        toast.add({
            color: 'error',
            title: 'Unknown Error',
            description: 'Please contact the administrator',
            icon: 'i-lucide-ban',
        });
        return;
    }

    // Laravel 422 validation (single consolidated toast)
    if (status === 422 || (typeof error.message === 'string' && error.message.includes('422'))) {
        const title = data?.message ?? 'Validation Error';
        let description = 'The given data was invalid.';
        if (data?.errors && typeof data.errors === 'object') {
            const allMessages: string[] = [];
            Object.values(data.errors).forEach((value) => {
                const messages = Array.isArray(value) ? value : [String(value)];
                allMessages.push(...messages);
            });
            // Deduplicate and join
            const unique = Array.from(new Set(allMessages));
            if (unique.length > 0) description = unique.join('\n');
        } else if (data?.message) {
            description = data.message;
        }
        toast.add({ color: 'error', title, description, icon: 'i-lucide-ban' });
        return;
    }

    if (status === 500) {
        toast.add({
            color: 'error',
            title: 'Server Error',
            description: 'Please contact the administrator',
            icon: 'i-lucide-ban',
        });
    } else if (status === 401) {
        toast.add({
            color: 'error',
            title: 'Unauthorized',
            description: 'Action not authorized.',
            icon: 'i-lucide-ban',
        });
    } else if (status === 403) {
        toast.add({
            color: 'error',
            title: 'Forbidden',
            description: 'Access denied.',
            icon: 'i-lucide-ban',
        });
    } else if (status === 404) {
        toast.add({
            color: 'error',
            title: 'Not Found',
            description: 'Resource not found.',
            icon: 'i-lucide-ban',
        });
    } else if (!data?.errors) {
        toast.add({
            color: 'error',
            title: data?.title ?? 'Unknown Error',
            description:
                data?.message ?? `Please contact the administrator, status code: ${status}`,
            icon: 'i-lucide-ban',
        });
    } else {
        Object.values(data.errors).forEach((value) => {
            const objVal = value as Array<string>;
            toast.add({
                color: 'error',
                title: data.message ?? 'Validation Error',
                description: objVal.toString(),
                icon: 'i-lucide-ban',
            });
        });
    }
};

defineExpose({
    toastDisplay,
});
</script>

<template>
    <slot />
</template>
