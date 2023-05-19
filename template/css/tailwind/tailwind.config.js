module.exports = {
  content: ['../../*.html', '../../../js/*.js'],
  //presets: [require('../../../../../../plugins_public/tailwind/js/tailwind.config.js')],
  safelist: ['md:col-span-2', 'md:col-span-3', 'md:col-span-4', 'md:col-span-5'],
  theme: {
      extend: {
          colors: {
              'bubble-away': '#f5a623'
          },
          flex: {
              'stat': '0 0 auto',
              'dynamic': '1 1 0',
          },
          screens: {
              'xs': '480px'
          }
      },
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}