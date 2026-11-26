([name, parameters]) => {
    const nodes = xpath => document.evaluate(xpath, document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
    const first = xpath => nodes(xpath).snapshotItem(0);
    const contains = (actual, expected) => actual.toLowerCase().includes(expected.toLowerCase());
    const text = element => element.tagName === 'TITLE' ? element.textContent : (element.checkVisibility() ? (element.innerText ?? '') : '');
    const html = () => (document.doctype ? new XMLSerializer().serializeToString(document.doctype) + '\n' : '') + document.documentElement.outerHTML;
    const value = element => {
        if (element.type === 'checkbox') return element.checked ? (element.getAttribute('value') ?? 'on') : null;

        if (element.type === 'radio') {
            const checked = Array.from(element.getRootNode().querySelectorAll('input[type=radio]')).find(radio => radio.name === element.name && radio.checked);
            return checked ? checked.value : null;
        }

        if (element.tagName === 'SELECT' && element.multiple) return Array.from(element.selectedOptions, option => option.value);

        return element.value ?? '';
    };

    switch (name) {
        case 'responseContains':
            return contains(html(), parameters[0]);
        case 'responseNotContains':
            return !contains(html(), parameters[0]);
        case 'pageTextContains':
            return contains(text(document.documentElement).replace(/\s+/gu, ' '), parameters[0]);
        case 'pageTextNotContains':
            return !contains(text(document.documentElement).replace(/\s+/gu, ' '), parameters[0]);
        case 'elementExists':
        case 'fieldExists':
            return first(parameters[0]) !== null;
        case 'elementNotExists':
        case 'fieldNotExists':
            return first(parameters[0]) === null;
        case 'elementsCount':
            return nodes(parameters[0]).snapshotLength === parameters[1];
        case 'elementTextContains':
            return first(parameters[0]) !== null && contains(text(first(parameters[0])), parameters[1]);
        case 'elementTextNotContains':
            return first(parameters[0]) !== null && !contains(text(first(parameters[0])), parameters[1]);
        case 'elementContains':
        case 'elementNotContains': {
            const element = first(parameters[0]);
            return element !== null && contains(element.innerHTML, parameters[1]) === (name === 'elementContains');
        }
        case 'elementAttributeContains':
        case 'elementAttributeNotContains': {
            const attribute = first(parameters[0])?.getAttribute(parameters[1]);

            if (attribute === null || attribute === undefined) return false;

            return contains(attribute, parameters[2]) === (name === 'elementAttributeContains');
        }
        case 'fieldValueEquals':
        case 'fieldValueNotEquals': {
            const element = first(parameters[0]);

            if (!element) return false;

            const actual = value(element);

            if (Array.isArray(actual)) return true;

            return (String(actual ?? '').toLowerCase() === parameters[1].toLowerCase()) === (name === 'fieldValueEquals');
        }
        case 'fieldValueContains': {
            const element = first(parameters[0]);

            if (!element) return false;

            const actual = value(element);
            const values = Array.isArray(actual) ? actual : (actual === null ? [] : [actual]);
            return values.includes(parameters[1]) !== parameters[2];
        }
        case 'checkboxChecked':
            return first(parameters[0]) !== null && first(parameters[0]).checked;
        case 'checkboxNotChecked':
            return first(parameters[0]) !== null && !first(parameters[0]).checked;
        default:
            return true;
    }
}
