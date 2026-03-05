import { defineAppConfig } from '#app';

export default defineAppConfig({
    ui: {
        // Override NavigationMenu slot classes to ensure leading icons are visible and positioned
        navigationMenu: {
            slots: {
                // Ensure leading icons have spacing and visible color
                linkLeadingIcon: 'shrink-0 size-5 mr-2 inline-flex items-center text-default',
                // Also ensure child link icons have spacing and visible color
                childLinkIcon: 'size-5 shrink-0 mr-2 text-default',
            },
        },
    },
});
