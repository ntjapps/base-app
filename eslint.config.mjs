import globals from 'globals';
import js from '@eslint/js';
import ts from 'typescript-eslint';
import vue from 'eslint-plugin-vue';
import prettier from 'eslint-plugin-prettier/recommended';

export default [
    {
        languageOptions: {
            ecmaVersion: 12,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                ...globals.es6,
            },
        },
    },

    // js
    js.configs.recommended,
    {
        files: ['resources/js/*.js', 'resources/vue/*.js', 'resources/vue/**/*.js'],
        rules: {
            'no-unused-vars': 'off',
            'no-undef': 'off',
        },
    },

    // ts
    ...ts.configs.recommended,
    {
        files: ['resources/ts/*.ts', 'resources/vue/*.ts', 'resources/vue/**/*.ts'],
        rules: {
            '@typescript-eslint/no-unused-vars': 'warn',
            '@typescript-eslint/no-explicit-any': 'warn',
        },
    },
    // tests (spec files): allow any and unused vars in tests/mocks
    {
        files: [
            'resources/ts/**/*.spec.ts',
            'resources/vue/**/*.spec.ts',
            'resources/vue/**/__tests__/**/*.ts',
        ],
        rules: {
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-unused-vars': 'off',
        },
    },

    // vue
    ...vue.configs['flat/recommended'],
    {
        files: ['resources/vue/*.vue', 'resources/vue/**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: ts.parser,
            },
        },
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/attribute-hyphenation': ['error', 'never'],
            'vue/v-on-event-hyphenation': ['error', 'never'],
            'vue/no-v-html': 'off',

            'vue/block-lang': ['error', { script: { lang: 'ts' } }],
            'vue/block-order': ['error', { order: ['script[setup]', 'template', 'style[scoped]'] }],
            'vue/component-api-style': ['error', ['script-setup']],
            'vue/component-name-in-template-casing': 'error',
            'vue/custom-event-name-casing': 'error',
            'vue/define-emits-declaration': 'error',
            'vue/define-macros-order': [
                'error',
                {
                    order: [
                        'defineOptions',
                        'defineModel',
                        'defineProps',
                        'defineEmits',
                        'defineSlots',
                    ],
                    defineExposeLast: true,
                },
            ],
            'vue/define-props-declaration': 'error',
            'vue/html-button-has-type': 'error',
            'vue/no-multiple-objects-in-class': 'warn',
            'vue/no-root-v-if': 'error',
            'vue/no-template-target-blank': 'error',
            'vue/no-undef-components': [
                'warn',
                {
                    ignorePatterns: [
                        'UButton',
                        'UInput',
                        'USelect',
                        'USelectOption',
                        'USelectGroup',
                        'UTable',
                        'UTableColumn',
                        'UTableRow',
                        'UTableCell',
                        'UTooltip',
                    ],
                },
            ],
            'vue/no-undef-properties': 'warn',
            'vue/no-unused-refs': 'warn',
            'vue/no-use-v-else-with-v-for': 'error',
            'vue/no-useless-mustaches': 'warn',
            'vue/no-useless-v-bind': 'warn',
            'vue/no-v-text': 'error',
            'vue/padding-line-between-blocks': 'warn',
            'vue/prefer-define-options': 'error',
            'vue/prefer-separate-static-class': 'warn',
            'vue/prefer-true-attribute-shorthand': 'warn',
            'vue/require-macro-variable-name': 'error',
            'vue/require-typed-ref': 'warn',
            'vue/v-for-delimiter-style': 'error',
            'vue/valid-define-options': 'error',
        },
    },
    {
        ignores: [
            '.git/*',
            '.github/*',
            '.pnpm-store/*',
            '.vscode/*',
            'app/*',
            'bootstrap/*',
            'config/*',
            'database/*',
            'html/*',
            'lang/*',
            'node_modules/*',
            'public/*',
            'resources/vue/volt/*',
            'routes/*',
            'storage/*',
            'stubs/*',
            'tests/*',
            'vendor/*',
        ],
    },

    // prettier
    prettier,
    {
        rules: {
            'prettier/prettier': 'warn',
        },
    },
];
