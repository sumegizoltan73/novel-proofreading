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
    function getStoredNovelProofreadingTab() {
        try {
            return window.sessionStorage.getItem("novelProofreadingActiveTab");
        } catch (error) {
            return "";
        }
    }

    function storeNovelProofreadingTab(targetSelector) {
        try {
            window.sessionStorage.setItem("novelProofreadingActiveTab", targetSelector);
        } catch (error) {
            return;
        }
    }

    function activateNovelProofreadingTab($tab, updateHash) {
        var targetSelector = $tab.data("bs-target");
        var $target = $(targetSelector);

        if (!$target.length) {
            return;
        }

        $(".novel-proofreading-tabs [role='tab']")
            .removeClass("nav-tab-active")
            .attr("aria-selected", "false");

        $(".novel-proofreading-tab-pane")
            .removeClass("show active")
            .attr("hidden", true);

        $tab
            .addClass("nav-tab-active")
            .attr("aria-selected", "true");

        $target
            .addClass("show active")
            .removeAttr("hidden");

        storeNovelProofreadingTab(targetSelector);

        if (updateHash && window.history && window.history.replaceState) {
            window.history.replaceState(null, "", targetSelector);
        }
    }

    function initializeNovelProofreadingTabs() {
        var activeTarget = window.location.hash;
        var $activeTab;

        if (!activeTarget) {
            activeTarget = getStoredNovelProofreadingTab();
        }

        if (activeTarget) {
            $activeTab = $(".novel-proofreading-tabs [role='tab']").filter(function () {
                return $(this).data("bs-target") === activeTarget;
            });
        }

        if (!$activeTab || !$activeTab.length) {
            $activeTab = $(".novel-proofreading-tabs [role='tab']").first();
        }

        activateNovelProofreadingTab($activeTab, false);
    }

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

    function appendReferenceLabel(referenceId, item) {
        var $list = $('.novel-proofreading-label-list[data-reference-id="' + referenceId + '"]');

        if (!$list.length || !item || !item.label) {
            return;
        }

        $list.append(
            ' <span class="novel-proofreading-badge is-label" data-label-id="' +
                escapeHtml(String(item.id || "")) +
                '">' +
                escapeHtml(item.label) +
                "</span>"
        );
    }

    function addReferenceLabel(referenceId) {
        var fallbackPrompt = function () {
            var label = window.prompt("Label");

            if (label) {
                saveReferenceLabel(referenceId, label);
            }
        };

        if (typeof Swal === "undefined") {
            fallbackPrompt();
            return;
        }

        Swal.fire({
            title: "Add label",
            input: "text",
            inputPlaceholder: "Label",
            showCancelButton: true,
            confirmButtonText: "Add",
            cancelButtonText: "Cancel",
            inputValidator: function (value) {
                if (!value) {
                    return "Label is required.";
                }

                return null;
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                saveReferenceLabel(referenceId, result.value);
            }
        });
    }

    function saveReferenceLabel(referenceId, label) {
        $.post(novelProofreading.ajaxUrl, {
            action: "novel_proofreading_add_label",
            nonce: novelProofreading.labelsNonce,
            reference_id: referenceId,
            label: label
        })
            .done(function (response) {
                if (!response || !response.success) {
                    if (typeof Swal === "undefined") {
                        window.alert(
                            response && response.data && response.data.message
                                ? response.data.message
                                : "Could not add label."
                        );
                        return;
                    }

                    Swal.fire({
                        icon: "error",
                        title: "Request failed",
                        text: response && response.data && response.data.message
                            ? response.data.message
                            : "Could not add label."
                    });
                    return;
                }

                appendReferenceLabel(referenceId, response.data.item);
            })
            .fail(function (xhr) {
                var message =
                    xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                        ? xhr.responseJSON.data.message
                        : "Could not add label.";

                if (typeof Swal === "undefined") {
                    window.alert(message);
                    return;
                }

                Swal.fire({
                    icon: "error",
                    title: "Request failed",
                    text: message
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

    $(document).on("click", ".novel-proofreading-add-label", function () {
        addReferenceLabel($(this).data("reference-id"));
    });

    $(document).on("click", ".novel-proofreading-tabs [role='tab']", function () {
        activateNovelProofreadingTab($(this), true);
    });

    $(document).on("keydown", ".novel-proofreading-tabs [role='tab']", function (event) {
        var keys = ["ArrowLeft", "ArrowRight", "Home", "End"];

        if (keys.indexOf(event.key) === -1) {
            return;
        }

        var $tabs = $(".novel-proofreading-tabs [role='tab']");
        var currentIndex = $tabs.index(this);
        var nextIndex = currentIndex;

        if (event.key === "ArrowLeft") {
            nextIndex = currentIndex === 0 ? $tabs.length - 1 : currentIndex - 1;
        } else if (event.key === "ArrowRight") {
            nextIndex = currentIndex === $tabs.length - 1 ? 0 : currentIndex + 1;
        } else if (event.key === "Home") {
            nextIndex = 0;
        } else if (event.key === "End") {
            nextIndex = $tabs.length - 1;
        }

        event.preventDefault();
        $tabs.eq(nextIndex).trigger("focus");
        activateNovelProofreadingTab($tabs.eq(nextIndex), true);
    });

    initializeNovelProofreadingTabs();
});
