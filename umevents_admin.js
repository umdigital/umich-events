(function($){
    $(document).ready(function(){
        function loadMultiselect(){
            $('.jqmslist').multiselect({
                columns    : 2,
                placeholder: 'Select options',
                search     : true,
                selectAll  : true
            });
        }

        loadMultiselect();

        // reload multiselect (new widget, updated widget, etc)
        $(document).ajaxStop(function(){
            loadMultiselect();
        });
    });
}(jQuery));
