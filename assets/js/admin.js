function show_hide(selector) {
    jQuery(selector).toggleClass("hidden");
}

function confirm_delete(button) {
    var form = jQuery(button).closest("form").get(0);

    if (!form) {
        return;
    }

    if (typeof Swal === "undefined") {
        if (window.confirm("Biztosan törölni szeretné?")) {
            form.submit();
        }

        return;
    }

    Swal.fire({
        text: "Biztosan törölni szeretné?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Törlés",
        cancelButtonText: "Mégsem"
    }).then(function (result) {
        if (result.isConfirmed) {
            form.submit();
        }
    });
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
            $controls = $scope.find(
                ".novel-proofreading-storyline-select, .novel-proofreading-event-select, .novel-proofreading-person-select, .novel-proofreading-location-select, .novel-proofreading-time-select, .novel-proofreading-relic-select"
            );
        }

        $controls
            .filter(
                ".novel-proofreading-storyline-select, .novel-proofreading-event-select, .novel-proofreading-person-select, .novel-proofreading-location-select, .novel-proofreading-time-select, .novel-proofreading-relic-select"
            )
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

    function updateReferenceEntityFields($typeSelect) {
        var selectedType = $typeSelect.val();
        var formId = $typeSelect.attr("form");
        var $scope = $typeSelect.closest("tr");

        if (!$scope.length) {
            $scope = $typeSelect.closest("form");
        }

        var $fields = $scope.find(".novel-proofreading-reference-entity");

        if (!$fields.length && formId) {
            $fields = $('[form="' + formId + '"]')
                .closest("tr")
                .find(".novel-proofreading-reference-entity");
        }

        $fields.addClass("hidden");

        if (!selectedType) {
            return;
        }

        if (
            selectedType === "STORYLINE" ||
            selectedType === "EVENT" ||
            selectedType === "PERSON" ||
            selectedType === "LOCATION" ||
            selectedType === "TIME" ||
            selectedType === "RELIC"
        ) {
            $fields
                .filter('[data-reference-entity="' + selectedType + '"]')
                .removeClass("hidden");
            return;
        }

        $fields
            .filter('[data-reference-entity="MULTI"]')
            .removeClass("hidden");
    }

    function escapeHtml(value) {
        return $("<div>").text(value || "").html();
    }

    function renderSuggestionItems(items) {
        if (!items.length) {
            return "<p>No suggestions found.</p>";
        }

        return items
            .map(function (item) {
                return (
                    '<div class="novel-proofreading-suggestion-popup-item">' +
                    '<span class="novel-proofreading-badge is-info">' +
                    escapeHtml(item.type) +
                    "</span>" +
                    "<p>" +
                    escapeHtml(item.description || "") +
                    "</p>" +
                    "</div>"
                );
            })
            .join("");
    }

    function showStorylineSuggestions(storylineId) {
        $.post(novelProofreading.ajaxUrl, {
            action: "novel_proofreading_get_storyline_suggestions",
            nonce: novelProofreading.storylineSuggestionsNonce,
            storyline_id: storylineId
        })
            .done(function (response) {
                if (!response || !response.success) {
                    return;
                }

                Swal.fire({
                    title: "Storyline suggestions",
                    html: renderSuggestionItems(response.data.items || []),
                    width: 700
                });
            })
            .fail(function () {
                Swal.fire({
                    icon: "error",
                    title: "Request failed",
                    text: "Could not load storyline suggestions."
                });
            });
    }

    function renderPersonAliasItems(items) {
        if (!items.length) {
            return "<p>No aliases found.</p>";
        }

        return items
            .map(function (item) {
                var label = [item.name, item.alias]
                    .filter(function (value) {
                        return value;
                    })
                    .join(" ");

                return (
                    '<div class="novel-proofreading-suggestion-popup-item">' +
                    '<span class="novel-proofreading-badge is-info">' +
                    escapeHtml(label || "Alias") +
                    "</span>" +
                    (item.description ? "<p>" + escapeHtml(item.description) + "</p>" : "") +
                    "</div>"
                );
            })
            .join("");
    }

    function showPersonAliases(personId) {
        $.post(novelProofreading.ajaxUrl, {
            action: "novel_proofreading_get_person_aliases",
            nonce: novelProofreading.personAliasesNonce,
            person_id: personId
        })
            .done(function (response) {
                if (!response || !response.success) {
                    return;
                }

                Swal.fire({
                    title: "Person aliases",
                    html: renderPersonAliasItems(response.data.items || []),
                    width: 700
                });
            })
            .fail(function () {
                Swal.fire({
                    icon: "error",
                    title: "Request failed",
                    text: "Could not load person aliases."
                });
            });
    }

    function renderPersonProfessionItems(items) {
        if (!items.length) {
            return "<p>No professions found.</p>";
        }

        return items
            .map(function (item) {
                var personLabel = [item.person_name, item.person_alias]
                    .filter(function (value) {
                        return value;
                    })
                    .join(" ");

                return (
                    '<div class="novel-proofreading-suggestion-popup-item">' +
                    '<span class="novel-proofreading-badge is-info">' +
                    escapeHtml(item.profession_name || "Profession") +
                    "</span>" +
                    (personLabel ? "<p><strong>" + escapeHtml(personLabel) + "</strong></p>" : "") +
                    (item.description ? "<p>" + escapeHtml(item.description) + "</p>" : "") +
                    "</div>"
                );
            })
            .join("");
    }

    function showPersonProfessions(personId, scope) {
        $.post(novelProofreading.ajaxUrl, {
            action: "novel_proofreading_get_person_professions",
            nonce: novelProofreading.personProfessionsNonce,
            person_id: personId,
            scope: scope
        })
            .done(function (response) {
                if (!response || !response.success) {
                    return;
                }

                Swal.fire({
                    title: "Person professions",
                    html: renderPersonProfessionItems(response.data.items || []),
                    width: 700
                });
            })
            .fail(function () {
                Swal.fire({
                    icon: "error",
                    title: "Request failed",
                    text: "Could not load person professions."
                });
            });
    }

    $(".novel-proofreading-book-select").each(function () {
        filterRelatedOptions($(this));
    });

    $(".novel-proofreading-reference-type-select").each(function () {
        updateReferenceEntityFields($(this));
    });

    $(document).on("change", ".novel-proofreading-book-select", function () {
        filterRelatedOptions($(this));
    });

    $(document).on("change", ".novel-proofreading-reference-type-select", function () {
        updateReferenceEntityFields($(this));
    });

    $(document).on("click", ".novel-proofreading-storyline-suggestion-badge", function () {
        showStorylineSuggestions($(this).data("storyline-id"));
    });

    $(document).on("click", ".novel-proofreading-person-alias-badge", function () {
        showPersonAliases($(this).data("person-id"));
    });

    $(document).on("click", ".novel-proofreading-person-profession-badge", function () {
        showPersonProfessions(
            $(this).data("person-id"),
            $(this).data("profession-scope")
        );
    });
});
