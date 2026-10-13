(function ($) {
    'use strict';

    var config = window.IconlyACF || { icons: [], i18n: {} };
    var icons = Array.isArray(config.icons) ? config.icons : [];
    var i18n = config.i18n || {};
    var iconByName = {};

    icons.forEach(function (icon) {
        iconByName[icon.name] = icon;
    });

    function getFieldElement(field) {
        if (field && field.$el) {
            return field.$el;
        }

        return $(field);
    }

    function closePicker($picker) {
        $picker.removeClass('is-open');
        $picker.find('.iconly-picker__panel').first().prop('hidden', true);
        $picker.find('.iconly-picker__toggle').first().attr('aria-expanded', 'false');
    }

    function closeOtherPickers($current) {
        $('.iconly-picker.is-open').each(function () {
            var $picker = $(this);
            if (!$current || $picker[0] !== $current[0]) {
                closePicker($picker);
            }
        });
    }

    function initialize(field) {
        var $field = getFieldElement(field);
        var $picker = $field.hasClass('iconly-picker')
            ? $field
            : $field.find('.iconly-picker').first();

        if (!$picker.length || $picker.data('iconlyReady')) {
            return;
        }

        $picker.data('iconlyReady', true);

        var $input = $picker.find('.iconly-picker__input').first();
        var $toggle = $picker.find('.iconly-picker__toggle').first();
        var $panel = $picker.find('.iconly-picker__panel').first();
        var $grid = $picker.find('.iconly-picker__grid').first();
        var $search = $picker.find('.iconly-picker__search').first();
        var $status = $picker.find('.iconly-picker__status').first();
        var $preview = $picker.find('.iconly-picker__preview-glyph').first();
        var $selectedName = $picker.find('.iconly-picker__selected-name').first();
        var $clear = $picker.find('.iconly-picker__clear').first();
        var columns = parseInt($picker.attr('data-columns'), 10);
        var rendered = false;

        if (!Number.isFinite(columns)) {
            columns = 6;
        }

        columns = Math.max(3, Math.min(12, columns));
        $picker[0].style.setProperty('--iconly-columns', columns);

        function updateStatus(message) {
            $status.text(message || '');
        }

        function createIconButton(icon) {
            var button = document.createElement('button');
            var glyph = document.createElement('span');
            var name = document.createElement('span');
            var codepoint = document.createElement('span');

            button.type = 'button';
            button.className = 'iconly-picker__item';
            button.setAttribute('role', 'option');
            button.setAttribute('data-name', icon.name);
            button.setAttribute('aria-selected', 'false');
            button.title = icon.name;

            glyph.className = 'iconly iconly-picker__glyph';
            glyph.setAttribute('aria-hidden', 'true');
            glyph.textContent = icon.unicode;

            name.className = 'iconly-picker__name';
            name.textContent = icon.name;

            codepoint.className = 'iconly-picker__codepoint';
            codepoint.textContent = icon.codepoint ? 'U+' + icon.codepoint : '';

            button.appendChild(glyph);
            button.appendChild(name);
            button.appendChild(codepoint);

            return button;
        }

        function markSelected(value) {
            $grid.find('.iconly-picker__item').each(function () {
                var selected = this.getAttribute('data-name') === value;
                this.classList.toggle('is-selected', selected);
                this.setAttribute('aria-selected', selected ? 'true' : 'false');
            });
        }

        function renderGrid() {
            if (rendered) {
                return;
            }

            rendered = true;

            if (!icons.length) {
                updateStatus(i18n.noIcons || 'No icons found.');
                return;
            }

            var fragment = document.createDocumentFragment();
            icons.forEach(function (icon) {
                fragment.appendChild(createIconButton(icon));
            });

            $grid[0].appendChild(fragment);
            markSelected($input.val());
            updateStatus(icons.length + ' ' + (i18n.icons || 'Icons'));
        }

        function setValue(value, triggerChange) {
            var icon = iconByName[value] || null;

            $input.val(value);

            if (icon) {
                $preview.text(icon.unicode);
                $selectedName.text(icon.name);
                $clear.prop('hidden', false);
            } else if (value) {
                $preview.text('');
                $selectedName.text((i18n.missing || 'Missing icon:') + ' ' + value);
                $clear.prop('hidden', false);
            } else {
                $preview.text('');
                $selectedName.text(i18n.choose || 'Choose an icon');
                $clear.prop('hidden', true);
            }

            markSelected(value);

            if (triggerChange) {
                $input.trigger('change');
            }
        }

        function filterGrid(query) {
            query = String(query || '').toLowerCase().trim();
            var visible = 0;

            $grid.find('.iconly-picker__item').each(function () {
                var name = String(this.getAttribute('data-name') || '').toLowerCase();
                var show = !query || name.indexOf(query) !== -1;
                this.hidden = !show;
                if (show) {
                    visible += 1;
                }
            });

            if (!icons.length) {
                updateStatus(i18n.noIcons || 'No icons found.');
            } else if (!visible) {
                updateStatus(i18n.noResults || 'No matching icons.');
            } else {
                updateStatus(visible + ' ' + (i18n.icons || 'Icons'));
            }
        }

        $toggle.on('click.iconlyPicker', function () {
            var opening = $panel.prop('hidden');

            if (!opening) {
                closePicker($picker);
                return;
            }

            closeOtherPickers($picker);
            renderGrid();
            $picker.addClass('is-open');
            $panel.prop('hidden', false);
            $toggle.attr('aria-expanded', 'true');

            if ($search.length) {
                window.setTimeout(function () {
                    $search.trigger('focus');
                }, 0);
            }
        });

        $grid.on('click.iconlyPicker', '.iconly-picker__item', function () {
            var value = String($(this).attr('data-name') || '');
            setValue(value, true);
            closePicker($picker);
        });

        $search.on('input.iconlyPicker', function () {
            filterGrid(this.value);
        });

        $clear.on('click.iconlyPicker', function () {
            setValue('', true);
            closePicker($picker);
        });

        $picker.on('keydown.iconlyPicker', function (event) {
            if (event.key === 'Escape') {
                closePicker($picker);
                $toggle.trigger('focus');
            }
        });

        setValue(String($input.val() || ''), false);
    }

    if (typeof acf !== 'undefined' && acf.addAction) {
        acf.addAction('ready_field/type=iconly_icon', initialize);
        acf.addAction('append_field/type=iconly_icon', initialize);
    }

    $(function () {
        $('.acf-field[data-type="iconly_icon"]').each(function () {
            initialize($(this));
        });
    });

    $(document).on('click.iconlyPickerGlobal', function (event) {
        $('.iconly-picker.is-open').each(function () {
            if (event.target !== this && !$.contains(this, event.target)) {
                closePicker($(this));
            }
        });
    });
}(jQuery));
