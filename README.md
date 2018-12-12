# Bloggly
_A Profile to fulfil all your blogging needs._

Bloggly was made for one of Drupal's GCI 2018 tasks - _Build a Drupal Distribution for blogging._

### How I knew I could make this?
For making Bloggly, I referred the [Drupal Documentation](https://www.drupal.org/docs/8/distributions) which provided all the required step-by-step information I needed.

### Selection of Modules / Themes

I did a bit of research on the Drupal Module And Theme directory, and searched modules by various keywords to decide which modules and themes I had to Include.

### The Development Process

1. Created the basic `.profile`, `.info.yml` and `.install` files.
2. Secondly, once I knew which modules I had to use, I downloaded the assets of the modules which were not included with the core, and put them in the `/modules` directory.
3. The _Slick_ module gave a bit of problem since it needed some dependencies like _Slick_ and _bLazy_ which I put in the `/libraries` directory. 
4. I downloaded the _Nexus_ theme and put it in the `/themes` directory. 
5. At each step, I used _Pantheon_ for testing. I used `SFTP` to upload the profile after every change and then check the working.

## Modules

* metatag
* layouter
* background_image
* svg_formatter
* mailchimp
* addtoany
* sitemap
* views_slideshow
* sociallinks
* slick
* automated_cron
* block
* block_content
* breakpoint
* ckeditor
* color
* comment
* config
* contact
* contextual
* datetime
* dblog
* dynamic_page_cache
* editor
* field
* field_ui
* file
* filter
* help
* history
* image
* link
* menu_ui
* node
* options
* page_cache
* path
* quickedit
* rdf
* search
* shortcut
* system
* taxonomy
* text
* toolbar
* tour
* update
* user
* views_ui
* menu_link_content
* views
* token

## Themes

* Nexus
* Stable
* Classy
* Bartik
* Seven

# Usage

1. Create a new website and _deploy_ Drupal 8. Do not **install** Drupal yet.
2. Download the Bloggly Distribution and place all the files in the `/profiles/bloggly` folder of the Drupal root folder on the website.
3. Now, open the `/config/install.php` page of your site to begin installing Drupal. 
4. The Bloggly installation profile will be chosen by default if there is no other profile available. Else, choose Bloggly as the installation profile while installing Drupal.
5. Enter the basic site configuration as followed. 

_You are done!_

**Note**: _Bloggly doesn't enable any themes, and so you would have to enable them yourself from `/admin/appearance`._
