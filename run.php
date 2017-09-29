<?php
require_once('plugins/pixabay_images/pixabay_images.class.php');

function pixabay_images_run()
{
    global $website;

    $out = "";

    $extension = new extension();
    $extension->load("pixabay_images");

    $pixabay = new pixabay_images($extension->settings['apikey']);

    switch($_REQUEST['mode'])
    {
        case 'json':
            switch($_REQUEST['oper'])
            {
                case 'info':
                    $data = $pixabay->get(intval($_REQUEST['id']));
                    echo json_encode($data);
                    core_terminate();
                    break;

                case 'download':
                    // try to get the image in High resolution (requires full api access with Pixabay approval)
                    if(!is_numeric($_REQUEST['id']))
                        $data = $pixabay->get_high_res($_REQUEST['id']);
                    else
                        $data = $pixabay->get(intval($_REQUEST['id']));

                    $tags = explode(", ", $data->tags);
                    if(isset($data->id))
                        $pixabay_file_name = $tags[0]."-pixabay-".$data->id;
                    else if(isset($data->id_hash))
                        $pixabay_file_name = $data->user."-pixabay-".$data->id_hash;

                    // check if we already have the image downloaded
                    $existing = file::filesBySearch($pixabay_file_name);

                    if(!empty($existing))
                    {
                        $data = $existing[0];
                    }
                    else
                    {
                        $pixabay_file_path = NAVIGATE_PRIVATE . '/' . $website->id . '/files/' . uniqid('pixabay-');

                        $image_url = $data->webformatURL;
                        if(isset($data->imageURL)) // Hi-Res available
                            $image_url = $data->imageURL;

                        core_file_curl($image_url, $pixabay_file_path);

                        if(file_exists($pixabay_file_path) && filesize($pixabay_file_path) > 256)
                        {
                            $f = file::register_upload($pixabay_file_path, $pixabay_file_name, 0);

                            foreach ($website->languages as $language => $foo)
                            {
                                $f->title[$language] = $data->user . ' / Pixabay';
                                $f->description[$language] = $data->tags;
                            }

                            // file details correction (when fileinfo extension is not available)
                            if($f->type != "image")
                            {
                                $f->type = "image";
                                $f->mime = "image/png"; // we assume all images are PNG, even if that's untrue

                                if($data->imageURL)
                                {
                                    $f->width = $data->imageWidth;
                                    $f->height = $data->imageHeight;
                                }
                                else
                                {
                                    $f->width = $data->webformatWidth;
                                    $f->height = $data->webformatHeight;
                                }
                            }

                            $f->save();

                            $f->title = json_encode($f->title);
                            $f->description = json_encode($f->description);

                            $data = $f;
                        }
                        else
                        {
                            $data = array("error" => $extension->t("download_error"));
                        }
                    }

                    echo json_encode($data);
                    core_terminate();
                    break;

                case 'search':
                default: // list or search
                    // default values: get most popular images selected by editor's choice
                    $text = value_or_default($_REQUEST["text"], "");
                    $page = value_or_default($_REQUEST['page'], 1);
                    $per_page = value_or_default($_REQUEST['per_page'], 64);
                    $order = value_or_default($_REQUEST['order'], 'popular');
                    $type = value_or_default($_REQUEST['type'], 'photo');
                    $orientation = value_or_default($_REQUEST['orientation'], 'all');
                    $editors_choice = value_or_default($_REQUEST['editors_choice'], true);

                    // try searching in high resolution
                    $items = $pixabay->search(
                        $text,
                        $page,
                        $per_page,
                        $order,
                        $type,
                        $orientation,
                        $editors_choice,
                        true
                    );

                    if(!isset($items->totalHits))
                    {
                        // high resolution not enabled
                        $items = $pixabay->search(
                            $text,
                            $page,
                            $per_page,
                            $order,
                            $type,
                            $orientation,
                            $editors_choice,
                            false
                        );
                    }

                    echo json_encode($items);

                    core_terminate();
                    break;
            }
            break;

        default:
           $out = pixabay_images_browser($extension);
    }

    return $out;
}

function pixabay_images_browser($extension)
{
    global $layout;

    $navibars = new navibars();

    $navibars->title("Pixabay images");

    $html = navigate_pixabay_images_browse($extension);

    $navibars->add_content($html);

    if(empty($extension->settings["apikey"]))
    {
        $layout->add_script('
            $("'.'<div>'.$extension->t("apikey_missing").'</div>'.'").dialog(
            {
                height: 150,
                width: 300,
                modal: true,
                title: "'.t(740, "Error").'",
                buttons:
                [
                    {
                        text: "'.t(459, "Settings").'",
                        click: function()
                        {              
                            window.location.replace("?fid=extensions&edit_settings=pixabay_images");                      
                            $(this).dialog("close");
                        }
                    }
                ]
            });
        ');
    }

    return $navibars->generate();
}

?>