<?=$args['before_widget'];?>

<?php if( $instance['title'] ): ?>
<?=$args['before_title'];?><?=$instance['title'];?><?=$args['after_title'];?>
<?php endif; ?>

<ul class="events">
    <?php foreach( $events as $event ): ?>
    <li><?php include $eventTemplate; ?></li>
    <?php endforeach; ?>
</ul>

<?php if( $instance['viewalltxt'] ): ?>
<p class="more"><a href="<?=UmichEvents::getMoreURL();?>"><?=$instance['viewalltxt'];?></a></p>
<?php endif; ?>

<?=$args['after_widget'];?>
