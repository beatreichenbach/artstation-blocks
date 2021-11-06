# artstation-blocks
A collection of blocks to integrate ArtStation into WordPress.

### ArtStation: Gallery (gallery)
Lists all the repositories from a user in a list.

## Installation
https://wordpress.org/support/article/managing-plugins/

Make sure your personal artstation website is set to the default theme. The plugin is scraping https://\<username\>.artstation.com/pages/portfolio for the first element with class "album-grid".

Unfortunately Cloudflare is detecting requests with Curl and file_get_contents. To get around this, the plugin now allows to override the scraping input by loading an html file manually. Go to https://\<username\>.artstation.com/pages/portfolio and save the source code in a file named _portfolio.html_ in the base directory next to _index.php_.

## Wordpress Blocks
https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/writing-your-first-block-type/
