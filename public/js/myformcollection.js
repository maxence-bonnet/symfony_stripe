/**
 * AddSubformButton must provide a target to identify CollectionHolder
 * 
 * CollectionHolder must have for id the AddSubformButton target (.collectionHolder class not really necessary)
 * 
 * subForm prototype must have for id the AddSubformButton target + "-form-prototype", example "enterprise-documents-form-prototype"
 * 
 * each CollectionItem (subforms) must have .collectionItem class
 * 
 * each new CollectionItem will have .collectionItem class, to which we can add optional classes with the array given for DynamicFormCollection construction
 * 
 * optionnal type parameter to handle GeographicScopeCollectionItem and its dynamic behaviour (ajax & stuff)
 */
 class DynamicFormCollection {
    constructor({
        addFormButtonsSelector,
        collectionItemClasses,
        type,
    }) {
        this.addFormButtonsSelector = addFormButtonsSelector ?? '.addSubformButton';
        this.addFormButtons = document.querySelectorAll(this.addFormButtonsSelector );
        if (this.addFormButtons.length === 0)
            throw new Error(`no addSubformButton found with selector : ${this.addFormButtonsSelector}`);
        this.collectionItemClasses = collectionItemClasses ?? ['shadow', 'p-3', 'rounded-md'];
        this.type = type ?? 'regular';
        this.init();
    }

    init () {
        for (let button of this.addFormButtons) {
            button = new AddSubformButton({
                element: button,
                collectionItemClasses: this.collectionItemClasses,
                type: this.type,
            });
        }
    }
}

class AddSubformButton {
    constructor ({
        element,
        collectionItemClasses,
        type,
    }) {
        if (null === element)
            throw new Error(`no element given for AddSubformButton`);
        this.button = element;
        this.target = element.dataset.target;
        this.collectionItemClasses = collectionItemClasses;
        this.collectionHolder = new CollectionHolder({
            element: this.getCollectionHolder(),
            subFormPrototype: this.getSubformPrototype(),
            collectionItemClasses: this.collectionItemClasses,
            type,
        });
        this.init();
    }

    init () {
        this.button.addEventListener('click', () => {
            this.collectionHolder.embedNewSubform();
        });
    }

    getCollectionHolder () {
        let collectionHolder = document.querySelector(`#${this.target}`);
        if (null === collectionHolder)
            throw new Error(`collectionHolder not found with selector : #${this.target}`);
        return collectionHolder;
    }

    getSubformPrototype () {
        let subFormPrototype = document.querySelector(`#${this.target}-form-prototype`);
        if (null === subFormPrototype)
            throw new Error(`subFormPrototype not found with selector : #${this.target}-form-prototype}`);
        if (undefined === subFormPrototype.dataset.prototype)
            throw new Error(`prototype not defined in subFormPrototype`);
        return subFormPrototype.dataset.prototype;
    }
}

class CollectionHolder {
    constructor ({
        element,
        subFormPrototype,
        collectionItemClasses,
        type,
    }) {
        if (null === element) {
            return;
        }
        this.holder = element;
        this.collectionItemClasses = collectionItemClasses;
        this.itemsCollection = [];
        this.subFormPrototype = subFormPrototype;
        this.type = type;
        this.index = element.querySelectorAll('.collectionItem').length;
        this.init();
    }

    init () {
        this.initExistingSubForms();
    }

    initExistingSubForms () {
        for (let subForm of this.holder.querySelectorAll('.collectionItem')) {
            let item;
            if (this.type === 'geographicScope') {
                item = new GeographicScopeCollectionItem({
                    element: subForm,
                });
            } else {
                item = new CollectionItem({
                    element: subForm
                });                
            }
            item.init();
            this.itemsCollection.push(item);
        }
    }

    embedNewSubform () {
        let newItem;
        if (this.type === 'geographicScope') {
            newItem = new GeographicScopeCollectionItem({
                element: this.createNewSubform(),
            });
        } else {
            newItem = new CollectionItem({
                element: this.createNewSubform(),
            });
        }

        this.holder.appendChild(newItem.subForm);
        newItem.init();
        this.itemsCollection.push(newItem);
        this.index++;
    }

    createNewSubform () {
        let newForm = document.createElement('div');
        newForm.classList.add('collectionItem');
        for (let collectionItemClass of this.collectionItemClasses) {
            newForm.classList.add(collectionItemClass);
        }
        newForm.innerHTML = this.subFormPrototype.replace(/__name__/g, this.index);
        return newForm;
    }
}

class CollectionItem {
    constructor ({
        element
    }) {
        if (null === element) {
            throw new Error(`no element given for CollectionItem`);
        }
        this.subForm = element;
    }

    init () {
        this.initRemove();
    }

    initRemove () {
        let removeButton = this.subForm.querySelector('button');
        if (null !== removeButton) {
            removeButton.addEventListener('click', () => {
                this.subForm.remove();
            })             
        }

    }
}

class GeographicScopeCollectionItem {
    constructor ({
        element
    }) {
        if (null === element) {
            return;
        }
        this.subForm = element;
    }

    init () {
        this.initDynamicForm();
        this.initRemove();
    }

    initRemove () {
        this.subForm.querySelector('button').addEventListener('click', () => {
            this.subForm.remove();
        }) 
    }

    initDynamicForm () {
        this.$subForm = $(this.subForm);
        this.$regionSelect = this.$subForm.find('.region-select');
        this.$departmentSelect = this.$subForm.find('.department-select');
        this.$cityInput = this.$subForm.find('.city-input');
        this.$form = this.$subForm.closest('form');
        this.$departmentId = this.$departmentSelect.attr('id');
        this.$cityInputId = this.$cityInput.attr('id');
        this.bindEvents();
    }

    // updateDepartmentSelect () {
    //     this.$departmentSelect = this.$subForm.find('.department-select');
    //     this.bindDepartement();
    // }

    bindEvents () {
        // this.bindRegion();
        // this.bindDepartement();
        this.bindCity();
        this.bindCityTypeAhead();
    }

    // bindRegion () {
    //     this.$regionSelect.on('change', () => {
    //         var data = {};
    //         data[this.$regionSelect.attr('name')] = this.$regionSelect.val();
    //         // Update department list
    //         $.ajax({
    //             url : this.$form.attr('action'),
    //             type: this.$form.attr('method'),
    //             data : data,
    //             complete: (html) => {
    //                 this.$subForm.find('.department-select').replaceWith(
    //                     $(html.responseText).find(`#${this.$departmentId}`)
    //                 );
    //                 this.$cityInput.val('');
    //                 this.updateDepartmentSelect();
    //             },
    //         });
    //     });
    // }
    
    // bindDepartement () {
    //     this.$departmentSelect.on('change', () => {
    //         // Update region select value
    //         $.ajax({
    //             url : 'https://'+location.host+'/static/json/regions_departments.json',
    //             type: 'get',
    //             complete: (response) => {
    //                 let region_departments = response.responseJSON;
    //                 for (let region of Object.keys(region_departments)) {
    //                     if (region_departments[region].includes(this.$departmentSelect.val())) {
    //                         this.$regionSelect.val(region);
    //                     }
    //                 }
    //             }
    //         });
    //         this.$cityInput.val('');
    //         this.bindCityTypeAhead();
    //     });
    // }

    bindCity() {
        this.$cityInput.on('change', () => {
            // pas foufou, besoin d'attendre un peu la mise à jour du champ
            // update region & department select value
            setTimeout(() => {
                $.ajax({
                    url : `https://${location.host}/static/json/cities_references.json`, // TROUVER MIEUX
                    type: 'get',
                    complete: (response) => {
                        let cities = response.responseJSON.data;
                        if (this.$cityInput.val() in cities) {
                            let key = this.$cityInput.val();
                            let regionId = cities[key].region;
                            let departmentId = cities[key].department;
                            if ('undefined' !== regionId && 'undefined' !== departmentId) {
                                this.$regionSelect.val(regionId);
                                this.$departmentSelect.val(departmentId);                                
                            }
                        }
                    }
                });
            }, 200);
        })
    }

    bindCityTypeAhead () {
        // if (this.$departmentSelect.val()) {
        //     this.bindCityTypeAheadByDepartment();
        //     console.log(this.$departmentSelect.val())
        // } else {
            this.bindCityTypeAheadAll();
        // }
    }

    bindCityTypeAheadByDepartment () {
        // return list of cities from department
        $.typeahead({
            input: '#'+this.$cityInputId,
            maxItem: 15,
            order: "asc",
            searchOnFocus: true,
            emptyTemplate: 'Aucun résultat pour "\{\{query\}\}"',
            href: false,
            source: {                    
                villes: {
                    ajax : {
                        url: 'https://'+location.host+'/static/json/department_cities.json',
                        path: "data."+this.$departmentSelect.val(),
                        data: {
                            geographicScope : "\{\{query\}\}"
                        },                        
                    }
                },
            },
        });
    }

    bindCityTypeAheadAll () {
        // return list of all cities
        $.typeahead({
            input: '#'+this.$cityInputId,
            maxItem: 15,
            order: "asc",
            searchOnFocus: true,
            emptyTemplate: 'Aucun résultat pour "\{\{query\}\}"',
            templateValue: "\{\{name\}\}",
            display: ['name'],
            href: false,
            template: function (query, item) {
                 if (item.num) {
                    return item.name + ' (' + item.num + ')';
                }
                return item.name
            }, 
            source: {                    
                villes: {
                    ajax : {
                        url: 'https://'+location.host+'/static/json/cities.json',
                        path: "data",
                        data: {
                            geographicScope : "\{\{query\}\}"
                        },                        
                    }
                },
            },
        });
    }
}
