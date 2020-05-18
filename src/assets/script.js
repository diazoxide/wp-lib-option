(function ($) {

    $(document).ready(function () {
        window.diazoxide.wordpress.option.select2Init(document.getElementsByClassName('wp-lib-option-wrap')[0]);

        let hash = decodeURI(window.location.hash.substr(1));
        if (hash) {
            $(".label[route='" + hash + "']").click();
        } else {
            /**
             * Expand all first fields
             * */
            $('.wp-lib-option-nested-fields>.label:first-child').each(
                function () {
                    if (this.offsetParent !== null) {
                        window.diazoxide.wordpress.option.toggleLabel(this, false);
                    }
                }
            )
        }
    });

    /**
     * Normalize sections
     * */
    $('.wp-lib-option-nested-fields>.content>.section').each(function () {
        $(this).parent().parent().addClass('include-section');
        $(this).parent().parent().prev().hide();
    })

    if (!window.hasOwnProperty('diazoxide')) {
        window.diazoxide = {};
        if (!window.diazoxide.hasOwnProperty('wordpress')) {
            window.diazoxide.wordpress = {};
            if (!window.diazoxide.wordpress.hasOwnProperty('option')) {
                window.diazoxide.wordpress.option = {
                    formSubmit: function (form) {
                        let _this = this;
                        if (form.dataset.ajax_submit === 'true') {
                            let data = new FormData(form);
                            let xhr = new XMLHttpRequest();
                            xhr.onload = function (data) {
                                _this.formSetStatus(form, 'saved');
                            };

                            xhr.open("POST", window.location.href);

                            _this.formSetStatus(form, 'saving');

                            xhr.send(data);
                            return false;
                        }
                        form.submit();
                        return false;
                    },
                    formChange: function (form) {
                        if (form.dataset.auto_submit === 'true') {
                            this.formSetStatus(form, 'saving');
                            this.formSubmit(form);
                        } else {
                            this.formSetStatus(form, 'unsaved');
                        }
                    },
                    formSetStatus(form, status) {
                        form.dataset.status = status;
                        let form_status = form.querySelector('.form-status>button');
                        if (form_status !== null) {
                            let statuses = form_status.querySelectorAll('span');

                            for (let i = 0; i < statuses.length; i++) {
                                statuses[i].classList.add('hidden');
                            }

                            status = form_status.querySelector('.' + status);

                            if (status !== null) {
                                status.classList.remove('hidden');
                            }
                        }
                    },
                    jump: function (h) {
                        window.location.href = "#" + h;
                    },
                    toggleLabel: function (label, jump = true, toggle_parent = false) {
                        let parentLabel = label.parentElement.previousSibling;
                        let route = '';
                        if (label.nextSibling.offsetParent === null) {
                            label.nextSibling.classList.add('open');
                            label.classList.add('open');
                            route = label.getAttribute('route');
                            if (
                                toggle_parent &&
                                parentLabel !== null &&
                                parentLabel.nodeType === 1 &&
                                parentLabel.classList.contains('label') &&
                                !parentLabel.classList.contains('open')
                            ) {
                                this.toggleLabel(parentLabel, jump, toggle_parent);
                            }
                        } else {
                            label.nextSibling.classList.remove('open');
                            label.classList.remove('open');

                            if (
                                parentLabel !== null &&
                                parentLabel.nodeType === 1 &&
                                parentLabel.classList.contains('label')
                            ) {
                                route = parentLabel.getAttribute('route');
                            }
                        }
                        if (jump) {
                            this.jump(route);
                        }
                    },
                    addNew: function (button) {

                        let $form = $(button).closest('form');
                        let last_key = parseInt($(button).attr('last-key')) + 1;
                        $(button).attr('last-key', last_key);

                        let $c = $(button).parent().parent().children('[new]').clone();
                        $c.removeAttr('new');
                        $c.removeClass('hidden');
                        $c.addClass('added');

                        $c.find('[name]').each(function () {
                            let name = $(this).attr('name');
                            name = name.replace('{{LAST_KEY}}', last_key);
                            $(this).attr('name', name);
                        })

                        $c.find('[name]:not([new] [name])').each(function () {
                            $(this).prop('disabled', false);
                        })

                        $c.insertBefore($(button).parent());

                        $form.trigger('change');

                        setTimeout(function () {
                            $c.removeClass('added');
                        }, 1000);

                        this.afterItemInsert($c);
                    },
                    afterItemInsert: function (item) {
                        this.select2Init(item);
                    },
                    collapseAll: function (button) {
                        let labels = button.closest('form').querySelectorAll('.wp-lib-option-nested-fields > .label.open');
                        for (let i = 0; i < labels.length; i++) {
                            this.toggleLabel(labels[i], false, false);
                        }
                    },
                    expandAll: function (button) {
                        let labels = button.closest('form').querySelectorAll('.wp-lib-option-nested-fields > .label:not(.open)');
                        for (let i = 0; i < labels.length; i++) {
                            this.toggleLabel(labels[i], false, false);
                        }
                    },
                    removeItem: function (button) {
                        if (confirm("Are you sure?")) {
                            let form = button.closest('form');
                            button.parentElement.parentElement.remove();
                            if (form !== null) {
                                form.onchange();
                            }
                        }
                    },
                    objectKeyChange(key_field) {
                        let fields = key_field.parentElement.querySelectorAll('[name]');
                        for (let i = 0; i < fields.length; i++) {
                            let field = fields[i];
                            if (key_field.value != null) {
                                field.removeAttribute('disabled')
                            }
                            let attr = field.getAttribute('name');
                            attr = attr.replace(/{{encode_key}}.*?(?=])/gm, '{{encode_key}}' + btoa(key_field.value));
                            fields[i].setAttribute('name', attr);
                        }
                    },
                    duplicateItem: function (button) {
                        let item = button.parentElement.parentElement;
                        let clone = item.cloneNode(true);
                        clone.classList.add('clone');
                        item.classList.add('cloned');
                        setTimeout(function () {
                            clone.classList.remove('clone');
                            item.classList.remove('cloned');
                        }, 1000);
                        item.parentElement.insertBefore(clone, item);
                    },
                    minimiseItem: function (button) {
                        let item = button.parentElement.parentElement;
                        if (item.hasAttribute('minimised') && item.getAttribute('minimised') === 'true') {
                            item.setAttribute('minimised', 'false');
                            button.setAttribute('title', 'Minimise');
                            button.classList.remove('minimised');
                        } else {
                            item.setAttribute('minimised', 'true');
                            button.setAttribute('title', 'Maximise');
                            button.classList.add('minimised');
                        }
                    },
                    select2OrderSortedValues: function (_field) {
                        $(_field).parent().find("ul.select2-selection__rendered").children("li[title]").each(function (i, obj) {
                            let element = $(_field).children('option').filter(function () {
                                return $(this).html() === obj.title
                            });
                            window.diazoxide.wordpress.option.select2MoveElementToEndOfParent(element)
                        });
                    },
                    select2MoveElementToEndOfParent: function (element) {
                        let parent = element.parent();
                        element.detach();
                        parent.append(element);
                    },
                    select2Init(item) {
                        $(item).find('select[select2=true]').each(function () {
                            if ($(this).parents('[new=true]').length === 0) {
                                let _field = this;

                                $(_field).select2(
                                    {
                                        placeholder: "Select a state",
                                        allowClear: true
                                    }
                                ).on("select2:select", function (evt) {
                                    let id = evt.params.data.id;

                                    let element = $(this).children("option[value='" + id + "']");

                                    window.diazoxide.wordpress.option.select2MoveElementToEndOfParent(element);

                                    $(this).trigger("change");
                                });
                                let ele = $(_field).parent().find("ul.select2-selection__rendered");
                                ele.sortable({
                                    containment: 'parent',
                                    update: function () {
                                        window.diazoxide.wordpress.option.select2OrderSortedValues(_field);
                                        //console.log("" + $(_field).val())
                                    }
                                });
                            }
                        });

                    }
                };
            }
        }
    }
})(jQuery);