<?php
/**
 * Template to add markdown option in comment's form.
 *
 * @package WP_Comment_Preview
 */

?>

<div class="comment-form-markdown">

	<input checked="checked" type="radio" id="format-markdown-radio" name="wp_comment_format" value="markdown">
	<label for="format-markdown-radio"><?php esc_html_e( 'Use', 'comment-preview' ); ?> <a href="https://commonmark.org/help/"><?php esc_html_e( 'markdown', 'comment-preview' ); ?></a></label>

	<input type="radio" id="format-text-radio" name="wp_comment_format" value="text">
	<label for="format-text-radio"><?php esc_html_e( 'Use plain text', 'comment-preview' ); ?></label>

</div>
