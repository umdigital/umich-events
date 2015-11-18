<p>
    <label for="<?=$this->get_field_id('title');?>">
        Title:
        <input type="text" class="widefat" id="<?=$this->get_field_id('title');?>" name="<?=$this->get_field_name('title');?>" value="<?=esc_attr( $instance['title'] );?>" />
    </label>
</p>

<p>
    <label for="<?=$this->get_field_id('featured');?>">
        <input type="checkbox" class="checkbox" id="<?=$this->get_field_id('featured');?>" name="<?=$this->get_field_name('featured');?>" <?=( $instance['featured'] ? 'checked="checked"' : null);?> />
        Featured events only
    </label>
    <br/>
    <label for="<?=$this->get_field_id('ongoing');?>">
        <input type="checkbox" class="checkbox" id="<?=$this->get_field_id('ongoing');?>" name="<?=$this->get_field_name('ongoing');?>" <?=( $instance['ongoing'] ? 'checked="checked"' : null);?> />
        Show ongoing events
    </label>
    <br/>
    <label for="<?=$this->get_field_id('showimage');?>">
        <input type="checkbox" class="checkbox" id="<?=$this->get_field_id('showimage');?>" name="<?=$this->get_field_name('showimage');?>" <?=( $instance['showimage'] ? 'checked="checked"' : null);?> />
        Show event image
    </label>
</p>

<p>
    <label for="<?=$this->get_field_id('image-size');?>">
        Image Size:
        <select class="widefat" id="<?=$this->get_field_id('image-size');?>" name="<?=$this->get_field_name('image-size');?>">
            <? foreach( array_merge( array( 'full' ), get_intermediate_image_sizes() ) as $size ): ?>
            <option value="<?=$size;?>" <?=( $instance['image-size'] == $size ? 'selected="selected"' : null);?>><?=$size;?></option>
            <? endforeach; ?>
        </select>
    </label>
</p>

<? $meta = UmichEvents::getMetadata(); ?>
<p>
    <label for="<?=$this->get_field_id('tags');?>">
        Tags:
        <select class="jqmslist widefat" id="<?=$this->get_field_id('tags');?>" name="<?=$this->get_field_name('tags');?>[]" multiple="multiple">
            <? foreach( $meta->tags as $name => $id ): ?>
            <option value="<?=$id;?>"<?=(in_array( $id, $instance['tags'] ) ? ' selected="selected"' : null);?>><?=$name;?></option>
            <? endforeach; ?>
        </select>
    </label>
</p>

<p>
    <label for="<?=$this->get_field_id('groups');?>">
        Groups:
        <select class="jqmslist widefat" id="<?=$this->get_field_id('groups');?>" name="<?=$this->get_field_name('groups');?>[]" multiple="multiple">
            <? foreach( $meta->sponsors as $name => $id ): ?>
            <option value="<?=$id;?>"<?=(in_array( $id, $instance['groups'] ) ? ' selected="selected"' : null);?>><?=$name;?></option>
            <? endforeach; ?>
        </select>
    </label>
</p>

<p>
    <label for="<?=$this->get_field_id('locations');?>">
        Locations:
        <select class="jqmslist widefat" id="<?=$this->get_field_id('locations');?>" name="<?=$this->get_field_name('locations');?>[]" multiple="multiple">
            <? foreach( $meta->locations as $name => $id ): ?>
            <option value="<?=$id;?>"<?=(in_array( $id, $instance['locations'] ) ? ' selected="selected"' : null);?>><?=$name;?></option>
            <? endforeach; ?>
        </select>
    </label>
</p>

<p>
    <label for="<?=$this->get_field_id('viewalltxt');?>">
        View All Link Text:
        <input type="text" class="widefat" id="<?=$this->get_field_id('viewalltxt');?>" name="<?=$this->get_field_name('viewalltxt');?>" value="<?=esc_attr( $instance['viewalltxt'] );?>" /><br />
        <small>Leave blank for no link</small>
    </label>
</p>

<p>
    <label for="<?=$this->get_field_id('limit');?>">
        Limit:
        <input type="text" class="widefat" id="<?=$this->get_field_id('limit');?>" name="<?=$this->get_field_name('limit');?>" value="<?=esc_attr( $instance['limit'] );?>" /><br />
        <small>Max number of events to show.</small>
    </label>
</p>
