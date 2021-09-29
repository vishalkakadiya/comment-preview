# WP Comment Preview

[![Build Status](https://app.travis-ci.com/vishalkakadiya/comment-preview.svg?branch=main)](https://app.travis-ci.com/vishalkakadiya/comment-preview)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/?branch=main)

This plugin will show real comment preview to the users, it's also supporting jetpack's markdown.

#### Steps to configure this plugin in your site:

- Activate jetpack's markdown module.
- ![Activate Jetpack's markdown module](https://github.com/vishalkakadiya/comment-preview/tree/main/screenshots/enable-jetpack-markdown.png)

- Activate jetpack markdown for comments settings.
- ![Activate jetpack markdown for comments settings](https://github.com/vishalkakadiya/comment-preview/tree/main/screenshots/enable-markdown-for-comments.png)

- Activate comment preview plugin now.
- ![Activate comment preview plugin](https://github.com/vishalkakadiya/comment-preview/tree/main/screenshots/enable-comment-preview-plugin.png)

- Comment's form and preview of comment.
- ![Comment's form and preview of comment](https://github.com/vishalkakadiya/comment-preview/tree/main/screenshots/working-comment-preview.png)

#### Support for Custom Post Type?

Yes, this plugin is giving support for custom post types, for that you can use below filter.
- `wp_comment_preview_allowed_post_types`: Returning array of post types will support those post types.

**NOTE:** This plugin works with WordPress's default comment form only.

#### Available Endpoint

**Get single post ( POST request )**
- http://example.com/wp-json/wp_comment_preview/v1/preview
  - Params:
    - `comment` - Comment text.
    - `format` - Whether to preview markdown or plain text - Two values it will take `plain` OR `markdown`.
    - `author` - For non-logged in user you can send author's name here, logged-in users will automatically get their name in that comment. 
