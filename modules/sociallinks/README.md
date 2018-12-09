# SocialLinks
This module is meant to make it easier for you to add cool social media links to your organisation - right on your Drupal Website!

![image](https://user-images.githubusercontent.com/25179158/48263659-e676e880-e44c-11e8-857f-7b750df4d526.png)

## Installation

#### STEP 1
Clone the repository to your system, and copy the files into your *Drupal Installation* at `\modules\custom\sociallinks\`.

#### STEP 2
Navigate to `Manage → Extend → Custom` and check `SocialLinks`.

#### STEP 3
Click on ***Install***. You are now ready to start using *SocialLinks*.

## Configuration
You can configure the module to specify the URLs for the social media links in the grid.

**Note**: *You must be an administrator for this.*

#### STEP 1
Navigate to `Manage → Configuration → Development → SocialLinks Configuration`

This will lead you to `/admin/config/Social/config`.

![image](https://user-images.githubusercontent.com/25179158/48264199-b7617680-e44e-11e8-9b48-f2d3c6ff7af5.png)

#### STEP 2
In each text field, enter the required value (usually *username*) for each corresponding website.

#### STEP 3
Click on ***Save Configuration***.  You are done!


## Usage
Head on to `/Social` from your website to see *SocialLinks* in action.

*For a working demo, [click here](http://dev-mriganksdrupalsite.pantheonsite.io/Social).*

## How *SocialLinks* was made
SocialLinks was made entirely by me, and the only external library I used was *FontAwesome* for the Social Media Website Icons.

    https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css
I followed some Drupal 8 Module Tutorials from the following links, to get acquainted with Drupal (as I never worked on it before) to get a start.


 1. [Drupal 8 Docs](https://www.drupal.org/docs/8/creating-custom-modules/getting-started-background-prerequisites-drupal-8)
 2. [Suyati.com](https://suyati.com/blog/step-step-guide-create-custom-modules-drupal-8/)
 3. [Red Crackle](http://redcrackle.com/blog/say-hello-world-drupal-8-basic-steps-involved-creating-custom-module-drupal-8)
 4. [Cloud Ways](https://www.cloudways.com/blog/create-drupal-8-module/)

For reference to a simple working model to understand the skeleton of a module, I referred to [Sidharrell's D8HW Example](https://github.com/sidharrell/D8HWexample).

### Directory Structure
![image](https://user-images.githubusercontent.com/25179158/48297755-33eb6800-e4d6-11e8-9d31-28a57408951d.png)

The module consists of the basic `.info.yml` to provide information about the module while installing it, `.links.menu.yml` to specify the path to be linked from *Configuration* section of admin pages, and a `.routing.yml` to specify what should be displayed at which path (the content and the settings) and for whom.

> One of the problems I experienced was that the link for *SocialLinks Configuration* in Configuration page was taking me to `/Social` where the module's content lives, not the settings - even though I had specified `/admin/config/Social/config` in `.routing.yml`.
> 
> The problem was finally solved when I changed `route_name` under `sociallinks.admin`  to `sociallinks.config` (which is routed to configuration), which I had mistakenly put to `sociallinks.content	` (which routes to the content page).

Further, there is a GNU `LICENSE` and `README.md` needed for the Git repository.

The module consists of 2 components - a controller (`sociallinksController`) and a form (`sociallinksConfigForm`).

The Controller contains the markup that is returned to the page. This included the `HTML` markup that makes the icons and the hyperlinks, and some `CSS` within `<style></style>` and a link to the `FontAwesome` library. This code was taken from a fiddle I created myself at [JSFiddle](https://jsfiddle.net/mrigankpawagi/dd24utvp/) earlier.

The Form consists of the Configuration Form that allows Administrators to customize the links to be displayed. It has fields for each social media website and finally a `submitForm()` function. 

> The problem I faced in this was to use the Configuration Settings Given in the Form within my Controller, to alter the markup according to the settings. 
>
>I firstly used 

    \Drupal::config('sociallinks.facebook')->get('sociallinks.facebook')
    (for the value to facebook)

> but this returned nothing. I noticed that I hadn't imported the required modules from Drupal Core.

    use Drupal\Core\Form\ConfigFormBase;
    use Drupal\Core\Form\FormStateInterface;
> This didn't work either and it finally did when I changed the above segment to

    \Drupal::config('sociallinks.settings')->get('sociallinks.facebook')

Finally, there is a `.config.yml` at `config → install`  to put some conventional default configuration.
