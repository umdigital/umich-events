<? if( $instance['showimage'] && $event->image_url ): ?>
<? //var_dump( UmichEvents::getResizedEventImage( $event->image_url, $instance['image-size'] ) ); ?>
<img src="<?=UmichEvents::getResizedEventImage( $event->image_url, $instance['image-size'] );?>" />
<? endif; ?>
<span class="month-date">
    <span class="month"><?=date( 'M', strtotime( $event->datetime_start ) );?></span>
    <span class="date"><?=date( 'j', strtotime( $event->datetime_start ) );?></span>
</span>
<h5><a href="<?=$event->permalink;?>"><?=$event->event_title;?></a></h5>
<h6><?=$event->event_subtitle;?></h6>
<span class="location"><?=$event->building_name;?></span>
@
<span class="time"><?=date( 'H:ia', strtotime( date( 'Y-m-d '. $event->time_start ) ) );?></span>
