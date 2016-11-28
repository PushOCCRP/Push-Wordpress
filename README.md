# Push Mobile App Wordpress Plugin
------
This is a plugin that enables the Push Mobile App ecosystem for Wordpress.
## Setup
1. Download, or clone this repository
2. Zip up the files
3. In the Wordpress admin page go to 'Plugins' -> 'Add New' on the left side menu.
4. Click the 'Upload Plugin' button near the top of the window
5. Choose the zip file you make in step two
6. After it's installed click the "Activate Plugin" link

At this point it should be working. You can test by going to a link ```[Your URL]?push-occrp=true&occrp_push_type=articles``` ex. ```https://www.example.com?push-occrp=true&occrp_push_type=articles```

## Customizing
------
If you have certain types of post types or categories that you'd like to limit from every being available in the app you can easily set it in the settings menu.

The settings are available in the 'Settings' -> 'Push Mobile App' menu option.

On this page check any categories or post types you *do not* want to be available to the app backend. You can also choose whether or not to have articles categorized by the 'categories' or the 'post_types' fields on each post.
