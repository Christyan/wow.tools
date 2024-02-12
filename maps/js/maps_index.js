function wowMapMatcher(params, data) {
    // If there are no search terms, return all of the data
    if ($.trim(params.term) === '') {
        return data;
    }

    // Do not display the item if there is no 'text' property
    if (typeof data.text === 'undefined') {
        return null;
    }

    if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        var modifiedData = $.extend({}, data, true);
        modifiedData.text += ' (text match)';
        return modifiedData;
    }

    if (data.element.dataset.internal.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        var modifiedData = $.extend({}, data, true);
        modifiedData.text += ' (internal match)';
        return modifiedData;
    }

    if (data.element.dataset.internal.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        var modifiedData = $.extend({}, data, true);
        modifiedData.text += ' (internal match)';
        return modifiedData;
    }

    if(data.element.dataset.imapid != null && data.element.dataset.imapid == params.term){
        var modifiedData = $.extend({}, data, true);
        modifiedData.text += ' (mapid match)';
        return modifiedData;
    }

    return null;
}