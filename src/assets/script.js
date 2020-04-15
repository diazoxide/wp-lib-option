(function () {

    document.addEventListener("DOMContentLoaded", function () {

        window.diazoxide.wordpress.option.select2Init(document.getElementsByClassName('wp-lib-option-wrap')[0]);

        let hash = decodeURI(window.location.hash.substr(1));
        if (hash) {
            let element = document.querySelector("[route='" + hash + "']");
            if (element !== null) {
                element.click();
            }
        } else {
            /**
             * Expand all first fields
             * */
            let fields = document.querySelectorAll('ul.wp-lib-option-nested-fields>li.label:first-child');
            for (let i = 0; i < fields.length; i++) {
                if (fields[i].offsetParent !== null) {
                    window.diazoxide.wordpress.option.toggleLabel(fields[i], false);
                }
            }
        }
    });

    /**
     * Normalize sections
     * */
    let sections = document.querySelectorAll('ul.wp-lib-option-nested-fields>li>.section');
    for (let i = 0; i < sections.length; i++) {
        let section = sections[i];
        section.parentNode.parentNode.classList.add('include-section');
        section.parentNode.parentNode.previousSibling.style.display = "none";
    }

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
                        let form_status = form.querySelector('.form-status');
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
                        let form = button.closest('form');

                        let last_key = parseInt(button.getAttribute('last-key')) + 1;
                        button.setAttribute('last-key', last_key);
                        let c = button.parentElement.parentElement.querySelector(':scope>[new]').cloneNode(true);
                        c.removeAttribute('new');
                        c.classList.remove('hidden');
                        c.classList.add('added');
                        let e = c.querySelectorAll('[name]');

                        for (let i = 0; i < e.length; i++) {
                            e[i].disabled = false;
                            e[i].name = (e[i].name).replace('{{LAST_KEY}}', last_key);
                        }
                        button.parentElement.parentElement.insertBefore(c, button.parentElement);

                        form.onchange();

                        setTimeout(function () {
                            c.classList.remove('added');
                        }, 1000);

                        this.afterItemInsert(c);
                    },
                    afterItemInsert: function (item) {
                        this.select2Init(item);
                    },
                    collapseAll: function (button) {
                        let labels = button.closest('form').querySelectorAll('ul.wp-lib-option-nested-fields > .label.open');
                        for (let i = 0; i < labels.length; i++) {
                            this.toggleLabel(labels[i],false,false);
                        }
                    },
                    expandAll: function (button) {
                        let labels = button.closest('form').querySelectorAll('ul.wp-lib-option-nested-fields > .label:not(.open)');
                        for (let i = 0; i < labels.length; i++) {
                            this.toggleLabel(labels[i],false,false);
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
                        if (!window.hasOwnProperty('jQuery')) {
                            return;
                        }
                        let $ = window.jQuery;
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
                        if (!window.hasOwnProperty('jQuery')) {
                            return;
                        }
                        let $ = window.jQuery;

                        $(item).find('select[select2=true]').each(function () {
                            if ($(this).parents('[new=true]').length === 0) {
                                let _field = this;

                                $(_field).select2().on("select2:select", function (evt) {
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
})();