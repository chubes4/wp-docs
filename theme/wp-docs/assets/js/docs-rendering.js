( () => {
	const content = document.querySelector( '.wp-block-post-content, .entry-content' );

	if ( ! content ) {
		return;
	}

	const usedSlugs = new Set(
		Array.from( content.querySelectorAll( 'h2[id], h3[id], h4[id]' ) ).map(
			( heading ) => heading.id
		)
	);
	const slugify = ( value ) => {
		const base = value
			.toLowerCase()
			.trim()
			.replace( /[`'".()\[\]{}]/g, '' )
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' ) || 'section';
		let slug = base;
		let count = 2;

		while ( usedSlugs.has( slug ) ) {
			slug = `${ base }-${ count }`;
			count += 1;
		}

		usedSlugs.add( slug );

		return slug;
	};

	content.querySelectorAll( 'h2, h3, h4' ).forEach( ( heading ) => {
		if ( ! heading.id ) {
			heading.id = slugify( heading.textContent || '' );
		}

		if ( heading.querySelector( '.wp-docs-heading-anchor' ) ) {
			return;
		}

		const anchor = document.createElement( 'a' );
		anchor.className = 'wp-docs-heading-anchor';
		anchor.href = `#${ encodeURIComponent( heading.id ) }`;
		anchor.setAttribute( 'aria-label', `Link to ${ heading.textContent.trim() }` );
		anchor.textContent = '#';
		heading.append( anchor );
	} );

	if ( ! navigator.clipboard ) {
		return;
	}

	content.querySelectorAll( 'pre' ).forEach( ( pre ) => {
		let container = pre.closest( '.wp-block-code' );

		if ( ! container ) {
			container = document.createElement( 'div' );
			container.className = 'wp-docs-code-frame';
			pre.before( container );
			container.append( pre );
		}

		if ( container.querySelector( '.wp-docs-code-copy' ) ) {
			return;
		}

		container.classList.add( 'wp-docs-code-has-copy' );

		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'wp-docs-code-copy';
		button.textContent = 'Copy';
		button.setAttribute( 'aria-label', 'Copy code example' );

		button.addEventListener( 'click', async () => {
			try {
				await navigator.clipboard.writeText( pre.textContent || '' );
				button.textContent = 'Copied';
				setTimeout( () => {
					button.textContent = 'Copy';
				}, 1800 );
			} catch {
				button.textContent = 'Select code';
			}
		} );

		container.prepend( button );
	} );
} )();
