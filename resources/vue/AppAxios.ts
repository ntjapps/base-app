import axios from 'axios';
import type { AxiosInstance, AxiosResponse, AxiosError, AxiosRequestConfig } from 'axios';

// Types
interface ApiError {
    title: string;
    message: string;
}

interface ApiResponse<T = unknown> {
    title: string;
    message: string;
    data?: T;
}

interface LoginRequest {
    username: string;
    password: string;
    token: string;
}

interface LoginResponse {
    title: string;
    message: string;
}

interface ToastMessage {
    severity: 'success' | 'error' | 'info' | 'warn';
    summary: string;
    detail: string;
    response?: unknown;
}

// Common ID helpers
type IdLike = string | number;
type UserIdPayload = { id?: IdLike; user?: IdLike } & Record<string, unknown>;
type MessageIdPayload = { id?: IdLike; message?: IdLike } & Record<string, unknown>;

// Request options with toast control and typed params
type RequestOptions = Omit<AxiosRequestConfig, 'params'> & {
    noToast?: boolean;
    params?: Record<string, unknown>;
};

// Create axios instance with default config
const axiosInstance: AxiosInstance = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

// Type for toast display function
type ToastDisplayFn = (message: ToastMessage) => void;

export class ApiClient {
    private toast: ToastDisplayFn | null = null;
    private turnstileReset: (() => void) | null = null;
    // simple toast dedupe to avoid duplicate messages
    private lastToastSignature: string | null = null;
    private lastToastAt: number | null = null;

    // merge helper for params without losing caller-provided params
    private mergeOptions(
        options?: RequestOptions,
        params?: Record<string, unknown>,
    ): RequestOptions {
        const mergedParams = { ...(options?.params ?? {}), ...(params ?? {}) };
        return { ...(options ?? {}), params: mergedParams };
    }

    constructor(private readonly axios: AxiosInstance) {}

    // Method to set the toast display function
    setToastDisplay(fn: ToastDisplayFn) {
        this.toast = fn;
    }

    // Method to set the turnstile reset function
    setTurnstileReset(fn: () => void) {
        this.turnstileReset = fn;
    }

    // Helper method to display toast
    private showToast(message: ToastMessage) {
        if (!this.toast) return;

        // compute a simple signature for this message
        const sig = `${message.severity}::${message.summary}::${message.detail}`;
        const now = Date.now();

        // if same signature was shown recently (2 seconds), skip to prevent duplicates
        if (this.lastToastSignature === sig && this.lastToastAt && now - this.lastToastAt < 2000) {
            return;
        }

        this.lastToastSignature = sig;
        this.lastToastAt = now;

        this.toast(message);
    }

    // Helper method to display success toast when API returns standard shape
    private showToastIfPresent<T>(response: AxiosResponse<T>) {
        const data = response?.data as unknown as { title?: string; message?: string } | undefined;
        if (data && typeof data === 'object' && data.title && data.message) {
            this.showToast({
                severity: 'success',
                summary: String(data.title),
                detail: String(data.message),
            });
        }
    }

    // Helper method to handle API errors
    // Helper method to handle API errors
    private handleError(error: AxiosError<ApiError>) {
        const errorData = error.response?.data;

        // if request config requested no toast, skip showing error toast
        const reqConfig = (error.config || {}) as AxiosRequestConfig & { noToast?: boolean };
        if (!reqConfig.noToast) {
            this.showToast({
                severity: 'error',
                summary: errorData?.title || 'Error',
                detail: errorData?.message || 'An unexpected error occurred',
                response: error,
            });
        }

        // Reset turnstile if needed
        if (this.turnstileReset) {
            this.turnstileReset();
        }

        throw error;
    }

    // Generic request methods with error handling
    private async get<T>(
        url: string,
        config: AxiosRequestConfig & { noToast?: boolean } = {},
    ): Promise<AxiosResponse<T>> {
        try {
            const response = await this.axios.get<T>(url, config);
            if (!config.noToast) this.showToastIfPresent(response);
            return response;
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    private async post<T>(
        url: string,
        data = {},
        config: AxiosRequestConfig & { noToast?: boolean } = {},
    ): Promise<AxiosResponse<T>> {
        try {
            const response = await this.axios.post<T>(url, data, config);
            if (!config.noToast) this.showToastIfPresent(response);
            return response;
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    // Public wrapper to allow arbitrary POST usage from legacy callers
    public async requestPost<T>(url: string, data = {}, config = {}): Promise<AxiosResponse<T>> {
        return this.post<T>(url, data, config);
    }

    private async put<T>(
        url: string,
        data = {},
        config: AxiosRequestConfig & { noToast?: boolean } = {},
    ): Promise<AxiosResponse<T>> {
        try {
            const response = await this.axios.put<T>(url, data, config);
            if (!config.noToast) this.showToastIfPresent(response);
            return response;
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    private async patch<T>(
        url: string,
        data = {},
        config: AxiosRequestConfig & { noToast?: boolean } = {},
    ): Promise<AxiosResponse<T>> {
        try {
            const response = await this.axios.patch<T>(url, data, config);
            if (!config.noToast) this.showToastIfPresent(response);
            return response;
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    private async delete<T>(
        url: string,
        config: AxiosRequestConfig & { noToast?: boolean } = {},
    ): Promise<AxiosResponse<T>> {
        try {
            const response = await this.axios.delete<T>(url, config);
            if (!config.noToast) this.showToastIfPresent(response);
            return response;
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    // Constant APIs
    async postAppConst(
        params: Record<string, unknown> = {},
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/const/app', this.mergeOptions(options, params));
    }

    async postLogAgent(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/const/logs/agent', {}, options);
    }

    async postGetCurrentAppVersion(
        params: Record<string, unknown> = {},
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/const/app/version', this.mergeOptions(options, params));
    }

    // Auth APIs
    // Web auth routes
    async postLogin(
        data: LoginRequest,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<LoginResponse>> {
        return this.post<LoginResponse>('/post-login', data, options);
    }

    async postLogout(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/auth/post-logout', {}, options);
    }

    async getLogout(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/auth/get-logout', options);
    }

    // API auth routes
    async postToken(
        data: LoginRequest,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<LoginResponse>> {
        return this.post<LoginResponse>('/api/v1/auth/token', data, options);
    }

    async postTokenRevoke(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.delete('/api/v1/auth/token', options);
    }

    // Profile APIs
    async getNotificationList(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/profile/notifications', options);
    }

    async postNotificationAsRead(
        data: Record<string, unknown>,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.patch('/api/v1/profile/notifications/read', data, options);
    }

    async postNotificationClearAll(
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.delete('/api/v1/profile/notifications', options);
    }

    async postUpdateProfile(
        data: Record<string, unknown>,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.patch<ApiResponse>('/api/v1/profile', data, options);
    }

    // User Management APIs
    async getUserList(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/user-man/users', options);
    }

    async getUserRolePerm(
        params: Record<string, unknown> = {},
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/user-man/users/role-perm', this.mergeOptions(options, params));
    }

    async postUserManSubmit(
        data: Record<string, unknown>,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/user-man/users', data, options);
    }

    async postDeleteUserManSubmit(
        userId: string | number,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.delete(`/api/v1/user-man/users/${userId}`, options);
    }

    async postResetPasswordUserManSubmit(
        data: UserIdPayload,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        const id = data?.id ?? data?.user;
        return this.patch(`/api/v1/user-man/users/${id}/password`, data, options);
    }

    // Role Management APIs
    async getRoleList(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/role-man/roles', options);
    }

    async postRoleSubmit(
        data: Record<string, unknown>,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/role-man/roles', data, options);
    }

    async postDeleteRoleSubmit(
        roleId: string | number,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.delete(`/api/v1/role-man/roles/${roleId}`, options);
    }

    // Server Management APIs
    async getServerLogs(
        params: Record<string, unknown> = {},
        options: RequestOptions = {},
    ): Promise<AxiosResponse<unknown>> {
        return this.get('/api/v1/server-man/logs', this.mergeOptions(options, params));
    }

    async postClearAppCache(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.delete('/api/v1/server-man/cache', options);
    }

    // OAuth Management APIs
    async postGetOauthClient(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/oauth/clients', options);
    }

    async postResetOauthSecret(
        data: { id: string; old_secret: string },
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        const id = data?.id;
        return this.patch(`/api/v1/oauth/clients/${id}/secret`, data, options);
    }

    async postDeleteOauthClient(
        clientId: string | number,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.delete(`/api/v1/oauth/clients/${clientId}`, options);
    }

    async postUpdateOauthClient(
        data: { id: string; name: string; redirect: string },
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        const id = data?.id;
        return this.patch(`/api/v1/oauth/clients/${id}`, data, options);
    }

    async postCreateOauthClient(
        data: Record<string, unknown>,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/oauth/clients', data, options);
    }

    // WhatsApp Management APIs
    async getWhatsappMessagesList(
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/whatsapp/messages', options);
    }

    async getWhatsappMessagesDetail(
        data: MessageIdPayload,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        const { id, phone_number, ...rest } = data || {};
        const messageId = phone_number ?? id;
        return this.get(`/api/v1/whatsapp/messages/${messageId}`, this.mergeOptions(options, rest));
    }

    async postReplyWhatsappMessage(
        data: MessageIdPayload,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        const { id, phone_number, ...rest } = data || {};
        const messageId = phone_number ?? id;
        return this.post(`/api/v1/whatsapp/messages/${messageId}/reply`, rest, options);
    }

    // WhatsApp Template Management APIs
    async getWhatsappTemplatesList(
        params: Record<string, unknown> = {},
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/api/v1/whatsapp/templates', this.mergeOptions(options, params));
    }

    async postCreateWhatsappTemplate(
        data: Record<string, unknown>,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/whatsapp/templates', data, options);
    }

    async postUpdateWhatsappTemplate(
        templateId: string,
        data: Record<string, unknown>,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.patch(`/api/v1/whatsapp/templates/${templateId}`, data, options);
    }

    async deleteWhatsappTemplate(
        templateId: string,
        options: RequestOptions = {},
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.delete(`/api/v1/whatsapp/templates/${templateId}`, options);
    }
}

// Create and export API client instance
export const api = new ApiClient(axiosInstance);

// Export type for use in components
export type { ApiResponse, LoginRequest, LoginResponse, ToastMessage };
