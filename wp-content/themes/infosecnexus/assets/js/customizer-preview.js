(function () {
  'use strict';

  const style = document.createElement('style');
  document.head.appendChild(style);

  const map = {
    accent_color: '--isnx-accent',
    critical_color: '--isnx-critical',
    high_color: '--isnx-high',
    info_color: '--isnx-info',
    body_font: '--isnx-body-font',
    heading_font: '--isnx-heading-font',
    base_font_size: '--isnx-base-font-size',
    content_width: '--isnx-content-width',
    wide_width: '--isnx-wide-width'
  };

  Object.keys(map).forEach((setting) => {
    wp.customize(setting, (value) => {
      value.bind((next) => {
        document.documentElement.style.setProperty(map[setting], next);
      });
    });
  });
}());

