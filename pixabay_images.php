<?php
require_once(NAVIGATE_PATH.'/plugins/pixabay_images/pixabay_images.class.php');

function navigate_pixabay_images_dialog($params)
{
    global $layout;

    $extension = new Extension();
    $extension->load('pixabay_images');

    if(empty($extension->settings['apikey']))
        return;

    $layout->add_script('
        function navigate_pixabay_images_dialog()
        {
            var editor_id = tinymce.activeEditor.id;           
            if(!editor_id)
                return;
                 
            var dialog = $(\'<div id="navigate_pixabay_images_dialog" style="padding-left: 0; padding-right: 0; overflow: hidden;"></div>\');
            $(dialog).html(\'<iframe width="100%" height="100%" style="background: transparent;" frameborder="0" src="?fid=extensions&act=dialog&extension=pixabay_images&function=navigate_pixabay_images_dialog_find&editor_id=\'+editor_id+\'"></iframe>\');
            
            $(dialog).dialog(
            {
                width: $(window).width() * 0.95,
                height: $(window).height() * 0.95,
                modal: true,
                title: \'<img src="plugins/pixabay_images/pixabay_favicon.png" width="16" height="16" align="absmiddle"> '.$extension->t('pixabay_images').'\'
            })
            .dialogExtend({ maximizable: true });            
        }        
    ');

    $trigger = '<a href="#" onclick="navigate_pixabay_images_dialog();"><img height="16" align="absmiddle" width="16" src="plugins/pixabay_images/pixabay_favicon.png"> '.$extension->t("pixabay_images").'</a>';

    return $trigger;
}

function navigate_pixabay_images_dialog_find()
{
    global $layout;

    $extension = new Extension();
    $extension->load('pixabay_images');

    $navibars = new navibars();

    $html = navigate_pixabay_images_browse($extension, $_REQUEST['editor_id']);
    $navibars->add_content($html);

    $layout->add_content('<div id="navigate-content" class="navigate-content ui-corner-all">'.$navibars->generate().'</div>');
    $layout->navigate_additional_scripts();

    $layout->add_script('
        $("html").css("background", "transparent");
    ');
}

function navigate_pixabay_images_browse($extension, $editor_id=NULL)
{
    global $layout;

    $html = array();

    $html[] = '<div class="pixabay_images navigate-content-safe ui-corner-all" id="navigate-content-safe">';

    $html[] = '<div class="navibrowse-path ui-corner-all">';

    $html[] = '<form action="?" method="get" style="display: inline-block; float: left;" class="ui-widget">                    
                    <input class="pixabay_images-bar-input" type="text" name="q" value="" placeholder="'.$extension->t('search_placeholder').'" />
                    <button data-action="search"><i class="fa fa-search"></i></button>
                    <a href="http://www.pixabay.com" target="_blank">
                        <img src="plugins/pixabay_images/pixabay_logo_free_images.svg" height="16px" style="margin-left: 12px; vertical-align: middle; opacity: .9;" />
                    </a>
               </form>';

    $html[] = '
			<div style="float: right;" class="ui-widget">			    
				<i class="fa fa-lg fa-sort"></i>
	            <select id="navibrowse-filter-type" name="navibrowse-filter-sort">
					<option value="popular" selected="selected">'.$extension->t("popular").'</option>
					<option value="latest">'.$extension->t('latest').'</option>
				</select>
				&nbsp;				
				<i class="fa fa-lg fa-picture-o"></i>
	            <select id="navibrowse-filter-type" name="navibrowse-filter-type">
					<option value="all">('.t(443, "All").')</option>
					<option value="photo" selected="selected">'.$extension->t("photos").'</option>
					<option value="illustration">'.$extension->t('illustrations').'</option>
					<option value="vector">'.$extension->t('vectors').'</option>
				</select>
				&nbsp;
				<i class="fa fa-lg fa-columns"></i>
	            <select id="navibrowse-filter-type" name="navibrowse-filter-orientation">
					<option value="all" selected="selected">('.t(443, "All").')</option>
					<option value="horizontal">'.$extension->t("horizontal").'</option>
					<option value="vertical">'.$extension->t('vertical').'</option>
				</select>				
			</div>
		';

    $html[] = '<div style="clear: both;"></div></div>';

    // topbar end

    $html[] = '<div class="navigrid" id="pixabay_images_grid">';

    $html[] = '<div class="navigrid-items">';

    $html[] = '<div class="clearer">&nbsp;</div>';

    $html[] = '</div>';

    // grid items end
    $html[] = '</div>';

    $layout->add_content('
        <style>
            .pixabay_images-bar-input
            {
                background-color: rgba(255, 255, 255, 0.5);
                border: 1px solid #ccc;
                border-radius: 1px;
                color: #116;
                font-weight: normal;
                padding: 4px 3px 4px 5px;
                width: 360px;
            }
            
            .pixabay_images select
            {
                width: auto;
                min-width: 100px;
                margin-left: 24px;
            }            
        
            .navigrid-item img
            {
                width: auto;
                margin: 0;
            }
            
            .navigrid-items div.navigrid-item-info
            {
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.6);
                position: absolute;
                text-align: center;
                color: #fff;       
                opacity: 0;
                transition: all 0.3s;     
            }
            
            .navigrid-items div.navigrid-item-info a
            {
                color: #fff;
                text-decoration: none;
            }
            
            .navigrid-item-info-get
            {
                position: absolute;
                top: calc(50% - 32px);
                width: 100%;
                text-align: center; 
            }
            
            .navigrid-item-info-get i
            {
                font-size: 32px;
            }
            
            .navigrid-item-info-author
            {
                position: absolute;
                bottom: 8px;
                width: 100%;
                text-align: center;
            }
            
            .navigrid-items div.navigrid-item-info:hover
            {
                opacity: 1;
            }
            
            #navigrid-load-more,
            .navigrid-item-info-get i
            {
                cursor: pointer;
            }
            
            #navigrid-load-more i
            {
                font-size: 64px;
                color: #ccc;
                cursor: pointer;
                margin-top: calc((150px - 64px) / 2);
            }
            
        </style>
    ');

    $layout->add_script('
        $.ajax(
        {
            type: "GET",
            dataType: "script",
            url: "plugins/pixabay_images/pixabay_images.js?r='.$extension->version.'",
            cache: false,
            complete: function()
            {
                if(typeof navigate_pixabay_images_onload == "function")
                    navigate_pixabay_images_onload("'.$editor_id.'");
            }
        });
	');

    return implode("\n", $html);
}
?>