jQuery(document).ready(function($) {
    $('form').on('submit', function(e) {
        e.preventDefault();

        var data = {
            'action': 'filter_cars',
            'manufacturer': $('select[name="manufacturer"]').val(),
            'model': $('input[name="model"]').val(),
            'fuel_type': $('input[name="fuel_type"]').val(),
            'color': $('input[name="color"]').val()
        };

        $.post(frontendajax.ajaxurl, data, function(response) {
            $('#car-results').html(response);
        });
    });
});
