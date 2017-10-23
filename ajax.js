$( document ).ready(function() {
    $('#sendData').on('click', function () {
        if(!$('#postalDode').val()) {
            $('#message').text('Please enter zip code')
        } else {
            $('#message').text('');
            $.ajax({
                url: "controller.php",
                type: 'post',
                data: {
                    'postal_code' : $('#postalDode').val(),
                    'country' : $('#country').val()
                },
                success: function(result) {
                    var data = JSON.parse(result);
                    if (data.status == 'error') {
                        $('#message').text(data.message);
                    } else {
                        $('#message').text('');
                        $('#zipData tbody').empty();
                        data.map(function(obj) {
                            $('#zipData tbody').append(
                                '<tr>' +
                                '<td>' +
                                obj['place_name'] +
                                '</td>' +
                                '<td>' +
                                obj['longitude'] +
                                '</td>' +
                                '<td>' +
                                obj['latitude'] +
                                '</td>' +
                                '</tr>'
                            )
                        })
                    }
                }
            });
        }
    });
});
