<div class="<?=$instance['className'];?>">
    <ul class="events">
        <?php foreach( $events as $event ): ?>
        <li>
            <?php if( $instance['showImage'] && $event->image_url ): ?>
            <img src="<?=UmichEvents::getResizedEventImage( $event->image_url, $instance['imageSize'] );?>" />
            <?php endif; ?>
            <span class="month-date">
                <span class="month"><?=date( 'M', strtotime( $event->datetime_start ) );?></span>
                <span class="date"><?=date( 'j', strtotime( $event->datetime_start ) );?></span>
            </span>
            <h5><a href="<?=$event->permalink;?>"><?=$event->event_title;?></a></h5>
            <?php if( $event->event_subtitle ): ?>
            <h6><?=$event->event_subtitle;?></h6>
            <?php endif; ?>
            <span class="location"><?=$event->building_name;?></span>
            <span class="time">@ <?=date( 'g:ia', strtotime( date( 'Y-m-d '. $event->time_start ) ) );?></span>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if( $instance['moreLink'] ): ?>
    <p class="more"><a href="<?=UmichEvents::getMoreURL();?>"><?=$instance['moreLinkText'];?></a></p>
    <?php endif; ?>
</div>
