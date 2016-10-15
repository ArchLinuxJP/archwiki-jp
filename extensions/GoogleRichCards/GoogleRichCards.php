<?php
/**
 * GoogleRichCards
 * Google Rich Cards metadata generator
 *
 * PHP version 5.4
 *
 * @category Extension
 * @package  GoogleRichCards
 * @author   Igor Shishkin <me@teran.ru>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/teran/mediawiki-GoogleRichCards
 * */
$wgExtensionCredits['validextensionclass'][] = array(
   'name' => 'GoogleRichCards',
   'author' =>'Igor Shishkin',
   'url' => 'https://github.com/teran/mediawiki-GoogleRichCards'
);

if ( !defined( 'MEDIAWIKI' ) ) {
  echo( "This is a Mediawiki extension and doesn't provide standalone functionality\n" );
  die(1);
}

function GoogleRichCards(&$out) {
    global $wgLogo, $wgServer, $wgSitename, $wgTitle;
    if($wgTitle instanceof Title && $wgTitle->isContentPage()) {
      $ctime = DateTime::createFromFormat('YmdHis', $wgTitle->getEarliestRevTime());
      $mtime = DateTime::createFromFormat('YmdHis', $wgTitle->getTouched());
      if($ctime) {
        $created_timestamp = $ctime->format('c');
      } else {
        $created_timestamp = '0';
      }

      if($mtime) {
        $modified_timestamp = $mtime->format('c');
      } else {
        $modified_timestamp = '0';
      }


      $first_revision = $wgTitle->getFirstRevision();
      if($first_revision) {
        $author = $first_revision->getUserText();
      } else {
        $author = 'None';
      }

      $out->addHeadItem(
          'GoogleRichCards',
          '<script type="application/ld+json">
          {
             "@context": "http://schema.org",
             "@type": "Article",
             "mainEntityOfPage": {
               "@type": "WebPage",
               "@id": "'.$wgTitle->getFullURL().'"
             },
             "author": {
               "@type": "Person",
               "name": "'.$author.'"
             },
             "headline": "'.$wgTitle.'",
             "dateCreated": "'.$created_timestamp.'",
             "datePublished": "'.$created_timestamp.'",
             "dateModified": "'.$modified_timestamp.'",
             "discussionUrl": "'.$wgServer.'/'.$wgTitle->getTalkPage().'",
             "image": {
               "@type": "ImageObject",
               "url": "https://www.archlinuxjp.org/images/amplogo.png",
               "height": 1024,
               "width": 1024
             },
             "publisher": {
               "@type": "Organization",
               "name": "Arch Linux JP Project",
               "logo": {
                 "@type": "ImageObject",
                 "url": "https://www.archlinuxjp.org/images/archlogo.png"
               }
             },
             "description": "'.$wgTitle->getText().'",
             "potentialAction": {
               "@type": "SearchAction",
               "target": "https://wiki.archlinuxjp.org/index.php?title=%E7%89%B9%E5%88%A5:%E6%A4%9C%E7%B4%A2&search={search_term}",
               "query-input": "required name=search_term"
             }
           }
           </script>');
         }
}

$wgHooks['BeforePageDisplay'][] = 'GoogleRichCards';

?>
