const Storage = {
    KEY_TABS: 'pos_tabs',
    KEY_ACTIVE: 'pos_active_tab',

    saveTabs(tabs) {
        localStorage.setItem(this.KEY_TABS, JSON.stringify(tabs));
    },

    loadTabs() {
        return JSON.parse(localStorage.getItem(this.KEY_TABS) || '[]');
    },

    saveActive(tabId) {
        localStorage.setItem(this.KEY_ACTIVE, tabId);
    },

    loadActive() {
        return localStorage.getItem(this.KEY_ACTIVE);
    },

    clear() {
        localStorage.removeItem(this.KEY_TABS);
        localStorage.removeItem(this.KEY_ACTIVE);
    }
};

export default Storage;