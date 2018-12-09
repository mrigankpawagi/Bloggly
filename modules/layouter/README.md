CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * For developers
 * Maintainers

INTRODUCTION
------------

  Usually people use one of the WYSIWYG editors for adding/editing content on 
  the websites. But they are not very easy to use for many of them in some 
  cases. If you ever wondered how to put the content on the page formatted in 
  some fancy way (something like two columns of text, for instance) without much
  burden, and if you don't know how to do that in you editor, feel free to try 
  this module.

  * For a full description of the module, visit the project page: 
  https://drupal.org/project/layouter

  * To submit bug reports and feature suggestions, or to track changes:
  https://drupal.org/project/issues/layouter

REQUIREMENTS
------------

  This module requires the following modules:
  
    * Filter
    * Image

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.
   
 * Also you'll need to go to "_Administration_ » _People_ » _Permissions_"
   (/admin/people/permissions#module-layouter)and choose users of which roles
   are allowed to use Layouter for content editing. That's all, now you can go
   and create some content.
   
CONFIGURATION
-------------

  * Configure allowed content types for this module in
   "_Administration_ » _Configuration_ » _Content authoring_ » _Layouter_"
   (/admin/config/content/layouter)
   
  * Configure user permissions in "_Administration_ » _People_ » _Permissions_"
    (/admin/people/permissions#module-layouter)
   
    - Administer layouter
      
        Users in roles with the "Administer layouter" may go to admin page and
        configure module settings.
      
    - Use layouter
      
        User in roles with the "Use layouter" may use Layouter for editing
        content.
      
TROUBLESHOOTING
---------------

  * If you don't see needed text format on module admin page make sure that
    it has disabled html filter. Go to "_Administration_ » _Configuration_ »
    _Content authoring_ » _Text formats and editors_" and click to _Configure_
    button of your format. Then find "_Enable filters_" section and disable html 
    filter.
    
FOR DEVELOPERS
--------------

  You can extend this module with your custom layout templates. See
  layouter_extension_example module for more instructions.
    
MAINTAINERS
-----------

  This module developed by ADCI Solutions team.
  
  * https://www.drupal.org/adci-solutions
  
  * http://www.adcisolutions.com