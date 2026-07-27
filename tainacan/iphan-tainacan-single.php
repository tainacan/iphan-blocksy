<?php
/**
 * Template para mostrar o Museu
 */

$prefix = blocksy_manager()->screen->get_prefix();

$page_structure_type = get_theme_mod( $prefix . '_page_structure_type', 'type-dam');
$template_columns_style = '';
$display_items_related_to_this = get_theme_mod( $prefix . '_display_items_related_to_this', 'no' ) === 'yes';
$column_documents_attachments_affix = get_theme_mod( $prefix . '_document_attachments_affix', 'no') === 'yes';
$metadata_list_structure_type = get_theme_mod($prefix . '_metadata_list_structure_type', 'metadata-type-1');
$exclude_title_metadata = get_theme_mod($prefix . '_show_title_metadata', 'yes') === 'no';
$show_thumbnail_with_metadata = get_theme_mod($prefix . '_show_thumbnail', 'no') === 'yes';
$display_section_labels = get_theme_mod($prefix . '_display_section_labels', 'yes') == 'yes';
$items_related_to_this_position = get_theme_mod( $prefix . '_iphan_blocksy_items_related_to_this_position', 'bottom');

if ($page_structure_type == 'type-gm' || $page_structure_type == 'type-mg') {
    $column_documents_attachments_width = 60;
    $column_metadata_width = 40;

    $column_documents_attachments_width = intval(substr(get_theme_mod( $prefix . '_document_attachments_columns', '60%'), 0, -1));
    $column_metadata_width = 100 - $column_documents_attachments_width;

    if ($page_structure_type == 'type-gm') {
        $template_columns_style = 'grid-template-columns: ' . $column_documents_attachments_width . '% calc(' . $column_metadata_width . '% - 48px);';
    } else {
        $template_columns_style = 'grid-template-columns: ' . $column_metadata_width . '% calc(' . $column_documents_attachments_width . '% - 48px);';
    }
}

// Arrays para guardar os IDs das seções de metadados dependendo da área do template
$default_template_area_sections = [];
$collapses_template_area_sections = [];
$sidebar_template_area_sections = [];
$tabs_template_area_sections = [];

$Tainacan_Metadata_Sections = \Tainacan\Repositories\Metadata_Sections::get_instance();
$metadata_sections = $Tainacan_Metadata_Sections->fetch_by_collection(tainacan_get_collection(), array());

foreach ($metadata_sections as $metadata_section) {
    
    $metadata_section_id = $metadata_section->get_id();
    $template_area = get_post_meta($metadata_section_id, IphanBlocksyMetadataSectionHooks::get_instance()->template_area_field, true);

    if ($template_area === 'sidebar') {
        $sidebar_template_area_sections[] = $metadata_section_id;
    } elseif ($template_area === 'collapses') {
        $collapses_template_area_sections[] = $metadata_section_id;
    } elseif ($template_area === 'tabs') {
        $tabs_template_area_sections[] = $metadata_section_id;
    } else {
        $default_template_area_sections[] = $metadata_section_id;
    }
}

$metadata_args = array(
    'display_slug_as_class' => true,
    'before' 				=> '<div class="tainacan-item-section__metadatum metadata-type-$type" $id>',
    'after' 				=> '</div>',
    'before_title' => '<h3 class="tainacan-metadata-label">',
    'after_title' => '</h3>',
    'before_value' => '<p class="tainacan-metadata-value">',
    'after_value' => '</p>',
    'exclude_title' => $exclude_title_metadata
);

$sections_args = array(
    'before' => '<section class="tainacan-item-section tainacan-item-section--metadata">',
    'after' => '</section>',
    'before_name' => '<h2 class="tainacan-single-item-section" id="metadata-section-$slug">',
    'after_name' => '</h2>',
    'hide_name' => !$display_section_labels,
    'before_metadata_list' => do_action( 'tainacan-blocksy-single-item-metadata-begin' ) . '<div class="tainacan-item-section__metadata ' . $metadata_list_structure_type . '">',
    'after_metadata_list' => '</div>' . do_action( 'tainacan-blocksy-single-item-metadata-end' ),
    'metadata_list_args' => $metadata_args
);

function iphan_blocksy_tweak_metadata_section_label($before, $metadata_section) {

    $should_hide_label = get_post_meta($metadata_section->get_ID(), IphanBlocksyMetadataSectionHooks::get_instance()->hide_label_field, true);
    $should_hide_label = is_array($should_hide_label) ? $should_hide_label : array($should_hide_label);

    if ( !empty($should_hide_label) && $should_hide_label[0] === 'yes' )
        return str_replace('tainacan-single-item-section', 'tainacan-single-item-section screen-reader-text', $before);

    $icon = get_post_meta($metadata_section->get_ID(), IphanBlocksyMetadataSectionHooks::get_instance()->icon_field, true);
    
    if ( empty($icon) )
        return $before;
    
    return str_replace('<h2', '<i class="ph ph-' . $icon . '"></i><h2', $before);
}
add_filter('tainacan-get-metadata-section-as-html-before-name', 'iphan_blocksy_tweak_metadata_section_label', 10, 2);

function iphan_blocksy_tweak_metadatum_label($before, $item_metadatum) {

    $should_hide_label = get_post_meta($item_metadatum->get_metadatum()->get_id(), IphanBlocksyMetadatumHooks::get_instance()->hide_label_field, true);
    $should_hide_label = is_array($should_hide_label) ? $should_hide_label : array($should_hide_label);

    if ( !empty($should_hide_label) && $should_hide_label[0] === 'yes' )
        return str_replace('tainacan-metadata-label', 'tainacan-metadata-label screen-reader-text', $before);

    $icon = get_post_meta($item_metadatum->get_metadatum()->get_id(), IphanBlocksyMetadatumHooks::get_instance()->icon_field, true);
    
    if ( !empty($icon) )
        $before = str_replace('<h3', '<i class="ph ph-' . $icon . '"></i><h3', $before);

    return $before;
}
add_filter('tainacan-get-item-metadatum-as-html-before-title', 'iphan_blocksy_tweak_metadatum_label', 10, 2);

function iphan_blocksy_tweak_metadatum_value($before, $item_metadatum) {

    $metadata_type = $item_metadatum->get_metadatum()->get_metadata_type();
    if ( $metadata_type !== 'Tainacan\\Metadata_Types\\Relationship' && $metadata_type !== 'Tainacan\\Metadata_Types\\Taxonomy' )
        return $before;

    $style = get_post_meta($item_metadatum->get_metadatum()->get_id(), IphanBlocksyMetadatumHooks::get_instance()->style_field, true);
    if ( $style )
        return str_replace('tainacan-metadata-value', 'tainacan-metadata-value tainacan-metadata-value--' . $style, $before);

    return $before;
}
add_filter('tainacan-get-item-metadatum-as-html-before-value', 'iphan_blocksy_tweak_metadatum_value', 0, 2);

function iphan_blocksy_set_relationship_thumbnail_size() {
    return 'tainacan-medium';
}
add_filter( 'tainacan-item-metadata-relationship-get-item-thumbnail', 'iphan_blocksy_set_relationship_thumbnail_size' );

function iphan_blocksy_keep_spacing_on_minimum_mode($options) {
    return array_merge(
        $options,
        array(
            'spaceBetween' => '8'
        )
    );
}
add_filter( 'tainacan-swiper-thumbs-options', 'iphan_blocksy_keep_spacing_on_minimum_mode', 11 , 1);

do_action( 'tainacan-blocksy-single-item-top' ); 
do_action( 'tainacan-blocksy-single-item-after-title' );

?>

<div class="<?php echo esc_attr('tainacan-item-single tainacan-item-single--layout-'. $page_structure_type . ($column_documents_attachments_affix ? ' tainacan-item-single--affix-column' : '')) ?>" style="<?php echo esc_attr($template_columns_style) ?>">

    <?php

    if ( $page_structure_type !== 'type-gtm' ) {
        tainacan_blocksy_get_template_part( 'template-parts/tainacan-item-single-document' );
        do_action( 'tainacan-blocksy-single-item-after-document' );  

        tainacan_blocksy_get_template_part( 'template-parts/tainacan-item-single-attachments' );
        do_action( 'tainacan-blocksy-single-item-after-attachments' );
    }

    if ( $display_items_related_to_this && $items_related_to_this_position === 'top' ) {
        tainacan_blocksy_get_template_part( 'template-parts/tainacan-item-single-items-related-to-this' );
        do_action( 'tainacan-blocksy-single-item-after-items-related-to-this' );
    }
   
    if ( count( $default_template_area_sections ) > 0 ) : ?>
        <div class="tainacan-item-section tainacan-item-section--metadata-sections iphan-blocksy-metadata-section--default">
            
            <?php
                /** 
                 * The new metadata sections function makes it a bit more complicated to add
                 * the thumbnail in the middle of the metadata.
                 * So we have some logic that is only needed if it is set.
                 * The following uses a filter to add it right above the first metadatum in the default section.
                 **/
                if ( has_post_thumbnail() && $show_thumbnail_with_metadata ) {

                    add_filter('tainacan-get-metadata-section-as-html-before-metadata-list--index-0', function( $before_description, $metadata_section) {
                        
                        ob_start();
                        ?>
                            <div class="tainacan-item-section__metadata-thumbnail">
                                <h3 class="tainacan-metadata-label"><?php esc_html_e( 'Thumbnail', 'tainacan-blocksy' ); ?></h3>
                                <p class="tainacan-metadata-value"><?php the_post_thumbnail('tainacan-medium-full'); ?></p>
                            </div>
                        <?php
                
                        $extra_content = ob_get_contents();
                        ob_end_clean();
                
                        return $before_description . $extra_content;

                    }, 10, 2);

                }

                tainacan_the_metadata_sections( array_merge(
                    $sections_args,
                    array(
                        'metadata_sections__in' => $default_template_area_sections
                    )
                ) );
                do_action( 'tainacan-blocksy-single-item-after-metadata' );
            ?>
        </div> 
    <?php endif;

    if ( count( $sidebar_template_area_sections ) > 0 ) {
        $sidebar_section_content = '';

        ob_start();

        tainacan_the_metadata_sections( array_merge(
            $sections_args,
            array(
                'metadata_sections__in' => $sidebar_template_area_sections,
                'metadata_sections__not_in' => array_merge($tabs_template_area_sections, $default_template_area_sections, $collapses_template_area_sections),
            )
        ) );

        $sidebar_section_content = ob_get_contents();
        ob_end_clean();
            
        if ( !empty($sidebar_section_content) ) : ?>
        <div class="tainacan-item-section tainacan-item-section--metadata-sections iphan-blocksy-metadata-section--sidebar">
            <div class="metadata-section-layout--sidebar">
                <?php 
                    echo $sidebar_section_content;
                ?>
            </div>
        </div>
        <?php endif;
    
    }

    if ( count( $collapses_template_area_sections ) > 0 ) : ?>
        <div class="tainacan-item-section tainacan-item-section--metadata-sections iphan-blocksy-metadata-section--collapses">
            <div class="metadata-section-layout--collapses">
                <?php

                    function iphan_blocksy_add_checkbox_to_collapses_metadata_section($before, $item_metadatum) {
                        return str_replace('<input', '<input checked="checked"', $before);
                    }
                    add_filter('tainacan-get-metadata-section-as-html-before-name--index-0', 'iphan_blocksy_add_checkbox_to_collapses_metadata_section', 10, 2);

                    tainacan_the_metadata_sections( array_merge(
                        $sections_args,
                        array(
                            'metadata_sections__in' => $collapses_template_area_sections,
                            'metadata_sections__not_in' => array_merge($sidebar_template_area_sections, $tabs_template_area_sections, $default_template_area_sections),
                            'before' => '',
                            'after' => '',
                            'before_name' => '<input name="collapses" type="checkbox" id="collapse-section-$id"/>
                                        <label for="collapse-section-$id">
                                            <i class="tainacan-icon tainacan-icon-arrowright"></i>
                                            <h2 class="tainacan-single-item-section" id="metadata-section-$slug">',
                            'after_name' => '</h2>
                                        </label>',
                            'before_metadata_list' => '<section class="tainacan-item-section tainacan-item-section--metadata">' . do_action( 'tainacan-blocksy-single-item-metadata-begin' ) . '
                                <div class="tainacan-item-section__metadata ' . $metadata_list_structure_type . '" aria-labelledby="metadata-section-$slug">',
                            'after_metadata_list' => '</div>' . do_action( 'tainacan-blocksy-single-item-metadata-end' ) . '</section>',
                        )
                    ) );
                    do_action( 'tainacan-blocksy-single-item-after-metadata' );

                    remove_filter('tainacan-get-metadata-section-as-html-before-name--index-0', 'iphan_blocksy_add_checkbox_to_collapses_metadata_section' );
                ?>
            </div>
        </div> 
    <?php endif; ?>

    <?php if ( count( $tabs_template_area_sections ) > 0 ) : ?>

        <div class="tainacan-item-section tainacan-item-section--metadata-sections iphan-blocksy-metadata-section--tabs">

            <?php 
            
            function iphan_blocksy_add_checkbox_to_tabs_metadata_section($before, $item_metadatum) {
                return str_replace('<input', '<input checked="checked"', $before);
            }
            add_filter('tainacan-get-metadata-section-as-html-before-name--index-0', 'iphan_blocksy_add_checkbox_to_tabs_metadata_section', 10, 2);
            
            $tabs_section_decorator = get_theme_mod( 'iphan_blocksy_item_tabs_decorator', false );
            $tabs_section_background = '--item-tab-background-image: ' . ( $tabs_section_decorator && $tabs_section_decorator['url'] ? ( 'url(' . $tabs_section_decorator['url'] . ')' )  : 'none') . ';';

            ?>
            <div class="metadata-section-layout--tabs" style="<?php echo esc_attr($tabs_section_background); ?>">
                <?php 
                    tainacan_the_metadata_sections( array_merge(
                        $sections_args,
                        array(
                            'metadata_sections__in' => $tabs_template_area_sections,
                            'metadata_sections__not_in' => array_merge($sidebar_template_area_sections, $collapses_template_area_sections, $default_template_area_sections),
                            'before' => '',
                            'after' => '',
                            'before_name' => '<input name="tabs" type="radio" id="tab-section-$id" />
                                    <label for="tab-section-$id" data-index="$index">
                                        <h2 class="tainacan-single-item-section" id="metadata-section-$slug">',
                            'after_name' => '</h2>
                                        </label>',
                            'before_metadata_list' => '<section class="tainacan-item-section tainacan-item-section--metadata">' . do_action( 'tainacan-blocksy-single-item-metadata-begin' ) . '
                                    <div class="tainacan-item-section__metadata ' . $metadata_list_structure_type . '" aria-labelledby="metadata-section-$slug">',
                            'after_metadata_list' => '</div>' . do_action( 'tainacan-blocksy-single-item-metadata-end' ) . '</section>',
                        )
                    ) );
                ?>
            </div>

            <?php 
                remove_filter( 'tainacan-get-metadata-section-as-html-before-name--index-0', 'iphan_blocksy_add_checkbox_to_tabs_metadata_section' );
            ?> 
        </div>

    <?php endif; ?>

    <?php if ( $display_items_related_to_this && $items_related_to_this_position === 'bottom' ) {
        tainacan_blocksy_get_template_part( 'template-parts/tainacan-item-single-items-related-to-this' );
        do_action( 'tainacan-blocksy-single-item-after-items-related-to-this' );
    }
    ?>
</div>

<?php 
remove_filter( 'tainacan-item-metadata-relationship-get-item-thumbnail', 'iphan_blocksy_set_relationship_thumbnail_size' );
remove_filter( 'tainacan-get-metadata-section-as-html-before-name', 'iphan_blocksy_tweak_metadata_section_label' );
remove_filter( 'tainacan-get-item-metadatum-as-html-before-title', 'iphan_blocksy_tweak_metadatum_label', 10, 2 );
