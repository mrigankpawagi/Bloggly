CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * For developers

INTRODUCTION
------------

  Layouter template extension example module.

REQUIREMENTS
------------

  * Layouter
  
    https://drupal.org/project/layouter

FOR DEVELOPERS
--------------

  For implementing your templates create new extension module or edit this.
  
  * In *.module:
    
    - implement hook_form_alter for adding your css to layouter multistep form.
      This need for styling radio button in layouts list. Optional;
     
    - implement hook_theme that must contain your template. Required;
    
    - implement hook_layouter_templates_info that described your layout.
      See example that makes it clear. Required;
      
  * Create your twig template (required) and css file with style for radio
    button mentioned above.