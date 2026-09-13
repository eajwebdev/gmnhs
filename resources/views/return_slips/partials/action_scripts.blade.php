<script>
    $(function () {
        var actionBase = '{{ url('return-slips/items') }}';

        function summaryHtml($btn) {
            return '<strong>' + ($btn.data('property-no') || '') + '</strong>' +
                '<div>' + ($btn.data('item-name') || '') + '</div>' +
                '<div class="text-muted small">Returned by: ' + ($btn.data('returned-by') || 'Not specified') + '</div>';
        }

        // Hundreds of end users and offices, so make those pickers searchable.
        // Deferred for the same reason as the return picker: the layout's global
        // `$('.select2').select2()` runs after us and would re-initialise select2
        // on our own container spans (which carry the `select2` class), leaving
        // the dropdowns empty.
        setTimeout(function () {
            if (!$.fn.select2) {
                return;
            }

            try {
                $('#transferItemModal select[name="enduser_id"], #transferItemModal select[name="office_id"], #transferItemModal select[name="location_id"]').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $('#transferItemModal')
                });
            } catch (err) {
                if (window.console) {
                    console.error('Transfer picker: select2 failed, falling back to plain selects.', err);
                }
            }
        }, 0);

        $('#transferItemModal').on('select2:open', function () {
            $(document).off('focusin.bs.modal');
        });

        $(document).on('click', '.js-transfer-item', function () {
            var $btn = $(this);
            $('#transferItemForm').attr('action', actionBase + '/' + $btn.data('item-id') + '/transfer');
            $('#transferItemSummary').html(summaryHtml($btn));
            $('#transferItemForm')[0].reset();
            $('#transferItemForm select[name="condition"]').val('Good Condition');
            $('#transferItemModal select[name="enduser_id"], #transferItemModal select[name="office_id"], #transferItemModal select[name="location_id"]')
                .val('').trigger('change');
            $('#transferItemModal').modal('show');
        });

        $(document).on('click', '.js-unserviceable-item', function () {
            var $btn = $(this);
            $('#unserviceableItemForm').attr('action', actionBase + '/' + $btn.data('item-id') + '/unserviceable');
            $('#unserviceableItemSummary').html(summaryHtml($btn));
            $('#unserviceableItemForm')[0].reset();
            $('#unserviceableItemModal').modal('show');
        });

        $(document).on('click', '.js-obsolete-item', function () {
            var $btn = $(this);
            $('#obsoleteItemForm').attr('action', actionBase + '/' + $btn.data('item-id') + '/obsolete');
            $('#obsoleteItemSummary').html(summaryHtml($btn));
            $('#obsoleteItemForm')[0].reset();
            $('#obsoleteItemModal').modal('show');
        });

        $(document).on('click', '.js-cancel-item', function () {
            var $btn = $(this);
            $('#cancelItemForm').attr('action', actionBase + '/' + $btn.data('item-id') + '/cancel');
            $('#cancelItemSummary').html(summaryHtml($btn));
            $('#cancelItemForm')[0].reset();
            $('#cancelItemModal').modal('show');
        });

        $(document).on('submit', '.js-delete-return', function (e) {
            if (!window.confirm('Delete this return record and restore the property to its previous condition?')) {
                e.preventDefault();
            }
        });
    });
</script>
