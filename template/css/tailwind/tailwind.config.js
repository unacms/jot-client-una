module.exports = {
  presets: [require('./parent.config.js')],
  safelist: ['md:col-span-3', 'xl:col-span-2', 'xl:col-span-3', 'xl:col-span-4', 'xl:col-span-5', 'xl:col-span-6', 'xl:col-span-7', 'xl:col-span-8'],
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
  /*blocklist: [ 'hidden', 'text-white', 'font-semibold', 'align-middle', 'align-top', 'flex-col', 'flex-nowrap', 'items-start', 'items-center',
               'justify-center', 'justify-between', 'resize', 'visible', 'block', 'inline-block', 'flex', 'flex-grow', 'self-center', 'truncate',
               'h-min', 'text-ellipsis', 'rounded-full', 'overflow-hidden' ]*/
}