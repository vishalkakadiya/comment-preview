( function ( data ) {
	const previewButton = document.getElementById( 'preview' );
	const anonymousCheckBox = document.getElementById( 'anonymous-override' );
	const previewWrapper = document.getElementById( 'preview-wrapper' );
	const template = document.getElementById( 'preview-template' );

	document.getElementById( 'cancel-comment-reply-link' ).addEventListener( 'click', clearPreview );
	document.querySelectorAll( '.comment-reply-link' ).forEach( ( replyButton ) => {
		replyButton.addEventListener( 'click', clearPreview );
	} )

	previewButton.addEventListener( 'click', () => {

		// Disable the preview button while generating the preview.
		previewButton.disabled = true;

		// Collect the data to pass along for generating a comment preview.
		const commentData = {
			comment: document.getElementById( 'comment' ).value,
			format: document.querySelector( '[name="wp_comment_format"]:checked' ).value,
		};

		// Add anonymous submission data if applicable.
		if ( anonymousCheckBox && anonymousCheckBox.checked ) {
			commentData.anonymous = true;
		}

		// Make the request.
		fetch( data.apiURL + 'preview', {
			method: 'POST',
			headers: {
				Accept: 'application/json, text/plain, */*',
				'Content-Type': 'application/json',
				'X-WP-Nonce': data.nonce, // Required for any authenticated requests.
			},
			body: JSON.stringify( commentData ),
		} )
			.then( response => response.json() )
			.then( data => displayPreview( data ) );
	} );

	/**
	 * Clear out the preview wrapper when a reply or cancel link is clicked.
	 */
	function clearPreview() {
		previewWrapper.innerHTML = '';
	}

	/**
	 * Fill in the preview template with the response data.
	 *
	 * @param {Object} data Response data.
	 */
	function displayPreview( data ) {

		const preview = template.content.cloneNode( true );

		// Fill in the pieces.
		preview.querySelector( '.avatar' ).src = data.gravatar;
		preview.querySelector( '.comment-author .fn' ).innerText = data.author;
		preview.querySelector( 'time' ).innerText = data.date;
		preview.querySelector( '.comment-content' ).innerHTML = data.comment;

		// Clear out any previous previews before appending the current one.
		previewWrapper.innerHTML = '';
		previewWrapper.append( preview );

		// Add the badge class if appropriate - `preview` is a DocumentFragment,
		// so it doesn't have a classList property to manipulate directly.
		if ( data.class ) {
			previewWrapper.querySelector( '.comment' ).classList.add( data.class );
		}

		// Account for the WP Admin bar when determining scroll coordinates.
		let offset = ( document.body.classList.contains( 'admin-bar' ) )
			? document.getElementById( 'wpadminbar' ).offsetHeight
			: 0;

		// Scroll to the generated preview.
		window.scrollTo(
			{
				top: previewWrapper.getBoundingClientRect().top + window.pageYOffset - offset,
				behavior: 'smooth'
			}
		);

		// Re-enable the preview button to allow further previews.
		previewButton.disabled = false;
	}
} )( commentPreviewData ); /* global commentPreviewData */
