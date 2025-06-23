(function(){
    const __ = wp.i18n.__;
    const { useBlockProps, InnerBlocks } = wp.blockEditor;
    const { createElement } = wp.element;

    wp.blocks.registerBlockType( 'umichevents/events', {
        edit: ( props ) => {
            // get available image sizes to choose from
            const imageSizes = wp.data.useSelect( (select) => {
                const { getSettings } = wp.data.select( wp.blockEditor.store );
                return getSettings().imageSizes.map(function( size ){
                    return {
                        value: size.slug,
                        label: size.name
                    }
                });
            }, [] );

            const [ eventMetadata, setEventMetadata ] = React.useState({
                'types'    : [{
                    value: '',
                    label: 'Loading Types...'
                }],
                'tags'     : [{
                    value: '',
                    label: 'Loading Tags...'
                }],
                'sponsors' : [{
                    value: '',
                    label: 'Loading Groups...'
                }],
                'locations': [{
                    value: '',
                    label: 'Loading Locations...'
                }]
            });

            React.useEffect(() => {
                wp.apiFetch({ path: '/umich-events/v1/metadata' }).then( (res) => {
                    let metadata = {
                        'types'    : [{
                            value: '',
                            label: 'Loading Types...'
                        }],
                        'tags'     : [{
                            value: '',
                            label: 'Loading Tags...'
                        }],
                        'sponsors' : [{
                            value: '',
                            label: 'Loading Groups...'
                        }],
                        'locations': [{
                            value: '',
                            label: 'Loading Locations...'
                        }]
                    };

                    if( res.hasOwnProperty('metadata') ) {
                        for( let mKey in metadata ) {
                            if( res.metadata.hasOwnProperty( mKey ) ) {
                                metadata[ mKey ] = [];
                                Object.entries( res.metadata[ mKey ] ).forEach( ([ key, val ]) => {
                                    metadata[ mKey ].push({
                                        value: val,
                                        label: key
                                    });
                                });
                            }
                        };
                    }
                    else {
                        Object.entries( metadata ).forEach(([key, value]) => {
                            if( value.length == 1 && value[0].label.startsWith('Loading ') ) {
                                metadata[ key ] = [{
                                    value: '-1',
                                    label: 'Error getting options.'
                                }];
                            }
                        });
                    }

                    setEventMetadata( metadata );
                });
            }, []);

            return createElement(
                'div',
                useBlockProps({
                    className: props.className
                }),
                createElement( wp.blockEditor.InspectorControls,
                    null,
                    createElement(
                        wp.components.PanelBody, {
                            title: 'Events Display Options',
                            initialOpen: true
                        },
                        createElement(
                            wp.components.ToggleControl, {
                                label: 'Show Image',
                                checked: props.attributes.showImage,
                                onChange: function( value ){
                                    props.setAttributes({
                                        showImage: value
                                    });
                                }
                            }
                        ),
                        (
                            props.attributes.showImage &&
                            createElement(
                                wp.components.SelectControl, {
                                    label: 'Image Size',
                                    checked: props.attributes.imageSize,
                                    options: [ ...imageSizes ],
                                    onChange: function( value ){
                                        props.setAttributes({
                                            imageSize: value
                                        });
                                    }
                                }
                            )
                        ),

                        createElement(
                            wp.components.ToggleControl, {
                                label: 'Featured Only',
                                checked: props.attributes.featured,
                                onChange: function( value ){
                                    props.setAttributes({
                                        featured: value
                                    });
                                }
                            }
                        ),
                        createElement(
                            wp.components.ToggleControl, {
                                label: 'Show Ongoing',
                                checked: props.attributes.ongoing,
                                onChange: function( value ){
                                    props.setAttributes({
                                        ongoing: value
                                    });
                                }
                            }
                        ),

                        createElement(
                            wp.components.ComboboxControl, {
                                label: 'Types',
                                checked: props.attributes.types,
                                options: [ ...eventMetadata.types ],
                                onChange: function( value ){
                                    let newTypes = [];
                                    eventMetadata.types.forEach( ( val ) => {
                                        if( val.value == value || props.attributes.types.find( x => x == val.value ) ) {
                                            newTypes.push( val.value );
                                        }
                                    });

                                    props.setAttributes({
                                        types: newTypes
                                    });
                                }
                            }
                        ),
                        createElement(
                            'ul', {
                                className: 'umich-events--filter-list'
                            },
                            props.attributes.types.map( (type) => {
                                let item = eventMetadata.types.find( x => x.value == type );

                                if( !item ) {
                                    return;
                                }

                                return createElement(
                                    'li', {
                                        'data-value': type,
                                    },
                                    createElement( 'span', {
                                        className: 'item-name'
                                    }, item.label ),
                                    createElement(
                                        wp.components.Icon, {
                                            icon: 'trash',
                                            title: 'remove type',
                                            className: 'item-remove',
                                            onClick: (event) => {
                                                event.preventDefault();

                                                if( confirm( 'Are you sure you want to remove "'+ item.label +'"?' ) ) {
                                                    props.setAttributes({
                                                        types: props.attributes.types.filter( (val) => {
                                                            return val == item.value ? false : true;
                                                        })
                                                    });
                                                }
                                            }
                                        },
                                    )
                                );
                            })
                        ),

                        createElement(
                            wp.components.ComboboxControl, {
                                label: 'Tags',
                                checked: props.attributes.tags,
                                options: [ ...eventMetadata.tags ],
                                onChange: function( value ){
                                    let newTags = [];
                                    eventMetadata.tags.forEach( ( val ) => {
                                        if( val.value == value || props.attributes.tags.find( x => x == val.value ) ) {
                                            newTags.push( val.value );
                                        }
                                    });

                                    props.setAttributes({
                                        tags: newTags
                                    });
                                }
                            }
                        ),
                        createElement(
                            'ul', {
                                className: 'umich-events--filter-list'
                            },
                            props.attributes.tags.map( (tag) => {
                                let item = eventMetadata.tags.find( x => x.value == tag );

                                if( !item ) {
                                    return;
                                }

                                return createElement(
                                    'li', {
                                        'data-value': tag,
                                    },
                                    createElement( 'span', {
                                        className: 'item-name'
                                    }, item.label ),
                                    createElement(
                                        wp.components.Icon, {
                                            icon: 'trash',
                                            title: 'remove tag',
                                            className: 'item-remove',
                                            onClick: (event) => {
                                                event.preventDefault();

                                                if( confirm( 'Are you sure you want to remove "'+ item.label +'"?' ) ) {
                                                    props.setAttributes({
                                                        tags: props.attributes.tags.filter( (val) => {
                                                            return val == item.value ? false : true;
                                                        })
                                                    });
                                                }
                                            }
                                        },
                                    )
                                );
                            })
                        ),

                        createElement(
                            wp.components.ComboboxControl, {
                                label: 'Groups',
                                checked: props.attributes.groups,
                                options: [ ...eventMetadata.sponsors ],
                                onChange: function( value ){
                                    let newGroups = [];
                                    eventMetadata.sponsors.forEach( ( val ) => {
                                        if( val.value == value || props.attributes.groups.find( x => x == val.value ) ) {
                                            newGroups.push( val.value );
                                        }
                                    });

                                    props.setAttributes({
                                        groups: newGroups
                                    });
                                }
                            }
                        ),
                        createElement(
                            'ul', {
                                className: 'umich-events--filter-list'
                            },
                            props.attributes.groups.map( (group) => {
                                let item = eventMetadata.sponsors.find( x => x.value == group );

                                if( !item ) {
                                    return;
                                }

                                return createElement(
                                    'li', {
                                        'data-value': group,
                                    },
                                    createElement( 'span', {
                                        className: 'item-name'
                                    }, item.label ),
                                    createElement(
                                        wp.components.Icon, {
                                            icon: 'trash',
                                            title: 'remove group',
                                            className: 'item-remove',
                                            onClick: (event) => {
                                                event.preventDefault();

                                                if( confirm( 'Are you sure you want to remove "'+ item.label +'"?' ) ) {
                                                    props.setAttributes({
                                                        groups: props.attributes.groups.filter( (val) => {
                                                            return val == item.value ? false : true;
                                                        })
                                                    });
                                                }
                                            }
                                        },
                                    )
                                );
                            })
                        ),

                        createElement(
                            wp.components.ComboboxControl, {
                                label: 'Locations',
                                checked: props.attributes.locations,
                                options: [ ...eventMetadata.locations ],
                                onChange: function( value ){
                                    let newLocations = [];
                                    eventMetadata.locations.forEach( ( val ) => {
                                        if( val.value == value || props.attributes.locations.find( x => x == val.value ) ) {
                                            newLocations.push( val.value );
                                        }
                                    });

                                    props.setAttributes({
                                        locations: newLocations
                                    });
                                }
                            }
                        ),
                        createElement(
                            'ul', {
                                className: 'umich-events--filter-list'
                            },
                            props.attributes.locations.map( (location) => {
                                let item = eventMetadata.locations.find( x => x.value == location );

                                if( !item ) {
                                    return;
                                }

                                return createElement(
                                    'li', {
                                        'data-value': location,
                                    },
                                    createElement( 'span', {
                                        className: 'item-name'
                                    }, item.label ),
                                    createElement(
                                        wp.components.Icon, {
                                            icon: 'trash',
                                            title: 'remove location',
                                            className: 'item-remove',
                                            onClick: (event) => {
                                                event.preventDefault();

                                                if( confirm( 'Are you sure you want to remove "'+ item.label +'"?' ) ) {
                                                    props.setAttributes({
                                                        locations: props.attributes.locations.filter( (val) => {
                                                            return val == item.value ? false : true;
                                                        })
                                                    });
                                                }
                                            }
                                        },
                                    )
                                );
                            })
                        ),

                        createElement(
                            wp.components.ToggleControl, {
                                label: 'Show More Link',
                                checked: props.attributes.moreLink,
                                onChange: function( value ){
                                    props.setAttributes({
                                        moreLink: value
                                    });
                                }
                            }
                        ),
                        (
                            props.attributes.moreLink &&
                            createElement(
                                wp.components.TextControl, {
                                    label: 'More Link Text',
                                    type:  'text',
                                    value: props.attributes.moreLinkText,
                                    onChange: function( value ){
                                        props.setAttributes({
                                            moreLinkText: value
                                        });
                                    }
                                }
                            )
                        ),

                        createElement(
                            wp.components.TextControl, {
                                label: 'Limit',
                                type:  'number',
                                value: props.attributes.limit,
                                onChange: function( value ){
                                    props.setAttributes({
                                        limit: parseInt( value )
                                    });
                                }
                            }
                        )
                    )
                ),
                createElement( wp.serverSideRender, {
                    block: 'umichevents/events',
                    attributes: { ...props.attributes, ...{inEditor: true} }
                })
            )
        }
    });
}());
