$( document ).ready(function() {
    $('#loginForm').on('submit', function (e) {
        e.preventDefault();
        let form = $(this);
        let url = form.attr('action');
        // let phone = $('#username').val().replace(/[^0-9]/g, '');

        let data = {
            'username': $('#username').val(),
            'password': $('#password').val()
        };
        let jsonData = JSON.stringify(data);
        console.log(jsonData);

        $.ajax({
            type: "POST",
            url: url,
            data: jsonData,
            dataType: 'json',
            contentType: "application/json"
        }).done(function (data) {
            console.log(data);
            if (typeof data.redirect !== 'undefined') {
                window.location = data.redirect;
            }
        }).fail(function (response) {
            alert('Wrong login or password!');
            console.log(response);
        });

        return false;
    });

    $('#cabinetLink').on('click', function (e) {
        e.preventDefault();
        $('#modal-auth').toggle();
    });

    $('#switchToRegister').on('click', function (e) {
        e.preventDefault();
        $('#modal-auth').hide();
        $('#modal-reg').toggle();
    });

    $('#switchToLogin').on('click', function (e) {
        e.preventDefault();
        $('#modal-reg').hide();
        $('#modal-auth').toggle();
    });

    $('#registerPasswordRepeat').on('blur', function (e) {
        compareRegisterPasswords();
    });
});

function compareRegisterPasswords() {
    let passRepeat = $('#registerPasswordRepeat').val();
    let pass = $('#registerPassword').val();
    if (passRepeat == pass) {
        return true;
    }

    $('#passwordAlert').show();
    return false;
}