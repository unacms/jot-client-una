/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup	Messenger Messenger
 * @ingroup	UnaModules
 * @{
 */

/**
 * Work with browser storage
 */

;window.oMessengerStorage = class {
    constructor(sName = ''){
        this._sSubLot = 'lots';
        this._sName = typeof sName === 'string' && sName.length ? sName : 'Jot';
        this._oData = typeof localStorage[this._sName] !== 'undefined' ? JSON.parse(localStorage.getItem(this._sName)) : {};
    }

    getLots(){
        return this._oData[this._sSubLot];
    }

    getLot(iLotId){
        return this.get(this._sSubLot, iLotId);
    }

    saveLot(iLotId, mixedValue) {
        return this.set(this._sSubLot, iLotId, mixedValue);
    }

    deleteLot(iLotId){
        return this.delete(this._sSubLot, iLotId);
    }

    get(sCateg, sKey){
        return sCateg && this._oData[sCateg] && this._oData[sCateg][sKey];
    }

    set(sCateg, sKey, mixedValue){
        if (sCateg && sKey && typeof mixedValue !== 'undefined') {
            if (this._oData[sCateg] === undefined)
                this._oData[sCateg] = Object.create({});

            this._oData[sCateg][sKey] = mixedValue;
            this.save();
        }

        return this;
    };

    delete(sCateg, sKey){
        if (sCateg && sKey && this._oData[sCateg][sKey]) {
            delete this._oData[sCateg][sKey];
            this.save();
        }

        return this;
    }
    save(){
        return localStorage && localStorage.setItem(this._sName, JSON.stringify(this._oData))
    }
};