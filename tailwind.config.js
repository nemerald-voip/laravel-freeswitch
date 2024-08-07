/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./Modules/**/Resources/assets/js/**/*.vue", // Include module Vue files
    ],
    // prefix: 'tw-',
    // corePlugins: {
    //     preflight: false,
    //   },
    theme: {
        fontFamily: {
            nunito: ["Nunito", "sans-serif"],
          },
        extend: {
            display: ['group-hover'],
            maxWidth: {'95':'95%'},
        },


    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}

