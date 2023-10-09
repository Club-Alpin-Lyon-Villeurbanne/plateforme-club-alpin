"use strict";

module.exports = function (options) {
  // @see https://www.npmjs.com/package/ssh-deploy-release
  return {
    // Common configuration
    // These options will be merged with those specific to the environment
    common: {
      localPath: './',
      share: {},
      exclude: [
        'infrastructure',
        'assets',
        'docker',
        'app.blackfire.io',
        '.circleci',
        '.github',
        'node_modules',
        'resources',
        'tests',
        'translations'
      ],
      create: []
    },
    // Environment specific configuration
    environments: {
      review: {
        host: 'my.server.com',
        username: 'username',
        password: 'password',
        deployPath: '/path/to/review/' + options.get('branch'),
        allowRemove: true
      },
      preproduction: {
        host: 'clubalpinlyon.top',
        username: 'username',
        password: 'password',
        deployPath: '/path/to'
      },
      production: {
        host: 'clubalpinlyon.fr',
        username: 'ec2-user',
        privateKeyFile: process.env.SSH_PRIVATE_KEY,
        deployPath: '/var/www/clubalpinlyon.fr/deployments/test'
      }
    }
  };
};