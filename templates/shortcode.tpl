<ul class="events">
    <? foreach( $events as $event ): ?>
    <li><? include $eventTemplate; ?></li>
    <? endforeach; ?>
</ul>

<? if( $atts['morelink'] ): ?>
<p class="more"><a href="<?=UmichEvents::getMoreURL();?>"><?=$atts['morelinktext'];?></a></p>
<? endif; ?>
