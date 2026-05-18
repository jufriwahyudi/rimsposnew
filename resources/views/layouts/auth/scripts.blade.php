<script src="{{ asset('assets/js/jquery.min.js') }}"></script>

<script>
    $(document).ready(function () {
        $("#show_hide_password a").on('click', function (event) {
            event.preventDefault();
            const input = $('#show_hide_password input');
            const icon = $('#show_hide_password i');
            if (input.attr("type") == "text") {
                input.attr('type', 'password');
                icon.addClass("bi-eye-slash-fill").removeClass("bi-eye-fill");
            } else {
                input.attr('type', 'text');
                icon.removeClass("bi-eye-slash-fill").addClass("bi-eye-fill");
            }
        });
    });
</script>