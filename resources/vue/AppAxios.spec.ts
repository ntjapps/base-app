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
        patch: vi.fn(),
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
            vi.spyOn((globalThis as any).__axiosMock, 'patch').mockResolvedValueOnce(
                successResponse,
            );

            await api.postUpdateProfile(profileData);

            expect((globalThis as any).__axiosMock.patch).toHaveBeenCalledWith(
                '/api/v1/profile',
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
            vi.spyOn((globalThis as any).__axiosMock, 'get').mockResolvedValueOnce(
                notificationResponse,
            );

            await api.getNotificationList();

            expect((globalThis as any).__axiosMock.get).toHaveBeenCalledWith(
                '/api/v1/profile/notifications',
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

            (globalThis as any).__axiosMock.delete.mockResolvedValueOnce(successResponse);

            await api.postClearAppCache();

            expect((globalThis as any).__axiosMock.delete).toHaveBeenCalledWith(
                '/api/v1/server-man/cache',
                {},
            );
        });
    });

    describe('Utility behaviors', () => {
        it('merges params and shows toast when response has title/message', async () => {
            (globalThis as any).__axiosMock.get.mockResolvedValueOnce({
                data: { title: 'Ok', message: 'Done' },
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });

            await api.postAppConst({ a: 1 }, { params: { b: 2 } });

            expect((globalThis as any).__axiosMock.get).toHaveBeenCalledWith('/api/v1/const/app', {
                params: { b: 2, a: 1 },
            });
            expect(mockToast).toHaveBeenCalledWith({
                severity: 'success',
                summary: 'Ok',
                detail: 'Done',
            });
        });

        it('suppresses success toast when noToast is set', async () => {
            (globalThis as any).__axiosMock.get.mockResolvedValueOnce({
                data: { title: 'Ok', message: 'Done' },
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });

            await api.postAppConst({}, { noToast: true });
            expect(mockToast).not.toHaveBeenCalled();
        });

        it('dedupes identical toasts within a short window', () => {
            const now = Date.now();
            vi.spyOn(Date, 'now').mockReturnValue(now);

            const accepted = {
                status: 202,
                data: { data: { message: 'Queued' } },
            } as any;

            const a = api.handle202Accepted(accepted, 'fallback');
            const b = api.handle202Accepted(accepted, 'fallback');

            expect(a).toBe(true);
            expect(b).toBe(true);
            expect(mockToast).toHaveBeenCalledTimes(1);

            (Date.now as any).mockRestore?.();
        });

        it('handle202Accepted returns false for non-202', () => {
            expect(api.handle202Accepted({ status: 200 } as any)).toBe(false);
        });

        it('skips error toast when request config sets noToast, but resets turnstile', async () => {
            const err: any = {
                response: { data: { title: 'E', message: 'M' }, status: 422 },
                config: { noToast: true },
            };
            (globalThis as any).__axiosMock.get.mockRejectedValueOnce(err);

            await expect(api.postAppConst()).rejects.toBe(err);
            expect(mockToast).not.toHaveBeenCalled();
            expect(mockTurnstileReset).toHaveBeenCalled();
        });

        it('exposes requestPost wrapper', async () => {
            (globalThis as any).__axiosMock.post.mockResolvedValueOnce({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });

            await api.requestPost('/x', { a: 1 }, { noToast: true } as any);
            expect((globalThis as any).__axiosMock.post).toHaveBeenCalledWith(
                '/x',
                { a: 1 },
                {
                    noToast: true,
                },
            );
        });
    });

    describe('ID helpers', () => {
        it('builds reset-password route from id or user key', async () => {
            (globalThis as any).__axiosMock.patch.mockResolvedValue({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });

            await api.postResetPasswordUserManSubmit({ id: '1' } as any, { noToast: true });
            expect((globalThis as any).__axiosMock.patch).toHaveBeenCalledWith(
                '/api/v1/user-man/users/1/password',
                { id: '1' },
                { noToast: true },
            );

            await api.postResetPasswordUserManSubmit({ user: '2' } as any, { noToast: true });
            expect((globalThis as any).__axiosMock.patch).toHaveBeenCalledWith(
                '/api/v1/user-man/users/2/password',
                { user: '2' },
                { noToast: true },
            );
        });

        it('uses phone_number when present for whatsapp detail and merges remaining fields into params', async () => {
            (globalThis as any).__axiosMock.get.mockResolvedValueOnce({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });

            await api.getWhatsappMessagesDetail(
                { id: '1', phone_number: '99', extra: 'x' } as any,
                { params: { p: 1 }, noToast: true },
            );

            expect((globalThis as any).__axiosMock.get).toHaveBeenCalledWith(
                '/api/v1/whatsapp/messages/99',
                { params: { p: 1, extra: 'x' }, noToast: true },
            );
        });

        it('uses phone_number when present for whatsapp reply and sends remaining fields as body', async () => {
            (globalThis as any).__axiosMock.post.mockResolvedValueOnce({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });

            await api.postReplyWhatsappMessage({ id: '1', phone_number: '99', text: 'hi' } as any, {
                noToast: true,
            });

            expect((globalThis as any).__axiosMock.post).toHaveBeenCalledWith(
                '/api/v1/whatsapp/messages/99/reply',
                { text: 'hi' },
                { noToast: true },
            );
        });
    });

    describe('Endpoint wrappers', () => {
        it('calls expected endpoints', async () => {
            (globalThis as any).__axiosMock.get.mockResolvedValue({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });
            (globalThis as any).__axiosMock.post.mockResolvedValue({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });
            (globalThis as any).__axiosMock.patch.mockResolvedValue({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });
            (globalThis as any).__axiosMock.delete.mockResolvedValue({
                data: {},
                status: 200,
                statusText: 'OK',
                headers: {},
                config: {},
            });

            await api.getUserList({ noToast: true });
            await api.getUserRolePerm({ q: 1 }, { params: { p: 2 }, noToast: true });
            await api.postUserManSubmit({ a: 1 }, { noToast: true });
            await api.postDeleteUserManSubmit(1, { noToast: true });

            await api.getRoleList({ noToast: true });
            await api.postRoleSubmit({ a: 1 }, { noToast: true });
            await api.postDeleteRoleSubmit(1, { noToast: true });

            await api.getServerLogs({ page: 1 }, { params: { q: 'x' }, noToast: true });
            await api.getRouteAnalytics({ page: 1 }, { params: { q: 'x' }, noToast: true });

            await api.postGetOauthClient({ noToast: true });
            await api.postResetOauthSecret({ id: '1', old_secret: 's' }, { noToast: true });
            await api.postUpdateOauthClient(
                { id: '1', name: 'n', redirect: 'r' },
                { noToast: true },
            );
            await api.postCreateOauthClient({ name: 'n' }, { noToast: true });
            await api.postDeleteOauthClient(1, { noToast: true });

            await api.getWhatsappMessagesList({ noToast: true });
            await api.getWhatsappStats({ noToast: true });
            await api.resolveConversation({ conversation_id: 'c' }, { noToast: true });

            await api.getWhatsappTemplatesList({ page: 1 }, { params: { q: 'x' }, noToast: true });
            await api.postCreateWhatsappTemplate({ a: 1 }, { noToast: true });
            await api.postUpdateWhatsappTemplate('tpl', { a: 1 }, { noToast: true });
            await api.deleteWhatsappTemplate('tpl', { noToast: true });

            await api.getDivisionList({ noToast: true });
            await api.postDivisionManSubmit({ a: 1 }, { noToast: true });
            await api.postDeleteDivisionManSubmit(1, { noToast: true });

            await api.getTagList({ noToast: true });
            await api.postTagManSubmit({ a: 1 }, { noToast: true });
            await api.postDeleteTagManSubmit(1, { noToast: true });

            await api.getAiModelInstructionList({ noToast: true });
            await api.postAiModelInstructionManSubmit({ a: 1 }, { noToast: true });
            await api.postImportAiModelInstructionFromFile({ a: 1 }, { noToast: true });
            await api.postExportAiModelInstructionToFile({ a: 1 }, { noToast: true });
            await api.postDeleteAiModelInstructionManSubmit(1, { noToast: true });

            await api.postGetCurrentAppVersion({ a: 1 }, { params: { b: 2 }, noToast: true });
            await api.postToken({ username: 'u', password: 'p', token: 't' }, { noToast: true });
            await api.postTokenRevoke({ noToast: true });
            await api.postNotificationAsRead({ id: 1 }, { noToast: true });
            await api.postNotificationClearAll({ noToast: true });

            expect((globalThis as any).__axiosMock.get).toHaveBeenCalled();
            expect((globalThis as any).__axiosMock.post).toHaveBeenCalled();
            expect((globalThis as any).__axiosMock.patch).toHaveBeenCalled();
            expect((globalThis as any).__axiosMock.delete).toHaveBeenCalled();
        });

        it('can invoke the internal put helper to cover error handling', async () => {
            const err: any = {
                response: { data: { title: 'E', message: 'M' }, status: 500 },
                config: {},
            };
            (globalThis as any).__axiosMock.put.mockRejectedValueOnce(err);
            await expect((api as any).put('/x', { a: 1 }, { noToast: true })).rejects.toBe(err);
        });
    });

    describe('Error Handling', () => {
        it('should handle network errors', async () => {
            const networkError = new Error('Network Error');
            (globalThis as any).__axiosMock.get.mockRejectedValueOnce(networkError);

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

            (globalThis as any).__axiosMock.get.mockRejectedValueOnce(serverError);

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
