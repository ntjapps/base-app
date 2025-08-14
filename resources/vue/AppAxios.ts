import axios from 'axios';
import type { AxiosInstance, AxiosResponse, AxiosError } from 'axios';

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
        if (this.toast) {
            this.toast(message);
        }
    }

    // Helper method to handle API errors
    private handleError(error: AxiosError<ApiError>) {
        const errorData = error.response?.data;
        this.showToast({
            severity: 'error',
            summary: errorData?.title || 'Error',
            detail: errorData?.message || 'An unexpected error occurred',
            response: error,
        });

        // Reset turnstile if needed
        if (this.turnstileReset) {
            this.turnstileReset();
        }

        throw error;
    }

    // Generic request methods with error handling
    private async get<T>(url: string, config = {}): Promise<AxiosResponse<T>> {
        try {
            return await this.axios.get<T>(url, config);
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    private async post<T>(url: string, data = {}, config = {}): Promise<AxiosResponse<T>> {
        try {
            return await this.axios.post<T>(url, data, config);
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    // Public wrapper to allow arbitrary POST usage from legacy callers
    public async requestPost<T>(url: string, data = {}, config = {}): Promise<AxiosResponse<T>> {
        return this.post<T>(url, data, config);
    }

    private async put<T>(url: string, data = {}, config = {}): Promise<AxiosResponse<T>> {
        try {
            return await this.axios.put<T>(url, data, config);
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    private async delete<T>(url: string, config = {}): Promise<AxiosResponse<T>> {
        try {
            return await this.axios.delete<T>(url, config);
        } catch (error) {
            throw this.handleError(error as AxiosError<ApiError>);
        }
    }

    // Constant APIs
    async postAppConst(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/const/post-app-const');
    }

    async postLogAgent(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/const/post-log-agent');
    }

    async postGetCurrentAppVersion(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/const/post-get-current-app-version');
    }

    // Auth APIs
    // Web auth routes
    async postLogin(data: LoginRequest): Promise<AxiosResponse<LoginResponse>> {
        const response = await this.post<LoginResponse>('/post-login', data);
        this.showToast({
            severity: 'success',
            summary: response.data.title,
            detail: response.data.message,
        });
        return response;
    }

    async postLogout(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/auth/post-logout');
    }

    async getLogout(): Promise<AxiosResponse<ApiResponse>> {
        return this.get('/auth/get-logout');
    }

    // API auth routes
    async postToken(data: LoginRequest): Promise<AxiosResponse<LoginResponse>> {
        const response = await this.post<LoginResponse>('/api/v1/auth/post-token', data);
        this.showToast({
            severity: 'success',
            summary: response.data.title,
            detail: response.data.message,
        });
        return response;
    }

    async postTokenRevoke(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/auth/post-token-revoke');
    }

    // Profile APIs
    async getNotificationList(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/profile/get-notification-list');
    }

    async postNotificationAsRead(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/profile/post-notification-as-read', data);
    }

    async postNotificationClearAll(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/profile/post-notification-clear-all');
    }

    async postUpdateProfile(data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/profile/post-update-profile', data);
    }

    // User Management APIs
    async getUserList(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/user-man/get-user-list');
    }

    async getUserRolePerm(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/user-man/get-user-role-perm');
    }

    async postUserManSubmit(data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/user-man/post-user-man-submit', data);
    }

    async postDeleteUserManSubmit(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/user-man/post-delete-user-man-submit', data);
    }

    async postResetPasswordUserManSubmit(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/user-man/post-reset-password-user-man-submit', data);
    }

    // Role Management APIs
    async getRoleList(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/role-man/get-role-list');
    }

    async postRoleSubmit(data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/role-man/post-role-submit', data);
    }

    async postDeleteRoleSubmit(data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/role-man/post-delete-role-submit', data);
    }

    // Server Management APIs
    async getServerLogs(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/server-man/get-server-logs');
    }

    async postClearAppCache(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/server-man/post-clear-app-cache');
    }

    // OAuth Management APIs
    async postGetOauthClient(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/oauth/post-get-oauth-client');
    }

    async postResetOauthSecret(data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/oauth/post-reset-oauth-secret', data);
    }

    async postDeleteOauthClient(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/oauth/post-delete-oauth-client', data);
    }

    async postUpdateOauthClient(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/oauth/post-update-oauth-client', data);
    }

    async postCreateOauthClient(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/oauth/post-create-oauth-client', data);
    }

    // WhatsApp Management APIs
    async getWhatsappMessagesList(): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/whatsapp/get-whatsapp-messages-list');
    }

    async getWhatsappMessagesDetail(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/whatsapp/get-whatsapp-messages-detail', data);
    }

    async postReplyWhatsappMessage(
        data: Record<string, unknown>,
    ): Promise<AxiosResponse<ApiResponse>> {
        return this.post('/api/v1/whatsapp/post-reply-whatsapp-message', data);
    }
}

// Create and export API client instance
export const api = new ApiClient(axiosInstance);

// Export type for use in components
export type { ApiResponse, LoginRequest, LoginResponse, ToastMessage };

// Backwards compatibility adapter for legacy imports (AppAxios)
export const AppAxios = {
    // auth
    postLogin: (data: LoginRequest) => api.postLogin(data),
    postLogout: () => api.postLogout(),
    getLogout: () => api.getLogout(),
    postToken: (data: LoginRequest) => api.postToken(data),
    postTokenRevoke: () => api.postTokenRevoke(),
    // const
    postAppConst: () => api.postAppConst(),
    postLogAgent: () => api.postLogAgent(),
    postGetCurrentAppVersion: () => api.postGetCurrentAppVersion(),
    // profile
    getNotificationList: () => api.getNotificationList(),
    postNotificationAsRead: (data: Record<string, unknown>) => api.postNotificationAsRead(data),
    postNotificationClearAll: () => api.postNotificationClearAll(),
    postUpdateProfile: (data: Record<string, unknown>) => api.postUpdateProfile(data),
    // user man
    getUserList: () => api.getUserList(),
    getUserRolePerm: () => api.getUserRolePerm(),
    postUserManSubmit: (data: Record<string, unknown>) => api.postUserManSubmit(data),
    postDeleteUserManSubmit: (data: Record<string, unknown>) => api.postDeleteUserManSubmit(data),
    postResetPasswordUserManSubmit: (data: Record<string, unknown>) =>
        api.postResetPasswordUserManSubmit(data),
    // role man
    getRoleList: () => api.getRoleList(),
    postRoleSubmit: (data: Record<string, unknown>) => api.postRoleSubmit(data),
    postDeleteRoleSubmit: (data: Record<string, unknown>) => api.postDeleteRoleSubmit(data),
    // server
    getServerLogs: () => api.getServerLogs(),
    // oauth
    postGetOauthClient: () => api.postGetOauthClient(),
    postResetOauthSecret: (data: Record<string, unknown>) => api.postResetOauthSecret(data),
    postDeleteOauthClient: (data: Record<string, unknown>) => api.postDeleteOauthClient(data),
    postUpdateOauthClient: (data: Record<string, unknown>) => api.postUpdateOauthClient(data),
    postCreateOauthClient: (data: Record<string, unknown>) => api.postCreateOauthClient(data),
    // whatsapp
    getWhatsappMessagesList: () => api.getWhatsappMessagesList(),
    getWaThreadsList: () => api.getWhatsappMessagesList(), // alias
    getWhatsappMessagesDetail: (data: Record<string, unknown>) =>
        api.getWhatsappMessagesDetail(data),
    getWaThreadDetail: (data: Record<string, unknown>) => api.getWhatsappMessagesDetail(data), // alias
    postReplyWhatsappMessage: (data: Record<string, unknown>) =>
        api.postReplyWhatsappMessage(data),
    // generic
    post: (url: string, data = {}, config = {}) => api.requestPost(url, data, config),
};
