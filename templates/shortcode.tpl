<ul class="events">
    <?php foreach( $events as $event ): ?>
    <li><?php include $eventTemplate; ?></li>
    <?php endforeach; ?>
</ul>

<?php if( $atts['morelink'] ): ?>
<p class="more"><a href="<?=UmichEvents::getMoreURL();?>"><?=$atts['morelinktext'];?></a></p>
<?php endif; ?>
