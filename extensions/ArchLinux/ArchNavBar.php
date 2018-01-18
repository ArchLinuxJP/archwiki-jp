<?php

namespace MediaWiki\Extensions\ArchLinux;

/**
 * @var \SkinTemplate $skinTemplate
 * @var array $archNavBar
 * @var string $archHome
 * @var array $archNavBarSelected
 * @var string $archNavBarSelectedDefault
 */
?>
<div id="archnavbar" class="noprint">
    <?php $today = getdate();if($today["mon"]==12&&$today["mday"]>14){ ?>
    <div id="archnavbarlogo" class="xmas">
    <?php }else{ ?>
    <div id="archnavbarlogo">
    <?php } ?>
        <p><a id="logo" href="<?= $archHome ?>"></a></p>
    </div>
    <div id="archnavbaricon"></div>
    <div id="archnavbarmenu">
        <ul id="archnavbarlist">
            <?php
            foreach ($archNavBar as $name => $url) {
                if (($skinTemplate->getTitle() == $name && in_array($name, $archNavBarSelected))
                    || (!(in_array($skinTemplate->getTitle(), $archNavBarSelected)) && $name == $archNavBarSelectedDefault)) {
                    $anbClass = ' class="anb-selected"';
                } else {
                    $anbClass = '';
                }
                ?>
            <li id="anb-<?= strtolower($name) ?>"<?= $anbClass ?>><a href="<?= $url ?>"><?= $name ?></a></li><?php
            }
            ?>
        </ul>
    </div>
</div>
