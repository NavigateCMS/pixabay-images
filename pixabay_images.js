navigate_pixabay_row_height = 150;
navigate_pixabay_page = 1;
navigate_pixabay_editor_id = null;

function navigate_pixabay_images_onload(editor_id)
{
    navigate_pixabay_editor_id = editor_id;

    $(".pixabay_images select").each(function()
    {
        navigate_selector_upgrade(this);
    });

    $(".navigate-content-safe").on("scroll", function()
    {
        $("#navigrid-load-more", ".navigrid-items").each(function()
        {
            if(navigate_element_visible(this) && !$(this).find("i").hasClass("fa-spin"))
                $(this).trigger("click");
        });
    });

    $(".navigrid-items").on("click", "#navigrid-load-more", function()
    {
        navigate_pixabay_page++;
        $(this).find("i").addClass("fa-spin");
        navigate_pixabay_images_request();
    });

    $(".pixabay_images button[data-action=search]").on("click", function(e)
    {
        e.stopPropagation();
        e.preventDefault();

        navigate_pixabay_page = 1;
        navigate_pixabay_images_request();
    });

    $(".pixabay_images select").on("change", function(e)
    {
        e.stopPropagation();
        e.preventDefault();

        navigate_pixabay_page = 1;
        navigate_pixabay_images_request();
    });

    $(".navigrid-items").on("click", ".navigrid-item", function()
    {
        var pixabay_item_id = $(this).data("pixabay-item-id");
        var webformat_url = $(this).find("img").data("webformat-url");
        var infopage_url = $(this).find("img").data("infopage-url");

        if(!webformat_url)
            return;

        if(editor_id)
        {
            // append the image in a content with a single click
            $(this).find("i.fa-download")
                .removeClass("fa-download")
                .addClass("fa-spin fa-circle-o-notch");

            // step 1: download the image
            $.post(
                "?fid=ext_pixabay_images&mode=json&oper=download",
                {
                    id: pixabay_item_id
                },
                function (result)
                {
                    result = $.parseJSON(result);

                    if(result.id)
                    {
                        // hide the dialog
                        window.parent.$("#navigate_pixabay_images_dialog").hide();

                        // step 2: put the image in the content
                        window.parent.navigate_tinymce_add_content(
                            editor_id,
                            result.id,
                            result.type,
                            result.mime,
                            result.website,
                            null,
                            {
                                width: result.width,
                                height: result.height,
                                title: result.title,
                                alt: result.description
                            }
                        );

                        // step 3: close the dialog
                        window.parent.$("#navigate_pixabay_images_dialog").dialog("close");
                        window.parent.$("#navigate_pixabay_images_dialog").remove();
                    }
                    else if (result.error)
                    {
                        navigate_notification(result.error, false, "fa fa-exclamation-triangle");
                    }
                }
            );
        }
        else
        {
            $('<div style="text-align: center;"><img src="' + webformat_url + '" width="100%" /></div>').dialog({
                modal: true,
                width: 960,
                height: 600,
                title: $(this).find("img").data("tags") + " @" + $(this).find(".navigrid-item-info-author a:first").text(),
                buttons: [
                    {
                        text: navigate_t(739, "Download"),
                        icons:
                        {
                            primary: "ui-icon-arrowthickstop-1-s"
                        },
                        click: function ()
                        {
                            var that = this;
                            $(this).parent().find(".ui-dialog-buttonset > button:first")
                                .addClass("ui-state-disabled");

                            $.post(
                                "?fid=ext_pixabay_images&mode=json&oper=download",
                                {
                                    id: pixabay_item_id
                                },
                                function (result)
                                {
                                    result = $.parseJSON(result);

                                    if (result.id)
                                    {
                                        navigate_notification(navigate_t(53, "Data successfully saved"), false, "fa fa-check");
                                        $(that).dialog("close");
                                    }
                                    else if (result.error)
                                    {
                                        navigate_notification(result.error, false, "fa fa-exclamation-triangle");
                                    }
                                }
                            );
                        }
                    },
                    {
                        text: "Pixabay",
                        icons:
                        {
                            primary: "ui-icon-newwin"
                        },
                        click: function ()
                        {
                            window.open(infopage_url);
                        }
                    },
                    {
                        text: navigate_t(58, "Close"),
                        icons:
                        {
                            primary: "ui-icon-arrowreturnthick-1-w"
                        },
                        click: function ()
                        {
                            $(this).dialog("close");
                        }
                    }
                ]
            });
        }
    });

    $(window).on("resize focus blur", function()
    {
        $("#navigate-content-safe .navigrid-items").css({"width": 1, "height": 1});

        $("#navigate-content-safe .navigrid-items").css({
            padding: "0px 4px",
            width: $(".navibrowse-path").width() + parseInt($(".navibrowse-path").css("padding-right")) * 2,
            height: $("#navigate-content-safe").height() - $("#navigate-content-safe div:first").height(),
            visibility: "visible"
        });
    });

    if(editor_id)
    {
        // in iframe; apply styling fixes
        $("#navigate-content-top-spacer").remove();

        navigatecms.resize_callbacks.push(function()
        {
            $("#navigate-content").height($("#navigate-content").height() + 16);
            $("#navigate-content-safe").height($("#navigate-content-safe").height() + 42);
        });
    }

    $(window).trigger("resize");

    // load example photos
    navigate_pixabay_images_request();
}

function navigate_pixabay_images_request()
{
    if(navigate_pixabay_page == 1)
        $(".navigrid-items").empty();

    $.getJSON(
        "?fid=ext_pixabay_images&mode=json&oper=search",
        {
            page: navigate_pixabay_page,
            per_page: 64,
            text: $(".pixabay_images input[name=q]").val(),
            order: $("select[name=navibrowse-filter-sort]").val(),
            type: $("select[name=navibrowse-filter-type]").val(),
            orientation: $("select[name=navibrowse-filter-orientation]").val(),
            editors_choice: false
        },
        function(data)
        {
            $("#navigrid-load-more").remove();

            if(!data)
                return;

            for(i in data.hits)
            {
                var img = data.hits[i];
                var scaled_width = Math.floor((img.previewWidth / img.previewHeight) * navigate_pixabay_row_height);

                $(".navigrid-items").append('<div class="navigrid-item ui-corner-all"></div>');
                $(".navigrid-items .navigrid-item:last")
                    .attr("id", "pixabay-item-" + img.id)
                    .css({"width": scaled_width, "height": navigate_pixabay_row_height});

                $(".navigrid-items .navigrid-item:last").append('<div class="navigrid-item-info"></div>');

                if(!navigate_pixabay_editor_id)
                    $(".navigrid-item-info:last").append('<div class="navigrid-item-info-get"><i class="fa fa-2x fa-expand"></i><br /><br />'+img.imageWidth+' x '+img.imageHeight+'</div>');
                else
                    $(".navigrid-item-info:last").append('<div class="navigrid-item-info-get"><i class="fa fa-2x fa-download"></i><br /><br />'+img.imageWidth+' x '+img.imageHeight+'</div>');

                $(".navigrid-item-info:last").append('<div class="navigrid-item-info-author"><a href="https://pixabay.com/users/'+img.user+'" target="_blank">'+img.user+'</a> @ <a href="'+img.pageURL+'" target="_blank">Pixabay</a></div>');
                $(".navigrid-items .navigrid-item:last").append('<img src="'+img.previewURL+'" data-webformat-url="'+img.webformatURL+'" data-infopage-url="'+img.pageURL+'" data-tags="'+img.tags+'"  />');
                $(".navigrid-items .navigrid-item:last img").css({"width": "auto", "height": navigate_pixabay_row_height});
                $(".navigrid-items .navigrid-item:last").data("pixabay-item-id", img.id);
            }

            // if there are more results, append a load more button
            if($(".navigrid-items").children().length < data.totalHits)
            {
                $(".navigrid-items").append('<div id="navigrid-load-more" class="navigrid-item ui-corner-all"><i class="fa fa-repeat"></i></div>');
                $("#navigrid-load-more").css({"width": navigate_pixabay_row_height, "height": navigate_pixabay_row_height});
            }

            $(window).trigger("resize");
        }
    );
}