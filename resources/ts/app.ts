/**
 * Let's import CSS from TS/JS this need file extension
 */
import "../css/app.scss";

/**
 * Let's load our application
 */
import "./bootstrap";
import "../vue/AppVue";

/**
 * Load static assets
 */
import.meta.glob([
    '../images/**',
    '../fonts/**',
]);
