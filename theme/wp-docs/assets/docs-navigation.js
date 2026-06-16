( function () {
	const searchData = window.wpDocsSearchIndex || { entries: [] };
	const entries = Array.isArray( searchData.entries ) ? searchData.entries : [];
	let activeResult = -1;
	let filteredResults = [];

	function slugify( value ) {
		return value
			.toLowerCase()
			.trim()
			.replace( /<[^>]*>/g, '' )
			.replace( /[^a-z0-9\s-]/g, '' )
			.replace( /\s+/g, '-' )
			.replace( /-+/g, '-' )
			.replace( /^-|-$/g, '' );
	}

	function buildToc() {
		const content = document.querySelector( '[data-wp-docs-content]' );
		const toc = document.querySelector( '[data-wp-docs-toc-list]' );
		const empty = document.querySelector( '[data-wp-docs-toc-empty]' );

		if ( ! content || ! toc ) {
			return;
		}

		const headings = Array.from( content.querySelectorAll( 'h2, h3, h4' ) ).filter( ( heading ) => heading.textContent.trim() );
		const usedIds = new Set();

		toc.innerHTML = '';
		if ( empty ) {
			empty.hidden = headings.length > 0;
		}

		if ( headings.length === 0 ) {
			return;
		}

		const list = document.createElement( 'ol' );
		list.className = 'wp-docs-toc__list';

		headings.forEach( ( heading ) => {
			const headingText = heading.textContent.trim();
			const baseId = heading.id || slugify( headingText ) || 'section';
			let id = baseId;
			let index = 2;

			while ( usedIds.has( id ) || ( document.getElementById( id ) && document.getElementById( id ) !== heading ) ) {
				id = `${ baseId }-${ index }`;
				index += 1;
			}

			usedIds.add( id );
			heading.id = id;

			if ( ! heading.querySelector( '.wp-docs-heading-anchor' ) ) {
				const anchor = document.createElement( 'a' );
				anchor.className = 'wp-docs-heading-anchor';
				anchor.href = `#${ id }`;
				anchor.setAttribute( 'aria-label', `Link to ${ headingText }` );
				anchor.textContent = '#';
				heading.appendChild( anchor );
			}

			const item = document.createElement( 'li' );
			item.className = `wp-docs-toc__item wp-docs-toc__item--${ heading.tagName.toLowerCase() }`;

			const link = document.createElement( 'a' );
			link.href = `#${ id }`;
			link.textContent = headingText;

			item.appendChild( link );
			list.appendChild( item );
		} );

		toc.appendChild( list );
	}

	function normalize( value ) {
		return String( value || '' ).toLowerCase();
	}

	function search( query ) {
		const terms = normalize( query ).split( /\s+/ ).filter( Boolean );

		if ( terms.length === 0 ) {
			return [];
		}

		return entries
			.map( ( entry ) => {
				const haystacks = [
					normalize( entry.title ),
					normalize( entry.section ),
					normalize( entry.excerpt ),
					normalize( Array.isArray( entry.headings ) ? entry.headings.join( ' ' ) : '' ),
					normalize( entry.body ),
				];
				const text = haystacks.join( ' ' );
				const matches = terms.every( ( term ) => text.includes( term ) );

				if ( ! matches ) {
					return null;
				}

				let score = 0;
				terms.forEach( ( term ) => {
					if ( haystacks[ 0 ].includes( term ) ) {
						score += 8;
					}
					if ( haystacks[ 3 ].includes( term ) ) {
						score += 4;
					}
					if ( haystacks[ 2 ].includes( term ) ) {
						score += 2;
					}
				} );

				return { ...entry, score };
			} )
			.filter( Boolean )
			.sort( ( a, b ) => b.score - a.score || String( a.title ).localeCompare( String( b.title ) ) )
			.slice( 0, 12 );
	}

	function renderResults( results, query ) {
		const resultsNode = document.querySelector( '[data-wp-docs-search-results]' );
		const empty = document.querySelector( '[data-wp-docs-search-empty]' );

		if ( ! resultsNode || ! empty ) {
			return;
		}

		resultsNode.innerHTML = '';
		activeResult = results.length > 0 ? 0 : -1;
		filteredResults = results;

		if ( results.length === 0 ) {
			empty.hidden = false;
			empty.textContent = query.trim() ? 'No results found.' : 'Start typing to search the pilot docs.';
			return;
		}

		empty.hidden = true;
		const sections = [];
		results.forEach( ( result, index ) => {
			const section = result.section || 'Docs';
			let group = sections.find( ( candidate ) => candidate.section === section );

			if ( ! group ) {
				group = { section, items: [] };
				sections.push( group );
			}

			group.items.push( { result, index } );
		} );

		sections.forEach( ( group ) => {
			const heading = document.createElement( 'div' );
			heading.className = 'wp-docs-search__section';
			heading.textContent = group.section;
			resultsNode.appendChild( heading );

			group.items.forEach( ( { result, index } ) => {
				appendResult( resultsNode, result, index );
			} );
		} );
	}

	function appendResult( resultsNode, result, index ) {
		const link = document.createElement( 'a' );
		link.className = 'wp-docs-search__result';
		link.href = result.url || '#';
		link.setAttribute( 'role', 'option' );
		link.setAttribute( 'aria-selected', index === activeResult ? 'true' : 'false' );
		link.dataset.wpDocsSearchResult = String( index );

		const title = document.createElement( 'span' );
		title.className = 'wp-docs-search__result-title';
		title.textContent = result.title || 'Untitled';

		const excerpt = document.createElement( 'span' );
		excerpt.className = 'wp-docs-search__result-excerpt';
		excerpt.textContent = result.excerpt || ( Array.isArray( result.headings ) ? result.headings.slice( 0, 3 ).join( ' · ' ) : '' );

		link.append( title, excerpt );
		resultsNode.appendChild( link );
	}

	function setActiveResult( nextIndex ) {
		if ( filteredResults.length === 0 ) {
			return;
		}

		activeResult = ( nextIndex + filteredResults.length ) % filteredResults.length;
		document.querySelectorAll( '[data-wp-docs-search-result]' ).forEach( ( result ) => {
			const isActive = Number( result.dataset.wpDocsSearchResult ) === activeResult;
			result.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			if ( isActive ) {
				result.scrollIntoView( { block: 'nearest' } );
			}
		} );
	}

	function openSearch() {
		const modal = document.querySelector( '[data-wp-docs-search]' );
		const input = document.querySelector( '[data-wp-docs-search-input]' );

		if ( ! modal || ! input ) {
			return;
		}

		modal.hidden = false;
		document.documentElement.classList.add( 'has-wp-docs-search-open' );
		input.focus();
		input.select();
		renderResults( search( input.value ), input.value );
	}

	function closeSearch() {
		const modal = document.querySelector( '[data-wp-docs-search]' );

		if ( ! modal ) {
			return;
		}

		modal.hidden = true;
		document.documentElement.classList.remove( 'has-wp-docs-search-open' );
	}

	function bindSearch() {
		const input = document.querySelector( '[data-wp-docs-search-input]' );

		document.querySelectorAll( '[data-wp-docs-search-open]' ).forEach( ( button ) => {
			button.addEventListener( 'click', openSearch );
		} );

		document.querySelectorAll( '[data-wp-docs-search-close]' ).forEach( ( button ) => {
			button.addEventListener( 'click', closeSearch );
		} );

		if ( input ) {
			input.addEventListener( 'input', () => renderResults( search( input.value ), input.value ) );
		}

		document.addEventListener( 'keydown', ( event ) => {
			const isSearchShortcut = ( event.metaKey || event.ctrlKey ) && event.key.toLowerCase() === 'k';

			if ( isSearchShortcut ) {
				event.preventDefault();
				openSearch();
				return;
			}

			const modal = document.querySelector( '[data-wp-docs-search]' );
			if ( ! modal || modal.hidden ) {
				return;
			}

			if ( event.key === 'Escape' ) {
				event.preventDefault();
				closeSearch();
			} else if ( event.key === 'ArrowDown' ) {
				event.preventDefault();
				setActiveResult( activeResult + 1 );
			} else if ( event.key === 'ArrowUp' ) {
				event.preventDefault();
				setActiveResult( activeResult - 1 );
			} else if ( event.key === 'Enter' && activeResult >= 0 && filteredResults[ activeResult ] ) {
				event.preventDefault();
				window.location.href = filteredResults[ activeResult ].url || '#';
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', () => {
		buildToc();
		bindSearch();
	} );
}() );
