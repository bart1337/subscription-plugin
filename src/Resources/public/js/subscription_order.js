(function ($) {
    $(document).ready(function () {
        $('#recurring_purchase_button').on('click', function () {
            const variant_id = this.dataset.variant_id;
            const url = this.dataset.url;
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    variant_id: variant_id,
                },
                success: function (data) {

                },
                error: function () {
                    alert('Request failed. ')
                }
            });
        });
    });
})(jQuery);
