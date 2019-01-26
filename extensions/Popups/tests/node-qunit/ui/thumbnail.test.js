import { createThumbnail, createThumbnailElement } from '../../../src/ui/thumbnail';
import * as constants from '../../../src/constants';

QUnit.module( 'ext.popups#thumbnail', {
	beforeEach() {
		this.sandbox.stub( constants.default, 'BRACKETED_DEVICE_PIXEL_RATIO' ).value( 1 );
	}
} );

QUnit.test( 'createThumbnail - tall image', ( assert ) => {
	const devicePixelRatio = constants.default.BRACKETED_DEVICE_PIXEL_RATIO,
		rawThumbnail = {
			source: 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8d/President_Barack_Obama.jpg/409px-President_Barack_Obama.jpg',
			width: 409,
			height: 512
		},
		thumbnail = createThumbnail( rawThumbnail );

	assert.strictEqual(
		thumbnail.isTall,
		true,
		'Thumbnail is tall.'
	);
	assert.strictEqual(
		thumbnail.width,
		thumbnail.width / devicePixelRatio,
		'Thumbnail width is correct.'
	);
	assert.strictEqual(
		thumbnail.height,
		thumbnail.height / devicePixelRatio,
		'Thumbnail height is correct.'
	);
} );

QUnit.test( 'createThumbnail - tall image element', ( assert ) => {
	const cases = [
		{
			width: 200,
			height: 300,
			// If thumbnail is less than SIZES.portraitImage we set x to 0
			// per https://phabricator.wikimedia.org/T192928#4312088
			expectedIsNarrow: true,
			expectedOffset: 3,
			expectedX: 0,
			expectedY: ( 300 - 250 ) / -2,
			// If thumbnail is less than SIZES.portraitImage we shrink thumbnail
			// per https://phabricator.wikimedia.org/T192928#4312088
			expectedSVGWidth: 200,
			expectedSVGHeight: 250,
			message: 'Width smaller than the predefined width (203).'
		},
		{
			width: 250,
			height: 300,
			expectedOffset: 0,
			expectedIsNarrow: false,
			expectedX: ( 250 - 203 ) / -2,
			expectedY: ( 300 - 250 ) / -2,
			expectedSVGWidth: 203,
			expectedSVGHeight: 250,
			message: 'Width bigger than the predefined width (203).'
		}
	];

	cases.forEach( ( case_ ) => {
		const thumbnail = createThumbnail( {
			source: 'https://image.url',
			width: case_.width,
			height: case_.height
		} );

		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'x' ) ),
			case_.expectedX,
			`Image element x coordinate is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'y' ) ),
			case_.expectedY,
			`Image element y coordinate is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'width' ) ),
			case_.width,
			`Image element width is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'height' ) ),
			case_.height,
			`Image element height is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.attr( 'width' ) ),
			case_.expectedSVGWidth,
			`Image SVG width is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.attr( 'height' ) ),
			case_.expectedSVGHeight,
			`Image SVG height is correct. ${ case_.message }`
		);
		assert.strictEqual(
			thumbnail.isNarrow,
			case_.expectedIsNarrow,
			`Image isNarrow is correct. ${ case_.message }`
		);
		assert.strictEqual(
			thumbnail.offset,
			case_.expectedOffset,
			`Image offset is correct. ${ case_.message }`
		);
	} );
} );

QUnit.test( 'createThumbnail - landscape image', ( assert ) => {
	const devicePixelRatio = constants.default.BRACKETED_DEVICE_PIXEL_RATIO,
		rawThumbnail = {
			source: 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8d/President_Barack_Obama.jpg/500px-President_Barack_Obama.jpg',
			width: 500,
			height: 400
		},
		thumbnail = createThumbnail( rawThumbnail );

	assert.strictEqual(
		thumbnail.isTall,
		false,
		'Thumbnail is not tall.'
	);
	assert.strictEqual(
		thumbnail.width,
		thumbnail.width / devicePixelRatio,
		'Thumbnail width is correct.'
	);
	assert.strictEqual(
		thumbnail.height,
		thumbnail.height / devicePixelRatio,
		'Thumbnail height is correct.'
	);
} );

QUnit.test( 'createThumbnail - landscape image element', ( assert ) => {
	const cases = [
		{
			width: 400,
			height: 150,
			expectedX: 0,
			expectedY: 0,
			expectedSVGWidth: 320,
			expectedSVGHeight: 150,
			message: 'Height smaller than the predefined height (200).'
		},
		{
			width: 400,
			height: 250,
			expectedX: 0,
			expectedY: ( 250 - 200 ) / -2,
			expectedSVGWidth: 320,
			expectedSVGHeight: 200,
			message: 'Height bigger than the predefined height (200).'
		}
	];

	cases.forEach( ( case_ ) => {
		const thumbnail = createThumbnail( {
			source: 'https://image.url',
			width: case_.width,
			height: case_.height
		} );

		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'x' ) ),
			case_.expectedX,
			`Image x coordinate is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'y' ) ),
			case_.expectedY,
			`Image y coordinate is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'width' ) ),
			case_.width,
			`Image element width is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.find( 'image' ).attr( 'height' ) ),
			case_.height,
			`Image element height is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.attr( 'width' ) ),
			case_.expectedSVGWidth,
			`Image SVG width is correct. ${ case_.message }`
		);
		assert.strictEqual(
			Number.parseFloat( thumbnail.el.attr( 'height' ) ),
			case_.expectedSVGHeight,
			`Image SVG height is correct. ${ case_.message }`
		);
	} );
} );

QUnit.test( 'createThumbnail - no raw thumbnail', ( assert ) => {
	const thumbnail = createThumbnail( null );

	assert.strictEqual( thumbnail, null, 'No thumbnail.' );
} );

QUnit.test( 'createThumbnail - small wide image', ( assert ) => {
	const rawThumbnail = {
			source: 'https://landscape-image.jpg',
			width: 299,
			height: 298
		},
		thumbnail = createThumbnail( rawThumbnail );

	assert.strictEqual( thumbnail, null, 'No thumbnail.' );
} );

QUnit.test( 'createThumbnail - small tall image', ( assert ) => {
	const rawThumbnail = {
			source: 'https://tall-image.jpg',
			width: 248,
			height: 249
		},
		thumbnail = createThumbnail( rawThumbnail );

	assert.strictEqual( thumbnail, null, 'No thumbnail.' );
} );

QUnit.test( 'createThumbnail - insecure URL', ( assert ) => {
	const cases = [
		'https://tall-ima\\ge.jpg',
		'https://tall-ima\'ge.jpg',
		'https://tall-ima"ge.jpg'
	];

	cases.forEach( ( case_ ) => {
		const thumbnail = createThumbnail( {
			source: case_,
			width: 500,
			height: 400
		} );

		assert.strictEqual( thumbnail, null, 'No thumbnail.' );
	} );
} );

QUnit.test( 'createThumbnailElement', ( assert ) => {
	const
		url = 'https://thumbnail.url',
		x = 25,
		y = 50,
		thumbnailWidth = 200,
		thumbnailHeight = 250,
		width = 500,
		height = 300;

	[
		{
			className: 'mwe-popups-is-not-tall',
			expectedPoints: '0 299 500 299',
			expectedHTML: '<image href="https://thumbnail.url" class="mwe-popups-is-not-tall" x="25" y="50" width="200" height="250"></image>'
		},
		{
			className: 'mwe-popups-is-tall',
			expectedPoints: '0 0 0 300'
		}
	].forEach( ( { className, expectedPoints, expectedHTML }, i ) => {
		const $thumbnail = createThumbnailElement(
			className, url, x, y, thumbnailWidth, thumbnailHeight,
			width, height );

		// Simplify HTML image test
		const points = $thumbnail.find( 'polyline' ).attr( 'points' );
		$thumbnail.find( 'polyline' ).remove();
		assert.strictEqual( points, expectedPoints, 'Points are correct.' );

		if ( expectedHTML ) {
			assert.strictEqual(
				$thumbnail.html(),
				expectedHTML,
				'Thumbnail HTML is correct.'
			);
		}
		if ( i === 0 ) {
			assert.strictEqual(
				$thumbnail.attr( 'xmlns' ),
				'http://www.w3.org/2000/svg',
				'SVG namespace is correct.'
			);
			assert.equal( $thumbnail.attr( 'height' ), height, 'SVG height is correct.' );
			assert.equal( $thumbnail.attr( 'width' ), width, 'SVG width is correct.' );
		}
	} );
} );
