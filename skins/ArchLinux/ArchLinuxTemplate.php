<?php
/**
 * ArchLinux skin (based on MonoBook)
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Skins
 */

/**
 * @ingroup Skins
 */
class ArchLinuxTemplate extends BaseTemplate {

	/**
	 * Template filter callback for ArchLinux skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgArchNavBar, $wgArchHome, $wgArchNavBarSelected, $wgArchNavBarSelectedDefault;
		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();

		$this->html( 'headelement' );
		?>

		<div id="archnavbar"><!-- Arch Linux global navigation bar -->
		<?php $today = getdate();if($today["mon"]==12&&$today["mday"]>14){ ?><div id="archnavbarlogo" class="xmas"><?php }else{ ?><div id="archnavbarlogo"><?php } ?>
				<p><a id="logo" href="https://www.archlinux.jp/" title="メインページに戻る"></a></p>
			</div>
			<div id="archnavbaricon"><img src="https://www.archlinux.jp/images/menu.png"></div>
			<div id="archnavbarmenu">
				<ul id="archnavbarlist">
		<li id="anb-home"><a href="https://www.archlinux.jp/" title="Arch ニュース、パッケージ、プロジェクトなど">ホーム</a></li>
		<li id="anb-packages"><a href="https://www.archlinux.jp/packages/" title="Arch パッケージデータベース">パッケージ</a></li>
		<li id="anb-forums"><a href="https://bbs.archlinux.jp/" title="コミュニティフォーラム">フォーラム</a></li>
		<li id="anb-wiki" class="anb-selected"><a href="https://wiki.archlinux.jp/" title="コミュニティドキュメント">ArchWiki</a></li>
		<li id="anb-bugs"><a href="https://slack.archlinux.jp/" title="チャット">Slack</a></li>
		<li id="anb-aur"><a href="https://aur.archlinux.org/" title="Arch Linux User Repository">AUR</a></li>
		<li id="anb-download"><a href="https://www.archlinux.jp/download/" title="Arch Linux を入手">ダウンロード</a></li>
				</ul>
			</div>
		</div><!-- #archnavbar -->

		<div id="globalWrapper">
		<div id="column-content">
			<div id="content" class="mw-body" role="main">
				<a id="top"></a>
				<?php
				if ( $this->data['sitenotice'] ) {
					?>
					<div id="siteNotice"><?php
					$this->html( 'sitenotice' )
					?></div><?php
				}
				?>

				<?php
				echo $this->getIndicators();
				// Loose comparison with '!=' is intentional, to catch null and false too, but not '0'
				if ( $this->data['title'] != '' ) {
				?>
				<h1 id="firstHeading" class="firstHeading" lang="<?php
				$this->data['pageLanguage'] =
					$this->getSkin()->getTitle()->getPageViewLanguage()->getHtmlCode();
				$this->text( 'pageLanguage' );
				?>"><?php $this->html( 'title' ) ?></h1>
				<?php } ?>

				<div id="bodyContent" class="mw-body-content">
					<div id="siteSub"><?php $this->msg( 'tagline' ) ?></div>
					<div id="contentSub"<?php
					$this->html( 'userlangattributes' ) ?>><?php $this->html( 'subtitle' )
						?></div>
					<?php if ( $this->data['undelete'] ) { ?>
						<div id="contentSub2"><?php $this->html( 'undelete' ) ?></div>
					<?php
}
					?><?php
					if ( $this->data['newtalk'] ) {
						?>
						<div class="usermessage"><?php $this->html( 'newtalk' ) ?></div>
					<?php
					}
					?>
					<div id="jump-to-nav" class="mw-jump"><?php
						$this->msg( 'jumpto' )
						?> <a href="#column-one"><?php
							$this->msg( 'jumptonavigation' )
							?></a><?php
						$this->msg( 'comma-separator' )
						?><a href="#searchInput"><?php
							$this->msg( 'jumptosearch' )
							?></a></div>

					<!-- start content -->
					<?php $this->html( 'bodytext' ) ?>
					<?php
					if ( $this->data['catlinks'] ) {
						$this->html( 'catlinks' );
					}
					?>
					<!-- end content -->
					<?php
					if ( $this->data['dataAfterContent'] ) {
						$this->html( 'dataAfterContent'
						);
					}
					?>
					<div class="visualClear"></div>
				</div>
			</div>
		</div>
		<div id="column-one"<?php $this->html( 'userlangattributes' ) ?>>
			<h2><?php $this->msg( 'navigation-heading' ) ?></h2>
			<?php $this->cactions(); ?>
			<div class="portlet" id="p-personal" role="navigation">
				<h3><?php $this->msg( 'personaltools' ) ?></h3>

				<div class="pBody">
					<ul<?php $this->html( 'userlangattributes' ) ?>>
						<?php foreach ( $this->getPersonalTools() as $key => $item ) { ?>
							<?php echo $this->makeListItem( $key, $item ); ?>

						<?php
}
						?>
					</ul>
				</div>
			</div>
			<div class="portlet" id="p-logo" role="banner">
				<?php
				echo Html::element( 'a', array(
						'href' => $this->data['nav_urls']['mainpage']['href'],
						'class' => 'mw-wiki-logo',
						)
						+ Linker::tooltipAndAccesskeyAttribs( 'p-logo' )
				); ?>

			</div>
			<?php
			$this->renderPortals( $this->data['sidebar'] );
			?>
		</div><!-- end of the left (by default at least) column -->
		<div class="visualClear"></div>
		<?php
		$validFooterIcons = $this->getFooterIcons( "icononly" );
		$validFooterLinks = $this->getFooterLinks( "flat" ); // Additional footer links

		if ( count( $validFooterIcons ) + count( $validFooterLinks ) > 0 ) {
			?>
			<div id="footer" role="contentinfo"<?php $this->html( 'userlangattributes' ) ?>>
			<?php
			$footerEnd = '</div>';
		} else {
			$footerEnd = '';
		}

		foreach ( $validFooterIcons as $blockName => $footerIcons ) {
			?>
			<div id="f-<?php echo htmlspecialchars( $blockName ); ?>ico">
				<?php foreach ( $footerIcons as $icon ) { ?>
					<?php echo $this->getSkin()->makeFooterIcon( $icon ); ?>

				<?php
}
				?>
			</div>
		<?php
		}

		if ( count( $validFooterLinks ) > 0 ) {
			?>
			<ul id="f-list">
				<?php
				foreach ( $validFooterLinks as $aLink ) {
					?>
					<li id="<?php echo $aLink ?>"><?php $this->html( $aLink ) ?></li>
				<?php
				}
				?>
			</ul>
		<?php
		}

		echo $footerEnd;
		?>

		</div>
		<?php
		$this->printTrail();
		echo Html::closeElement( 'body' );
		echo Html::closeElement( 'html' );
		echo "\n";
		wfRestoreWarnings();
	} // end of execute() method

	/*************************************************************************************************/

	/**
	 * @param array $sidebar
	 */
	protected function renderPortals( $sidebar ) {
		if ( !isset( $sidebar['SEARCH'] ) ) {
			$sidebar['SEARCH'] = true;
		}
		if ( !isset( $sidebar['TOOLBOX'] ) ) {
			$sidebar['TOOLBOX'] = true;
		}
		if ( !isset( $sidebar['LANGUAGES'] ) ) {
			$sidebar['LANGUAGES'] = true;
		}

		foreach ( $sidebar as $boxName => $content ) {
			if ( $content === false ) {
				continue;
			}

			// Numeric strings gets an integer when set as key, cast back - T73639
			$boxName = (string)$boxName;

			if ( $boxName == 'SEARCH' ) {
				$this->searchBox();
			} elseif ( $boxName == 'TOOLBOX' ) {
				$this->toolbox();
			} elseif ( $boxName == 'LANGUAGES' ) {
				$this->languageBox();
			} else {
				$this->customBox( $boxName, $content );
			}
		}
	}

	function searchBox() {
		?>
		<div id="p-search" class="portlet" role="search">
			<h3><label for="searchInput"><?php $this->msg( 'search' ) ?></label></h3>

			<div id="searchBody" class="pBody">
				<form action="<?php $this->text( 'wgScript' ) ?>" id="searchform">
					<input type='hidden' name="title" value="<?php $this->text( 'searchtitle' ) ?>"/>
					<?php echo $this->makeSearchInput( array( "id" => "searchInput" ) ); ?>

					<?php
					echo $this->makeSearchButton(
						"go",
						array( "id" => "searchGoButton", "class" => "searchButton" )
					);

					if ( $this->config->get( 'UseTwoButtonsSearchForm' ) ) {
						?>&#160;
						<?php echo $this->makeSearchButton(
							"fulltext",
							array( "id" => "mw-searchButton", "class" => "searchButton" )
						);
					} else {
						?>

						<div><a href="<?php
						$this->text( 'searchaction' )
						?>" rel="search"><?php $this->msg( 'powersearch-legend' ) ?></a></div><?php
					} ?>

				</form>

				<?php $this->renderAfterPortlet( 'search' ); ?>
			</div>
		</div>
	<?php
	}

	/**
	 * Prints the cactions bar.
	 * Shared between MonoBook and Modern
	 */
	function cactions() {
		?>
		<div id="p-cactions" class="portlet" role="navigation">
			<h3><?php $this->msg( 'views' ) ?></h3>

			<div class="pBody">
				<ul><?php
					foreach ( $this->data['content_actions'] as $key => $tab ) {
						echo '
				' . $this->makeListItem( $key, $tab );
					} ?>

				</ul>
				<?php $this->renderAfterPortlet( 'cactions' ); ?>
			</div>
		</div>
	<?php
	}

	/*************************************************************************************************/
	function toolbox() {
		?>
		<div class="portlet" id="p-tb" role="navigation">
			<h3><?php $this->msg( 'toolbox' ) ?></h3>

			<div class="pBody">
				<ul>
					<?php
					foreach ( $this->getToolbox() as $key => $tbitem ) {
						?>
						<?php echo $this->makeListItem( $key, $tbitem ); ?>

					<?php
					}
					Hooks::run( 'ArchLinuxTemplateToolboxEnd', array( &$this ) );
					Hooks::run( 'SkinTemplateToolboxEnd', array( &$this, true ) );
					?>
				</ul>
				<?php $this->renderAfterPortlet( 'tb' ); ?>
			</div>
		</div>
	<?php
	}

	/*************************************************************************************************/
	function languageBox() {
		if ( $this->data['language_urls'] !== false ) {
			?>
			<div id="p-lang" class="portlet" role="navigation">
				<h3<?php $this->html( 'userlangattributes' ) ?>><?php $this->msg( 'otherlanguages' ) ?></h3>

				<div class="pBody">
					<ul>
						<?php foreach ( $this->data['language_urls'] as $key => $langlink ) { ?>
							<?php echo $this->makeListItem( $key, $langlink ); ?>

						<?php
}
						?>
					</ul>

					<?php $this->renderAfterPortlet( 'lang' ); ?>
				</div>
			</div>
		<?php
		}
	}

	/*************************************************************************************************/
	/**
	 * @param string $bar
	 * @param array|string $cont
	 */
	function customBox( $bar, $cont ) {
		$portletAttribs = array(
			'class' => 'generated-sidebar portlet',
			'id' => Sanitizer::escapeId( "p-$bar" ),
			'role' => 'navigation'
		);

		$tooltip = Linker::titleAttrib( "p-$bar" );
		if ( $tooltip !== false ) {
			$portletAttribs['title'] = $tooltip;
		}
		echo '	' . Html::openElement( 'div', $portletAttribs );
		$msgObj = wfMessage( $bar );
		?>

		<h3><?php echo htmlspecialchars( $msgObj->exists() ? $msgObj->text() : $bar ); ?></h3>
		<div class='pBody'>
			<?php
			if ( is_array( $cont ) ) {
				?>
				<ul>
					<?php
					foreach ( $cont as $key => $val ) {
						?>
						<?php echo $this->makeListItem( $key, $val ); ?>

					<?php
					}
					?>
				</ul>
			<?php
			} else {
				# allow raw HTML block to be defined by extensions
				print $cont;
			}

			$this->renderAfterPortlet( $bar );
			?>
		</div>
		</div>
	<?php
	}
} // end of class
