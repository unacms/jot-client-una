;(function($){
    const sMenuId = 'data-group-id',
          sCountSelector = '.bx-menu-addon-cnt',
          sParentsGroupSelector = 'data-talks-type';

    let oMenuBubbleStructure = null;

    $.fn.extend({
        updateMenuBubbles: function(iLoId, mixedMode, bInc = true) {
            const { oNotifications } = this.get(0);
            if (oMenuBubbleStructure === null)
                oMenuBubbleStructure = oNotifications;

            //console.log('----- запуск обновления меню  -----', oNotifications, iLoId, mixedMode, oMenuBubbleStructure, bInc);

            updateStorageData(iLoId, mixedMode, bInc)
        },
        initMenuBubbles: function(){
            const { oNotifications } = this.get(0),
                { collapsableArea, chevronUpIcon, addonWrapper } = window.oMessengerSelectors.MENU_AREA;

            //console.log('----- инициализируем баблы меню -----', oNotifications);

            if (oMenuBubbleStructure === null)
                oMenuBubbleStructure = oNotifications;

            const fFunc = function(){
                if (+$(sCountSelector, this).html())
                    $(addonWrapper, this).removeClass('hidden');
            };

            $(collapsableArea).on('click', function(){
               if (!$(this).find(chevronUpIcon).is(':visible'))
                   $(addonWrapper, this).addClass('hidden');
               else
                   fFunc.apply(this);
            }).each(fFunc);
        }
    });

    function updateNavMenuBubbles(sType, sSubGroup){
         let iCounter = 0;

        //console.log('-----обновляем сами баблы ----', sType, sSubGroup);

        if (sType === 'groups' && typeof sSubGroup === 'string') {
            if (typeof oMenuBubbleStructure[sType][sSubGroup] !== 'undefined'){
                let iMainCounter = Object.keys(oMenuBubbleStructure[sType][sSubGroup]).length;
                const oTalkObject = $(`[${sParentsGroupSelector}="${sSubGroup}"]`),
                      fFunc = (oGroups, iGroupId) => {
                            let iCounter = oGroups[iGroupId] ? Object.keys(oGroups[iGroupId]).length : 0,
                                oGroup = $(`[${sMenuId}="${iGroupId}"] ${sCountSelector}`);

                        //console.log('------ обновляем баблы для груповых разговоров конерктной круппы-----', sType, sSubGroup, oGroups[iGroupId], iCounter);
                        oGroup.html(iCounter);
                        if (!iCounter)
                            oGroup.parent().addClass('hidden');
                        else
                            oGroup.parent().removeClass('hidden');
                      };

                oTalkObject.html(iMainCounter);
                /*if (!iMainCounter)
                    oTalkObject.parent().addClass('hidden');
                else
                    oTalkObject.parent().removeClass('hidden');*/

                //console.log('------ обновляем баблы для груповых разговоров конертного модуля -----', sType, sSubGroup);

                const oGroupItems = oMenuBubbleStructure[sType][sSubGroup];
                if (!Object.keys(oGroupItems).length)
                    $(`li[class*="${sSubGroup}"] li`).each(function(){
                        let iGroupId = +$(this).data('group-id');
                        if (iGroupId)
                            fFunc(oGroupItems, iGroupId);
                    });
                else
                    Object.keys(oGroupItems).forEach((iGroupId) => fFunc(oGroupItems, iGroupId));
            }
        }
        else
        {
            let oTalkType = $(`[${sParentsGroupSelector}="${sType}"]`);

            //console.log('------ обновляем баблы для простых менюбез вложенностей -----', sType, iCounter);
            iCounter = +Object.keys(oMenuBubbleStructure[sType]).length;
            oTalkType.html(iCounter);
            if (!iCounter)
                oTalkType
                    .parent()
                    .addClass('hidden');
            else
                oTalkType
                    .parent()
                    .removeClass('hidden');
        }
    }

    function updateStorageData(iLoId, mixedMode, bInc = true){
        const { type, groups_type, group_id } = mixedMode || {};

       // console.log('---- обновлеяем данные массива -----', oMenuBubbleStructure, iLoId, mixedMode);
        if (typeof oMenuBubbleStructure[type] === 'undefined') {
            if (iLoId) {
                Object.keys(oMenuBubbleStructure).some(function (sGroup) {
                   // console.log('---- смотрим содержание меню ---', sGroup);
                    if (sGroup === 'groups') {
                        return Object.keys(oMenuBubbleStructure[sGroup]).some((sSubGroup) => {
                            //console.log('---- смотрим содержание меню в модуле группы ---', sGroup, sSubGroup);
                            return Object.keys(oMenuBubbleStructure[sGroup][sSubGroup]).some((iGroupId) => {
                                //console.log('---- смотрим содержание меню в конкретной группе в модуле группы ---', sGroup, sSubGroup, iGroupId);
                                return Object.keys(oMenuBubbleStructure[sGroup][sSubGroup][iGroupId]).some((iGroupLotId) => {
                                    //console.log('---- меняем знаечения для конкретного разговора ---', iGroupLotId);

                                    if (+iGroupLotId === +iLoId) {
                                        delete oMenuBubbleStructure[sGroup][sSubGroup][iGroupId][iLoId];
                                        if (!Object.keys(oMenuBubbleStructure[sGroup][sSubGroup][iGroupId]).length)
                                            delete oMenuBubbleStructure[sGroup][sSubGroup][iGroupId];

                                        return updateNavMenuBubbles(sGroup, sSubGroup);
                                    }

                                    return;
                                })
                            });
                        });
                    } else {
                        return Object.keys(oMenuBubbleStructure[sGroup]).some((iId) => {
                            //console.log('---- обновлеяем значения для простых меню, не групповых -----', sGroup, iId, iLoId);
                            if (+iId === +iLoId) {
                                delete oMenuBubbleStructure[sGroup][iId];
                                return updateNavMenuBubbles(sGroup);
                            }
                        });
                    }
                });
            }

            return;
        }

        //console.log('---- обновляем структуру если нет id разговора или пустая структура данных  ---');

        if (!bInc){
            let sGroupType = groups_type;
            if (type !== 'groups') {
                if (typeof oMenuBubbleStructure[type][iLoId] !== 'undefined')
                    delete oMenuBubbleStructure[type][iLoId];
            }
            else
            {
                let bFound = false;
                Object.keys(oMenuBubbleStructure[type]).some(function (sGroup) {
                    return Object.keys(oMenuBubbleStructure[type][sGroup]).some((iGroupId) => {
                        if (+iGroupId === +group_id && typeof oMenuBubbleStructure[type][sGroup][iGroupId][iLoId] !== 'undefined') {
                            delete oMenuBubbleStructure[type][sGroup][iGroupId][iLoId];
                            bFound = true;
                            if (!sGroupType)
                                sGroupType = sGroup;

                            if (!Object.keys(oMenuBubbleStructure[type][sGroup][iGroupId]).length)
                                delete oMenuBubbleStructure[type][sGroup][iGroupId];

                            return true;
                        }
                    });
                    if (bFound)
                        return;
                });
            }

            return updateNavMenuBubbles(type, sGroupType);
        }
        else
        {
            if (type === 'groups') {
                if (oMenuBubbleStructure[type][groups_type] !== undefined) {
                    if (oMenuBubbleStructure[type][groups_type][group_id] !== undefined) {
                        if (oMenuBubbleStructure[type][groups_type][group_id][iLoId] !== undefined) {
                            if (bInc)
                                oMenuBubbleStructure[type][groups_type][group_id][iLoId] += 1;
                            else
                                delete oMenuBubbleStructure[type][groups_type][group_id][iLoId];
                        }
                        else
                            oMenuBubbleStructure[type][groups_type][group_id] = Object.assign({}, oMenuBubbleStructure[type][groups_type][group_id], { [iLoId]: 1 });
                    }
                    else
                        oMenuBubbleStructure[type][groups_type] = Object.assign({}, oMenuBubbleStructure[type][groups_type], { [group_id]: { [iLoId]: 1 }});
                }
                else
                    oMenuBubbleStructure[type][groups_type] = { [group_id]: {[iLoId]: 1 }};
            }
            else
            {
                if (oMenuBubbleStructure[type][iLoId] !== undefined)
                    oMenuBubbleStructure[type][iLoId] += 1;
                else
                    oMenuBubbleStructure[type] = Object.assign({}, oMenuBubbleStructure[type], {[iLoId]: 1});
            }
        }

        //console.log('------- результат данных после обновления массива -------', oMenuBubbleStructure);
        updateNavMenuBubbles(type, groups_type);
    }
})(jQuery);