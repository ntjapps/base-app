module.exports = {
    root: true,
    extends: [
        "plugin:vue/vue3-recommended",
        "plugin:prettier/recommended",
        "eslint:recommended",
        "@vue/typescript/recommended",
    ],
    parserOptions: {
        ecmaVersion: 12,
        sourceType: "module",
        parser: "@typescript-eslint/parser",
    },
    env: {
        browser: true,
        node: true,
        es6: true,
    },
};
