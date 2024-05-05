class MapContent {

    /**
     * Конструктор служебного класса для получения данных для карт из БД
     *
     * @param mapDefinition - определение карты, полученное из JS-запроса `/map:js/ID.js`
     * @param options
     * @param is_debug
     */
    constructor(mapDefinition = {}, options = {}, is_debug = false)
    {
        this.options = {
        }

        this.theMap = mapDefinition;
        this.is_debug = is_debug;
        jQuery.extend(this.options, options);
    }


    loadContent(target, id_region, all_regions) {
        if (!(id_region in all_regions)) {
            console.log("[" + id_region + "] not found at regionsDataset.");
            return false;
        }

        if (IS_DEBUG) console.log("Called do_LoadContent for " + id_region);

        if (current_infobox_region_id !== id_region) {
            let url = URL_GET_REGION_CONTENT + map_alias + '&id=' + id_region;

            $("#section-infobox-content").html('');

            $.get(url, function(){}).done(function(data){
                if (IS_DEBUG) console.log('data loaded, length ' + data.length);

                current_infobox_region_id = id_region;

                $("#section-infobox-content").html(data);
                document.getElementById('section-infobox-content').scrollTop = 0; // scroll box to top
            });
        }
    }
}