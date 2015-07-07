<?=$args['before_widget'];?>

<? if( $instance['title'] ): ?>
<?=$args['before_title'];?><?=$instance['title'];?><?=$args['after_title'];?>
<? endif; ?>

<ul class="events">
    <? foreach( $events as $event ): ?>
    <li><? include $eventTemplate; ?></li>
    <? endforeach; ?>
</ul>

<? if( $instance['viewalltxt'] ): ?>
<p class="more"><a href="<?=UmichEvents::getMoreURL();?>"><?=$instance['viewalltxt'];?></a></p>
<? endif; ?>

<?=$args['after_widget'];?>
