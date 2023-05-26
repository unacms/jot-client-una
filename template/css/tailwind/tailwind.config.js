module.exports = {
  presets: [require('./parent.config.js')],
  safelist: ['md:col-span-3'],
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
  }
}