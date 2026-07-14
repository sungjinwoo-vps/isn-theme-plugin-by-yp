module.exports = {
  testDir: './tests/e2e',
  timeout: 30000,
  use: {
    viewport: { width: 1366, height: 900 },
    trace: 'retain-on-failure'
  },
  projects: [
    { name: 'desktop' },
    { name: 'mobile', use: { viewport: { width: 390, height: 844 }, isMobile: true } }
  ]
};

