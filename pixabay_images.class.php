<?php

class pixabay_images
{
    public $apikey;
    public $pixabay_endpoint = "https://pixabay.com/api/?";

    function __construct($apikey)
    {
        $this->apikey = $apikey;
    }

    // TODO: implement a full search with additional parameters (safe, min. width/height...)
    function search($text="", $page=1, $per_page=64, $order="popular", $type="", $orientation="", $editors_choice=false)
    {
        $url = $this->pixabay_endpoint;

        $url.= 'key='.$this->apikey;
        $url.= '&page='.$page;
        $url.= '&per_page='.$per_page;
        $url.= '&order='.$order;

        if(!empty($text))
            $url.= '&q='.$text;

        if(!empty($type))
            $url.= '&image_type='.$type;

        if(!empty($orientation))
            $url.= '&orientation='.$orientation;

        if(isset($editors_choice))
            $url.= '&editors_choice='.($editors_choice? 'true' : 'false');

        $today = strftime("%Y%m%d");
        $hash = md5($today . '.' . $url);
        $file = 'plugins/pixabay_images/cache/request-'.$today.'-'.$hash;

        if(file_exists($file))
        {
            $data = file_get_contents($file);
            $this->clear_cache();
        }
        else
        {
            $data = core_curl_post($url);

            if(!empty($data))
                file_put_contents($file, $data);

            $this->clear_cache();
        }

        $data = json_decode($data);

        return $data;
    }

    function get($id, $high_resolution=false)
    {
        // get full image info
        $url = $this->pixabay_endpoint;
        $url.= 'key='.$this->apikey;
        $url.= '&id='.$id;

        if($high_resolution)
            $url .= '&response_group=high_resolution';

        $today = strftime("%Y%m%d");
        $hash = md5($today . '.' . $url);

        $file = 'plugins/pixabay_images/cache/request-'.$today.'-'.$hash;

        if(file_exists($file))
        {
            $data = file_get_contents($file);
            $this->clear_cache();
        }
        else
        {
            $data = core_curl_post($url);

            if(!empty($data))
                file_put_contents($file, $data);

            $this->clear_cache();
        }

        $data = json_decode($data);
        if(!empty($data->hits))
            $data = $data->hits[0];

        return $data;
    }

    function clear_cache()
    {
        $today = strftime("%Y%m%d");

        $files_all = glob('plugins/pixabay_images/cache/request-*');

        $pattern = 'plugins/pixabay_images/cache/request-'.$today.'-*';
        $files_today = glob($pattern);

        foreach($files_all as $file)
        {
            if(!in_array($file, $files_today))
                @unlink($file);
        }
    }
}

?>