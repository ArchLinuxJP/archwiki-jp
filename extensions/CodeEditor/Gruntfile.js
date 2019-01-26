/*!
 * Grunt file
 *
 * @package CodeEditor
 */

/* eslint-env node */

module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );

	grunt.initConfig( {
		eslint: {
			all: [
				'**/*.js',
				'!node_modules/**',
				'!modules/ace/**',
				'!vendor/**'
			]
		},
		stylelint: {
			all: [
				'**/*.css',
				'**/*.less',
				'!node_modules/**',
				'!modules/ace/**',
				'!vendor/**'
			]
		},
		banana: conf.MessagesDirs,
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		exec: {
			'npm-update-ace': {
				cmd: 'npm update ace-builds',
				callback: function ( error, stdout, stderr ) {
					grunt.log.write( stdout );
					if ( stderr ) {
						grunt.log.write( 'Error: ' + stderr );
					}

					if ( error !== null ) {
						grunt.log.error( 'update error: ' + error );
					}
				}
			}
		},
		clean: {
			ace: [ 'modules/ace/*' ]
		},
		copy: {
			ace: {
				expand: true,
				cwd: 'node_modules/ace-builds/src-noconflict/',
				src: [ '**' ],
				dest: 'modules/ace/'
			},
			'ace-license': {
				expand: true,
				cwd: 'node_modules/ace-builds/',
				src: [ 'LICENSE' ],
				dest: 'modules/ace/'
			}
		}
	} );

	grunt.registerTask( 'update-ace', [ 'exec:npm-update-ace', 'clean:ace', 'copy:ace', 'copy:ace-license' ] );
	grunt.registerTask( 'test', [ 'eslint', 'stylelint', 'jsonlint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
