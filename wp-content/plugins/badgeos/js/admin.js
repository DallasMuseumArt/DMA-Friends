jQuery(document).ready(function() {
    var isUnsaved = false, $design_settings = jQuery( '#badgestack-theme-options' );
	
    // Only show the background color input when the background color option type is Color (Hex)
    jQuery('.background-option-types', $design_settings).each(function() {
        showHideHexColor(jQuery(this));
        jQuery(this).change( function() {
            showHideHexColor( jQuery(this) ) 
        });
    });
        
    // Add color picker to color input boxes.
    jQuery('input:text.color-picker').each(function (i) {
        jQuery(this).after('<div id="picker-' + i + '" style="z-index: 100; background: #EEE; border: 1px solid #CCC; position: absolute; display: block;"></div>');
        jQuery('#picker-' + i).hide().farbtastic(jQuery(this));
    })
    .focus(function() {
        jQuery(this).next().show();
    })
    .blur(function() {
        jQuery(this).next().hide();
        isUnsaved = true;
    });
    
    // Add bottom margin to last meta box in each column, then bring Save / Reset buttons back in to place.
    jQuery('.postbox-container:first .postbox:last, .postbox-container .postbox:last', $design_settings).css('marginBottom', '195px');
    jQuery('.bottom-buttons input:first').css({'float': 'right', 'margin-top': '-180px', 'margin-right': '140px'});
    jQuery('.bottom-buttons input:last').css({'float': 'right', 'margin-top': '-180px'});
    
    
    // Add dirty flag when we change an option
    jQuery('input, select', $design_settings).change(function() {
        isUnsaved = true;
    })
    
    // Remove dirty flag when we save options
    jQuery('form', $design_settings).submit (function() {
       isUnsaved = false; 
    });
    
    function showHideHexColor($selectElement) {
        // Use of hide() and show() look bad, as it makes it display:block before display:none / inline.
        $selectElement.next().css('display','none');
        if ($selectElement.val() == 'hex') {
            $selectElement.next().css('display', 'inline');
        }
    }
    
});
