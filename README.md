# WP Comment Preview

[![Build Status](https://app.travis-ci.com/vishalkakadiya/comment-preview.svg?branch=main)](https://app.travis-ci.com/vishalkakadiya/comment-preview)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/vishalkakadiya/comment-preview/?branch=main)

**PHPCS Standard used:** WordPress-Extra

This plugin will show real comment preview to the users, it's also supporting jetpack's markdown.

-------------------

## Steps to configure this plugin in your site:

#### Activate jetpack's markdown module.

![Activate Jetpack's markdown module](https://user-images.githubusercontent.com/9035925/135707056-5ebb79ff-5685-413c-97a6-5ba6723504c3.png)

#### Activate jetpack markdown for comments settings.

![Activate jetpack markdown for comments settings](https://user-images.githubusercontent.com/9035925/135707070-3f94d8fd-f844-4edd-82f5-bcf7bee6422e.png)

#### Activate comment preview plugin now.

![Activate comment preview plugin](https://user-images.githubusercontent.com/9035925/135707095-8c25ecde-bad2-413e-8ee1-2e90ebc3fb45.png)

#### Comment's form and preview of comment.

![Comment's form and preview of comment](https://user-images.githubusercontent.com/9035925/135707115-4135a227-c256-433d-94d0-4ee621494557.png)

-------------------

## Support for Custom Post Type?

Yes, this plugin is giving support for custom post types, for that you can use below filter.
- `wp_comment_preview_allowed_post_types`: Returning array of post types will support those post types.

-------------------

**NOTE:** This plugin works with WordPress's default comment form only.

## Available Endpoint

**Convert Markdown comment to readable format ( POST request )**
- `https://example.com/wp-json/wp_comment_preview/v1/preview`
  - Params:
    - `comment` - Comment text.
    - `format` - Whether to preview markdown or plain text - Two values it will take `plain` OR `markdown`.
    - `author` - For non-logged in user you can send author's name here, logged-in users will automatically get their name in that comment. 
