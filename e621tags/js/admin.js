document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#e621tags-admin-form');
    const requestToken = document.head.dataset.requesttoken;

    if (!form || !requestToken) {
        return;
    }

    const tokenField = form.querySelector(
        'input[name="requesttoken"]'
    );

    if (tokenField) {
        tokenField.value = requestToken;
    }

    const e621EnabledCheckbox = form.querySelector(
        '#e621_enabled'
    );

    const e6aiEnabledCheckbox = form.querySelector(
        '#e6ai_enabled'
    );

    const normalTagsCheckbox = form.querySelector(
        '#normal_tags_enabled'
    );

    const e621NormalCheckboxes = [
        '#e621_general',
        '#e621_artist',
        '#e621_character',
        '#e621_copyright',
        '#e621_species',
        '#e621_invalid',
        '#e621_lore',
        '#e621_meta',
    ];

    const e621RatingCheckboxes = [
        '#e621_rating',
    ];

    const e6aiNormalCheckboxes = [
        '#e6ai_general',
        '#e6ai_director',
        '#e6ai_character',
        '#e6ai_copyright',
        '#e6ai_species',
        '#e6ai_invalid',
        '#e6ai_lore',
        '#e6ai_meta',
    ];

    const e6aiRatingCheckboxes = [
        '#e6ai_rating',
    ];

    const getCheckbox = (selector) => {
        return form.querySelector(selector);
    };

    const getHiddenField = (checkbox) => {
        if (!checkbox || !checkbox.name) {
            return null;
        }

        return form.querySelector(
            `input[type="hidden"][name="${checkbox.name}"]`
        );
    };

    const syncHiddenField = (checkbox) => {
        const hiddenField = getHiddenField(checkbox);

        if (!hiddenField) {
            return;
        }

        hiddenField.value = checkbox.checked ? '1' : '0';
    };

    const setDisabled = (
        selectors,
        disabled
    ) => {
        selectors.forEach((selector) => {
            const checkbox = getCheckbox(selector);

            if (!checkbox) {
                return;
            }

            checkbox.disabled = disabled;
        });
    };

    const updateState = () => {
        const e621Enabled =
            !e621EnabledCheckbox ||
            e621EnabledCheckbox.checked;

        const e6aiEnabled =
            !e6aiEnabledCheckbox ||
            e6aiEnabledCheckbox.checked;

        const normalTagsEnabled =
            !normalTagsCheckbox ||
            normalTagsCheckbox.checked;

        setDisabled(
            e621NormalCheckboxes,
            !e621Enabled || !normalTagsEnabled
        );

        setDisabled(
            e621RatingCheckboxes,
            !e621Enabled
        );

        setDisabled(
            e6aiNormalCheckboxes,
            !e6aiEnabled || !normalTagsEnabled
        );

        setDisabled(
            e6aiRatingCheckboxes,
            !e6aiEnabled
        );
    };

    [
        ...e621NormalCheckboxes,
        ...e621RatingCheckboxes,
        ...e6aiNormalCheckboxes,
        ...e6aiRatingCheckboxes,
    ].forEach((selector) => {
        const checkbox = getCheckbox(selector);

        if (!checkbox) {
            return;
        }

        checkbox.addEventListener(
            'change',
            () => {
                syncHiddenField(checkbox);
            }
        );
    });

    if (e621EnabledCheckbox) {
        e621EnabledCheckbox.addEventListener(
            'change',
            updateState
        );
    }

    if (e6aiEnabledCheckbox) {
        e6aiEnabledCheckbox.addEventListener(
            'change',
            updateState
        );
    }

    if (normalTagsCheckbox) {
        normalTagsCheckbox.addEventListener(
            'change',
            updateState
        );
    }

    form.addEventListener('submit', () => {
        [
            ...e621NormalCheckboxes,
            ...e621RatingCheckboxes,
            ...e6aiNormalCheckboxes,
            ...e6aiRatingCheckboxes,
        ].forEach((selector) => {
            const checkbox = getCheckbox(selector);

            if (checkbox) {
                syncHiddenField(checkbox);
            }
        });
    });

    updateState();
});
