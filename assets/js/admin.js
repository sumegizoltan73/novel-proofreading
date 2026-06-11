function show_hide(selector) {
    jQuery(selector).toggleClass("hidden");
}

jQuery(function ($) {
    function filterRelatedOptions($bookSelect) {
        var bookId = $bookSelect.val();
        var formId = $bookSelect.attr("form");
        var $controls = formId ? $('[form="' + formId + '"]') : $();
        var $scope = $bookSelect.closest("form");

        if (!$controls.length && !$scope.length) {
            $scope = $bookSelect.closest("tr, .form-table");
        }

        if (!$controls.length) {
            $controls = $scope.find(".novel-proofreading-storyline-select, .novel-proofreading-event-select");
        }

        $controls
            .filter(".novel-proofreading-storyline-select, .novel-proofreading-event-select")
            .each(function () {
                var $select = $(this);

                $select.find("option").each(function () {
                    var $option = $(this);
                    var optionBookId = $option.data("book-id");

                    if (!optionBookId || !bookId || String(optionBookId) === String(bookId)) {
                        $option.prop("hidden", false);
                        return;
                    }

                    if ($option.is(":selected")) {
                        $select.val($select.find("option:first").val());
                    }

                    $option.prop("hidden", true);
                });
            });
    }

    $(".novel-proofreading-book-select").each(function () {
        filterRelatedOptions($(this));
    });

    $(document).on("change", ".novel-proofreading-book-select", function () {
        filterRelatedOptions($(this));
    });
});
