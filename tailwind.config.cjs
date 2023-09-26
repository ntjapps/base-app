module.exports = {
    plugins: [require("daisyui")],
    content: [
        "resources/views/*.php",
        "resources/views/**/*.php",
        "resources/views/**/**/*.php",
        "resources/views/**/**/**/*.php",
        "resources/views/**/**/**/**/*.php",
        "resources/views/**/**/**/**/**/*.php",
        "resources/views/**/**/**/**/**/**/*.php",
        "resources/js/*.js",
        "resources/ts/*.ts",
        "resources/vue/*.vue",
        "resources/vue/*.js",
        "resources/vue/*.ts",
        "resources/vue/**/*.vue",
        "resources/vue/**/*.js",
        "resources/vue/**/*.ts",
        "./node_modules/primevue/**/*.{vue,js,ts,jsx,tsx}",
    ],
    darkMode: "class",
    daisyui: {
        styled: true,
        themes: [
            {
                mytheme: {
                    primary: "#1d4ed8",
                    secondary: "#177bc6",
                    accent: "#66f464",
                    neutral: "#2563eb",
                    "base-100": "#F2F1F3",
                    info: "#258AEF",
                    success: "#7BEAB3",
                    warning: "#F8C025",
                },
            },
        ],
    },
};
