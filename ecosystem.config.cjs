module.exports = {
  apps : [{
    name: 'playground-gallery',
    script: 'server/index.js',
    env: {
      NODE_ENV: 'production',
      PORT: 4007
    }
  }]
};
