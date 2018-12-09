# SVG Formatter

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Security
  * Author

## INTRODUCTION

SVG Formatter module provides support for using SVG images on your website.

Standard image field in Drupal 8 doesn't support SVG images. If you really want
to display SVG images on your website then you need another solution. This
module adds a new formatter for the file field, which allows files with any 
extension to be uploaded. In the formatter settings you can set default image 
size and enable alt and title attributes. If you want to add some CSS and 
Javascript magic to your SVG images, then use inline SVG option.

## REQUIREMENTS

None.

## INSTALLATION

Use Composer to install the module:

```
composer require drupal/svg_formatter
```

and then enable it with Drush:

```
drush en svg_formatter -y
```

## CONFIGURATION

1. Add a file field to your content type, taxonomy or any other entity and add 
svg to the allowed file extensions.
2. Go to the 'Manage display' and change the field format to 'SVG Formatter'.
3. Set image dimensions if you want and enable or disable attributes.

Blog post describing how to use the module:  
https://gorannikolovski.com/drupal-8-and-svg-images

## SECURITY

Please make sure that library 'enshrined/svg-sanitize' that is required in the
composer.json file is installed, because otherwise your site may be vulnerable.
If you allow users to upload SVG images and use inline SVG output mode, users
may exploit XSS vulnerability.

### AUTHOR

Goran Nikolovski  
Website: http://gorannikolovski.com  
Drupal.org: https://www.drupal.org/u/gnikolovski  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: http://www.studiopresent.com  
Drupal: https://www.drupal.org/studio-present  
Email: info@studiopresent.com  
