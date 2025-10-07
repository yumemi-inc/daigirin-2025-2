module.exports = {
  title: 'ゆめみ大技林 ' /*\'23'*/,
  author: 'ゆめみ大技林製作委員会',
  language: 'ja',
  size: 'A5',
  theme: [
    'vivliostyle-theme-macneko-techbook@0.4.0',
    '@mitsuharu/vivliostyle-theme-noto-sans-jp@0.1.4',
    'theme/theme-custom',
  ],
  entry: [
    // 目次
    'index.md',
    // はじめに
    'preface.md',
    // 各章の原稿
    'akatsuki174.md',
    'makino.md',
    'yuki.md',
    'usami.md',
    'namnium.md',
    'uutan1108/index.md',
    'molpui.md',
    'kii.md',
    'emoto.md',
    'kawashima.md',
    'mikai.md',
    'kitaji0306.md',
    'narawa.md',
    'motsu-keyboard.md',
    'lovee.md',

    // 著者紹介
    'authors.md',
    // 奥付
    'colophon.md',
  ],
  entryContext: './manuscripts',
  output: ['output/ebook.pdf'],
  workspaceDir: '.vivliostyle',
  toc: false,
  cover: undefined,
}
