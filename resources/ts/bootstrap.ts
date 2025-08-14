declare const window: Window & {
    supportedBrowsers: unknown;
};

/**
 * Let's load supported browser REGEX
 */
import { supportedBrowsers } from './browser';
window.supportedBrowsers = supportedBrowsers;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 *
 */

import Pusher from 'pusher-js';
window.Pusher = Pusher;
