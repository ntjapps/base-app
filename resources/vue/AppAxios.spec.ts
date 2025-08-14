import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import axios from 'axios';
import { api } from './AppAxios';
import type { LoginRequest } from './AppAxios';

// Mock axios (hoisted-safe)
vi.mock('axios', () => {
    const instance = {
        defaults: {
            headers: { common: { 'X-Requested-With': 'XMLHttpRequest' } },
            withCredentials: true,
        },
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    } as any;
    (globalThis as any).__axiosMock = instance;
    return {
        default: {
            create: vi.fn(() => instance),
        },
        // also expose named export shape if used somewhere
        create: vi.fn(() => instance),
    };
});

describe('ApiClient', () => {
    const mockToast = vi.fn();
    const mockTurnstileReset = vi.fn();

    beforeEach(() => {
        // Reset toast and turnstile functions
        api.setToastDisplay(mockToast);
        api.setTurnstileReset(mockTurnstileReset);
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    describe('Authentication', () => {
        const loginData: LoginRequest = {
            username: 'testuser',
            password: 'password123',
            token: 'turnstile-token',
        };

        const successResponse = {
            data: {
                title: 'Success',
                message: 'Login successful',
            },
            status: 200,
            statusText: 'OK',
            headers: {},
            config: {},
        };

        it('should handle successful login', async () => {
            (globalThis as any).__axiosMock.post.mockResolvedValueOnce(successResponse);

            await api.postLogin(loginData);

            expect(mockToast).toHaveBeenCalledWith({
                severity: 'success',
                summary: 'Success',
                detail: 'Login successful',
            });
            expect((globalThis as any).__axiosMock.post).toHaveBeenCalledWith(
                '/post-login',
                loginData,
                {},
            );
        });

        it('should handle login error', async () => {
            const errorResponse = {
                response: {
                    data: {
                        title: 'Error',
                        message: 'Invalid credentials',
                    },
                },
            };

            (globalThis as any).__axiosMock.post.mockRejectedValueOnce(errorResponse);

            await expect(api.postLogin(loginData)).rejects.toThrow();

            expect(mockToast).toHaveBeenCalledWith({
                severity: 'error',
                summary: 'Error',
                detail: 'Invalid credentials',
                response: errorResponse,
            });
            expect(mockTurnstileReset).toHaveBeenCalled();
        });

        it('should handle logout', async () => {
            (globalThis as any).__axiosMock.post.mockResolvedValueOnce(successResponse);

            await api.postLogout();

            // Verify that the post request was made to the correct endpoint
            expect((globalThis as any).__axiosMock.post).toHaveBeenCalledWith(
                '/auth/post-logout',
                {},
                {},
            );
        });
    });

    describe('Profile Management', () => {
        const profileData = {
            name: 'John Doe',
            email: 'john@example.com',
        };

        const successResponse = {
            data: {
                title: 'Success',
                message: 'Profile updated',
            },
            status: 200,
            statusText: 'OK',
            headers: {},
            config: {},
        };

        it('should update profile successfully', async () => {
            const axiosInstance = axios.create();
            vi.spyOn((globalThis as any).__axiosMock, 'post').mockResolvedValueOnce(
                successResponse,
            );

            await api.postUpdateProfile(profileData);

            expect((globalThis as any).__axiosMock.post).toHaveBeenCalledWith(
                '/api/v1/profile/post-update-profile',
                profileData,
                {},
            );
        });

        it('should get notification list', async () => {
            const notificationResponse = {
                data: {
                    title: 'Success',
                    message: 'Notifications retrieved',
                    data: [{ id: 1, message: 'Test notification' }],
                },
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            };

            const axiosInstance = axios.create();
            vi.spyOn((globalThis as any).__axiosMock, 'post').mockResolvedValueOnce(
                notificationResponse,
            );

            await api.getNotificationList();

            expect((globalThis as any).__axiosMock.post).toHaveBeenCalledWith(
                '/api/v1/profile/get-notification-list',
                {},
                {},
            );
        });
    });

    describe('Server Management', () => {
        it('should clear app cache', async () => {
            const successResponse = {
                data: {
                    title: 'Success',
                    message: 'Cache cleared',
                },
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            };

            (globalThis as any).__axiosMock.post.mockResolvedValueOnce(successResponse);

            await api.postClearAppCache();

            expect((globalThis as any).__axiosMock.post).toHaveBeenCalledWith(
                '/api/v1/server-man/post-clear-app-cache',
                {},
                {},
            );
        });
    });

    describe('Error Handling', () => {
        it('should handle network errors', async () => {
            const networkError = new Error('Network Error');
            (globalThis as any).__axiosMock.post.mockRejectedValueOnce(networkError);

            await expect(api.postAppConst()).rejects.toThrow('Network Error');

            expect(mockToast).toHaveBeenCalledWith({
                severity: 'error',
                summary: 'Error',
                detail: 'An unexpected error occurred',
                response: networkError,
            });
        });

        it('should handle server errors', async () => {
            const serverError = {
                response: {
                    data: {
                        title: 'Server Error',
                        message: 'Internal Server Error',
                    },
                    status: 500,
                },
            };

            (globalThis as any).__axiosMock.post.mockRejectedValueOnce(serverError);

            await expect(api.postAppConst()).rejects.toThrow();

            expect(mockToast).toHaveBeenCalledWith({
                severity: 'error',
                summary: 'Server Error',
                detail: 'Internal Server Error',
                response: serverError,
            });
        });
    });

    describe('Request Configuration', () => {
        it('should include correct headers', () => {
            expect((globalThis as any).__axiosMock.defaults.headers.common).toEqual(
                expect.objectContaining({
                    'X-Requested-With': 'XMLHttpRequest',
                }),
            );
        });

        it('should enable withCredentials', () => {
            expect((globalThis as any).__axiosMock.defaults.withCredentials).toBe(true);
        });
    });
});
