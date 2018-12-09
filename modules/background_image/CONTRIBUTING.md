# Contributing

### Compiling ES6 JavaScript

This module follows core's lead and develops using latest ES6 standards.

For this to function in all browsers, it must be compiled.

Instead of maintaining this module's own version of a "build system", it uses
the scripts that core provides. To get this working properly, you must do the
following:

- Navigate to this modules directory and run `yarn`. This will install the same
  core dependencies local to this module folder (needed).
- Navigate to `DOCROOT/core` and run `yarn`. This install the actual core
  dependencies.
- Execute the following (changing the path of this module's JS file to where
  ever you have the development version of this module installed):
  `yarn build:js --file ../modules/background_image/js/background_image.es6.js`
