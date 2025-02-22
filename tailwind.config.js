const plugin = require('tailwindcss/plugin');
const defaultTheme = require("tailwindcss/defaultTheme");
const _ = require("lodash");
const brand = require('./brand.json');

module.exports = {
    mode: 'jit',
    brand,
    darkMode: false,
    purge: {
        content: [
            './*.php',
            './*/*.php',
            './**/*.php',
            './safelist.txt'
        ],
    },
    theme: {
        fontFamily: {
          sans: ["Gotham Pro", ...defaultTheme.fontFamily.sans],
          serif: ["Gotham Pro", ...defaultTheme.fontFamily.serif],
          mono: [...defaultTheme.fontFamily.mono],
        },
        extend: {
            colors: brand.colors,
            inset: {
                full: "100%",
                "1/6": "16.6666%",
                "1/4": "25%",
                "1/3": "33.3333%",
                "1/2": "50%",
                4: "4px",
                8: "8px",
                16: "16px",
                32: "32px",
                48: "48px"
            },
            height: {
                80: "80vh",
                20: "20vh"
            },
            minHeight: {
                80: "80vh",
                75: "75vh",
                50: "50vh",
                20: "20vh"
            },
            maxWidth: {
                0: "none"
            },
            maxHeight: {
                0: "0"
            },
        },
    },
    plugins: [
        plugin(function ({addUtilities, addComponents, e, prefix, config, theme}) {
            const colors = theme('colors');
            const margin = theme('margin');
            const screens = theme('screens');
            const fontSize = theme('fontSize');

            const editorColorText = _.map(config("brand.colors", {}), (value, key) => {
                return {
                    [`.has-${key}-color`]: {
                        color: value,
                    },
                };
            });

            const editorColorBackground = _.map(config("brand.colors", {}), (value, key) => {
                return {
                    [`.has-${key}-background-color`]: {
                        backgroundColor: value,
                    },
                };
            });

            const editorFontSizes = _.map(config("brand.fontSizes", {}), (value, key) => {
                return {
                    [`.has-${key}`]: {
                        fontSize: value[0],
                        fontWeight: `${value[1] || '400'}`,
                        letterSpacing: `${value[2] || '0'}`,
                        textTransform: `${value[3] || 'none'}`,
                        lineHeight: `${value[4] || '150%'}`,
                    },
                };
            });

            const alignmentUtilities = {
                '.alignfull': {
                    margin: `${margin[2] || '0.5rem'} calc(50% - 50vw)`,
                    maxWidth: '100vw',
                    "@apply w-screen": {}
                },
                '.alignwide': {
                    "@apply -mx-16 my-2 max-w-screen-xl": {}
                },
                '.alignnone': {
                    "@apply h-auto max-w-full mx-0": {}
                },
                ".aligncenter": {
                    margin: `${margin[2] || '0.5rem'} auto`,
                    "@apply block": {}
                },
                [`@media (min-width: ${screens.sm || '640px'})`]: {
                    '.alignleft:not(.wp-block-button)': {
                        marginRight: margin[2] || '0.5rem',
                        "@apply float-left": {}
                    },
                    '.alignright:not(.wp-block-button)': {
                        marginLeft: margin[2] || '0.5rem',
                        "@apply float-right": {}
                    },
                    ".wp-block-button.alignleft a": {
                        "@apply float-left mr-4": {},
                    },
                    ".wp-block-button.alignright a": {
                        "@apply float-right ml-4": {},
                    },
                },
            };

            const imageCaptions = {
                '.wp-caption': {
                    "@apply inline-block": {},
                    '& img': {
                        marginBottom: margin[2] || '0.5rem',
                        "@apply leading-none": {}
                    },
                },
                '.wp-caption-text': {
                    fontSize: (fontSize.sm && fontSize.sm[0]) || '0.9rem',
                    color: (colors.gray && colors.gray[600]) || '#718096',
                },
            };

            addUtilities([editorColorText, editorColorBackground, alignmentUtilities, editorFontSizes, imageCaptions], {
                respectPrefix: false,
                respectImportant: false,
            });
        }),
    ]
};
