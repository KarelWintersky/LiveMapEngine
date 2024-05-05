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



}