<script>
    $(function () {
        var $modal = $('#newReturnModal');
        var $property = $('#newReturnProperty');
        var $enduser = $('#newReturnEnduser');
        var $hint = $('#newReturnPropertyHint');
        var $detail = $('#newReturnPropertyDetail');

        // A user recording their own return has no picker, so the id comes
        // from the server instead of from a <select>.
        var fixedEnduserId = {{ $fixedEnduserId ? (int) $fixedEnduserId : 'null' }};

        if (!$modal.length || !$property.length) {
            return;
        }

        function currentEnduserId() {
            return $enduser.length ? $enduser.val() : fixedEnduserId;
        }

        function selectedIsCustodian() {
            return false;
        }

        // The lines describing one property. `meta` is the small print; the
        // property number always leads so the item is identifiable at a glance.
        function optionLines(item) {
            var spec = [];
            if (item.serial_number && item.serial_number !== 'N/A') { spec.push('SN: ' + item.serial_number); }
            if (item.office_name) { spec.push(item.office_name); }
            if (item.condition) { spec.push(item.condition); }

            return {
                number: item.property_no || item.text || '(no property no.)',
                descrip: item.descrip || '',
                spec: spec.join(' / '),
                // Which of the two capacities the item is held in: an office head or
                // Office heads are usually the accountable officer, staff the end user.
                holder: 'Accountable: ' + (item.accountable_name || 'Unassigned') +
                    ' / End user: ' + (item.enduser_name || 'Unassigned')
            };
        }

        function buildOptionRow(item) {
            var line = optionLines(item);
            var $row = $('<div></div>');

            $row.append($('<strong></strong>').text(line.number));

            if (line.descrip) {
                $row.append($('<div class="small"></div>').text(line.descrip));
            }

            if (line.spec) {
                $row.append($('<div class="text-muted small"></div>').text(line.spec));
            }

            return $row.append($('<div class="text-muted small"></div>').text(line.holder));
        }

        // select2('data') is only callable once the widget exists, and the end-user
        // change handler can fire before the deferred initialisation has run.
        function selectedItems() {
            if (!$.fn.select2 || !$property.data('select2')) {
                return [];
            }

            return $property.select2('data') || [];
        }

        function renderDetail() {
            var items = selectedItems();

            $detail.empty();

            if (!items.length) {
                $detail.append($('<span class="text-muted"></span>').text('No items selected yet.'));
                return;
            }

            $detail.append(
                $('<strong></strong>').text(items.length + ' item' + (items.length === 1 ? '' : 's') + ' selected')
            );

            var $list = $('<ul class="mb-0 pl-3"></ul>');

            items.forEach(function (item) {
                var line = optionLines(item);
                var $li = $('<li class="mb-1"></li>');

                $li.append($('<strong></strong>').text(line.number));

                if (line.descrip) {
                    $li.append(document.createTextNode(' - ')).append($('<span></span>').text(line.descrip));
                }

                if (line.spec) {
                    $li.append($('<div class="text-muted small"></div>').text(line.spec));
                }

                $li.append($('<div class="text-muted small"></div>').text(line.holder));
                $list.append($li);
            });

            $detail.append($list);
        }

        function resetProperties(message) {
            $property.val(null).trigger('change');
            $hint.text(message);
            renderDetail();
        }

        // Must run AFTER the layout's global `$('.select2').select2()`. select2 wraps
        // each field in <span class="select2 select2-container">, so anything we
        // initialise first gets re-initialised by that global selector on the span
        // itself, which leaves the dropdown empty. setTimeout(0) queues us behind
        // every other document-ready handler, including the layout's.
        setTimeout(function () {
            if (!$.fn.select2) {
                if (window.console) {
                    console.error('Return picker: select2 is not loaded.');
                }
                return;
            }

            try {
                $property.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    multiple: true,
                    dropdownParent: $modal,
                    placeholder: 'Select the end user first...',
                    closeOnSelect: false,
                    minimumInputLength: 0,
                    language: {
                        noResults: function () {
                            return currentEnduserId()
                                ? 'No matching property found for this end user.'
                                : 'Select the end user first.';
                        },
                        errorLoading: function () {
                            return 'Could not load properties. Refresh the page and try again.';
                        },
                        searching: function () {
                            return 'Searching properties...';
                        }
                    },
                    ajax: {
                        url: '{{ route('returnSlips.properties') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1,
                                enduser_id: currentEnduserId() || ''
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;

                            if (data && data.scope) {
                                $hint.text(data.scope + '. Type to search by property no., description, serial no., model, or office.');
                            }

                            return {
                                results: (data && data.results) || [],
                                pagination: { more: !!(data && data.pagination && data.pagination.more) }
                            };
                        },
                        transport: function (params, success, failure) {
                            return $.ajax(params)
                                .done(success)
                                .fail(function (xhr) {
                                    // A session timeout answers with a login redirect, not JSON.
                                    if (xhr.status === 401 || xhr.status === 419 || xhr.status === 302) {
                                        toastr.error('Your session expired. Refresh the page and sign in again.');
                                    } else if (xhr.status) {
                                        toastr.error('Could not load properties (HTTP ' + xhr.status + ').');
                                    }
                                    failure(xhr);
                                });
                        },
                        cache: true
                    },
                    // Built by appending to one root element and returning it.
                    // Chaining .find()/.end() here is a trap: every meta line
                    // carries the "small" class, so a `div.small` selector matches
                    // all of them and .first().end() pops back to that whole set
                    // instead of the root, which silently returns the wrong node
                    // and drops the property number.
                    templateResult: function (item) {
                        if (!item.id) {
                            return item.text;
                        }

                        return buildOptionRow(item);
                    },
                    // Keep the chips readable: the property number identifies the item.
                    templateSelection: function (item) {
                        return item.property_no || item.text;
                    }
                });

                if ($enduser.length) {
                    $enduser.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: $modal,
                        placeholder: 'Select who returned the items'
                    });
                }

                if (currentEnduserId()) {
                    $property.select2('open');
                    $property.select2('close');
                }
            } catch (err) {
                if (window.console) {
                    console.error('Return picker: select2 failed to initialise.', err);
                }
            }
        }, 0);

        // Bootstrap's modal steals focus back from select2's search box; letting it
        // manage focus itself keeps the results list from rendering empty.
        $modal.on('select2:open', function () {
            $(document).off('focusin.bs.modal');
        });

        // Switching the end user invalidates anything already picked, because the
        // new person is not accountable for it.
        $enduser.on('change', function () {
            if (!currentEnduserId()) {
                resetProperties('Select the end user above first.');
                return;
            }

            resetProperties(selectedIsCustodian()
                ? 'Loading every item in the school offices...'
                : 'Loading the items assigned to this end user...');
        });

        $property.on('change', renderDetail);

        $modal.on('hidden.bs.modal', function () {
            $('#newReturnForm')[0].reset();
            $property.val(null).trigger('change');

            if ($enduser.length) {
                $enduser.val('{{ optional($defaultEnduser)->id }}').trigger('change');
            }

            $('#newReturnSubmit').prop('disabled', false);
        });

        $('#newReturnForm').on('submit', function (e) {
            if (!currentEnduserId()) {
                e.preventDefault();
                toastr.error('Select who returned the items.');
                return;
            }

            if (!($property.val() || []).length) {
                e.preventDefault();
                toastr.error('Select at least one property being returned.');
                return;
            }

            $('#newReturnSubmit').prop('disabled', true);
        });
    });
</script>
