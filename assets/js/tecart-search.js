// Search Bar & Toggle
$('.tecart-search-toggle').on('click', function() {
    // use classes and parent to be able to use search more than once on one page
    $(this).parent().find('.tecart-search-form').toggle('display: inline-block');
});
// End - Search Bar & Toggle

// Perform Search
$(document).ready(function(){

    $.ajaxSetup({
        cache: false
    });

    $('.tecart-search-box').keyup(function(e){

        const searchField       = $(this).val();
        const dataFileStorage   = $(this).data('storage');
        const dataLinkTarget    = $(this).data('linktarget');
        const dataEmpty         = $(this).data('empty');
        const resultHeadline    = $(this).data('resultheadline');
        // use classes and parent to be able to use search more than once on one page
        const resultContainer   = $(this).parent().find('.tecart-search-results');
        const expression        = new RegExp(searchField, "igm");

        // use "/" before relative path to avoid problems in subpages
        let dataFile = "/user/data/tecart-search-index.json";
        if(dataFileStorage === 'pages'){
            dataFile = "/user/pages/assets/tecart-search-index.json";
        }

        $.getJSON(dataFile, function(data) {

            if(typeof data === 'string' || data == '' || data == null){
                resultContainer.html('<li class="list-group-item link-class">' + dataEmpty + '</li>');
            }
            else{
                resultContainer.html('<li class="list-group-item headline-class"><h2>' + resultHeadline + '</h2></li>');
                $.each(data, function(key, value) {
                    if (value.title.search(expression) != -1 || value.content.search(expression) != -1) {
                        resultContainer.append(
                            '<li class="list-group-item link-class">' +
                                '<a href="'+value.route+'" target="' + dataLinkTarget + '">'+
                                    value.title + ' | <span class="text-muted">' + value.route + '</span>' +
                                '</a>'+
                            '</li>'
                        );
                    }
                });
            }
        })
    });

    // close link results and clear box
    $('.tecart-search-results').on('click', 'li', function() {
        // var click_text = $(this).text().split('|');
        const click_link = $(this).find('a').attr('href');
        const click_target = $(this).find('a').attr('target');

        //$('#tecart-search-box').val($.trim(click_text[0]));
        window.open(click_link, click_target);
        $(".tecart-search-results").html('');
    });

    // close searchbox
    $('.tecart-search-close').on('click', function() {
     $('.tecart-search-form').toggle('display: none');
    });

});
// End - Perform Search
