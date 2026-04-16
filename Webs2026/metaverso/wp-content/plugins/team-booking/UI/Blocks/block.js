(function (blocks, element) {
    const el = element.createElement;

    const Fragment = wp.element.Fragment;

    const {
        InspectorControls,
    } = wp.blockEditor;
    const {
        CheckboxControl,
        DatePicker,
        PanelBody,
        PanelRow,
        TextControl,
        ToggleControl,
        HorizontalRule,
        SelectControl,
        RadioControl
    } = wp.components;

    const iconEl = el('svg', {width: 20, height: 20},
        [
            el('path', {
                d: "M 2.070312 9.664062 L 6.765625 9.121094 L 7.101562 12.09375 L 2.402344 12.636719 Z M 2.070312 9.664062"
            }),
            el('path', {
                d: "M 8.730469 13.40625 L 13.425781 12.863281 L 13.761719 15.835938 L 9.066406 16.378906 Z M 8.730469 13.40625"
            }),
            el('path', {
                d: "M 13.886719 3.789062 L 18.585938 3.25 L 18.921875 6.222656 L 14.222656 6.761719 Z M 13.886719 3.789062"
            }),
        ]
    );

    const tipIcon = el('svg', {width: 24, height: 24},
            el('path', {
                d: 'M 20.45 4.91 L 19.04 3.5 l -1.79 1.8 l 1.41 1.41 l 1.79 -1.8 Z M 13 4 h -2 V 1 h 2 v 3 Z m 10 9 h -3 v -2 h 3 v 2 Z m -12 6.95 v -3.96 l -1 -0.58 c -1.24 -0.72 -2 -2.04 -2 -3.46 c 0 -2.21 1.79 -4 4 -4 s 4 1.79 4 4 c 0 1.42 -0.77 2.74 -2 3.46 l -1 0.58 v 3.96 h -2 Z m -2 2 h 6 v -4.81 c 1.79 -1.04 3 -2.97 3 -5.19 c 0 -3.31 -2.69 -6 -6 -6 s -6 2.69 -6 6 c 0 2.22 1.21 4.15 3 5.19 v 4.81 Z M 4 13 H 1 v -2 h 3 v 2 Z m 2.76 -7.71 l -1.79 -1.8 L 3.56 4.9 l 1.8 1.79 l 1.4 -1.4 Z'
            })
        )
    ;

    const noticeIcon = el('svg', {width: 16, height: 16, viewBox: '0 0 124 124', style: {color: '#cc3300'}},
            el('path', {
                d: 'M62,0C27.8,0,0,27.8,0,62s27.8,62,62,62s62-27.8,62-62S96.2,0,62,0z M62,109c-25.9,0-47-21.1-47-47c0-25.9,21.1-47,47-47c25.9,0,47,21.1,47,47C109,87.9,87.9,109,62,109z'
            }),
            el('path', {
                d: 'M65,23h-6c-3.3,0-6,2.7-6,6v41c0,3.3,2.7,6,6,6h6c3.3,0,6-2.7,6-6V29C71,25.7,68.3,23,65,23z'
            }),
            el('circle', {
                cx: '62',
                cy: '91.5',
                r : '9'
            })
        )
    ;

    let blockTypeAttributes = {
        restrictServices : {
            type    : 'array',
            selector: 'tbk-attr-restrictservices',
            default : []
        },
        restrictProviders: {
            type    : 'array',
            selector: 'tbk-attr-restrictproviders',
            default : []
        },
        readOnly         : {
            type    : 'boolean',
            selector: 'tbk-attr-readonly',
            default : false,
        },
        loggedOnly       : {
            type    : 'boolean',
            selector: 'tbk-attr-loggedonly',
            default : false,
        },
        view             : {
            type    : 'string',
            selector: 'tbk-attr-viewmode',
            default : 'monthly',
        },
        upcomingEvents   : {
            type    : 'integer',
            selector: 'tbk-attr-upcomingevents',
            default : 4,
        },
        upcomingLimit    : {
            type    : 'integer',
            selector: 'tbk-attr-upcominglimit',
            default : 0,
        },
        showMore         : {
            type    : 'boolean',
            selector: 'tbk-attr-showmore',
            default : false,
        },
        uuid             : {
            type    : 'string',
            selector: 'tbk-attr-uuid',
            default : null,
        }
    };

    blockTypeAttributes = {...blockTypeAttributes};

    const generateKey = function () {
        return 'tbk-frontend-xxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    const blockTypeParams = {
        icon      : iconEl,
        attributes: blockTypeAttributes,
        edit      : function (props) {

            React.useEffect(() => {
                if (!props.attributes.uuid) {
                    const UUID = generateKey();
                    props.setAttributes({uuid: UUID})
                }
            }, []);

            const blockProps = wp.blockEditor.useBlockProps();

            let content = '';

            let restrictServices = props.attributes.restrictServices;
            let restrictProviders = props.attributes.restrictProviders;
            let view = props.attributes.view;
            let showMore = props.attributes.showMore;
            let upcomingEvents = props.attributes.upcomingEvents;
            let upcomingLimit = props.attributes.upcomingLimit;
            let readOnly = props.attributes.readOnly;
            let loggedOnly = props.attributes.loggedOnly;

            let optionsServices = [];
            let optionProviders = [];

            for (const service of VSHM_BACKEND_SERVICES) {
                optionsServices.push(el(CheckboxControl, {
                    label   : service.name,
                    checked : restrictServices.includes(service.id),
                    onChange: newValue => {
                        const index = restrictServices.indexOf(service.id);
                        const newArr = restrictServices.slice();
                        if (newValue) {
                            newArr.push(service.id);
                            props.setAttributes({restrictServices: newArr});
                        } else {
                            newArr.splice(index, 1);
                            props.setAttributes({restrictServices: newArr});
                        }
                    }
                }))
            }

            for (const provider of VSHM_BACKEND_PROVIDERS) {
                optionProviders.push(el(CheckboxControl, {
                    label   : provider.name,
                    checked : restrictProviders.includes(provider.id),
                    onChange: newValue => {
                        const index = restrictProviders.indexOf(provider.id);
                        const newArr = restrictProviders.slice();
                        if (newValue) {
                            newArr.push(provider.id);
                            props.setAttributes({restrictProviders: newArr});
                        } else {
                            newArr.splice(index, 1);
                            props.setAttributes({restrictProviders: newArr});
                        }
                    }
                }))
            }

            const restrictionsTabServices = el(PanelBody, {title: 'Restrict services', initialOpen: false, icon: restrictServices.length ? noticeIcon : null},
                el('div', {className: 'tbk-row-noflex'}, [
                    el(PanelRow, {},
                        el('div', {className: 'components-tip'}, [tipIcon, el('p', {},
                            "If you don't intend to restrict, do not select any checkbox!"
                        )])
                    ),
                    el(HorizontalRule, {}),
                    optionsServices
                ])
            );

            const restrictionsTabProviders = el(PanelBody, {title: 'Restrict providers', initialOpen: false, icon: restrictProviders.length ? noticeIcon : null},
                el('div', {className: 'tbk-row-noflex'}, [
                    el(PanelRow, {},
                        el('div', {className: 'components-tip'}, [tipIcon, el('p', {},
                            "If you don't intend to restrict, do not select any checkbox!"
                        )])
                    ),
                    el(HorizontalRule, {}),
                    optionProviders
                ])
            );

            const viewModeTab = el(PanelBody, {title: 'Configuration', initialOpen: true},
                el(PanelRow, {},
                    el(RadioControl,
                        {
                            label   : 'Widget type',
                            selected: view,
                            options : [
                                {
                                    label: 'Monthly calendar',
                                    value: 'monthly'
                                },
                                {
                                    label: 'Upcoming events',
                                    value: 'upcoming'
                                },
                                {
                                    label: 'Unscheduled services',
                                    value: 'unscheduled'
                                },
                                {
                                    label: 'Reservations list (logged users only)',
                                    value: 'reservations'
                                }
                            ],
                            onChange: newValue => {
                                props.setAttributes({view: newValue});
                            }
                        }
                    ),
                ),
                view === 'upcoming' && el(HorizontalRule, {}),
                view === 'upcoming' && el(TextControl, {
                    label   : 'Displayed events',
                    help    : 'The number of upcoming events that are displayed on the page',
                    type    : 'number',
                    step    : 1,
                    min     : 1,
                    value   : upcomingEvents,
                    onChange: newValue => {
                        let value = Math.max(1, Math.floor(newValue));
                        props.setAttributes({upcomingEvents: value});
                    }
                }),
                view === 'upcoming' && el(PanelRow, {},
                    el(ToggleControl,
                        {
                            label   : 'Show more',
                            help    : 'Shows a button to load more events',
                            checked : showMore,
                            onChange: newValue => {
                                props.setAttributes({showMore: newValue});
                            }
                        })
                ),
                view === 'upcoming' && showMore && el(TextControl, {
                    label   : 'Maximum fetched events',
                    help    : 'Limit the number of maximum events that can be loaded. 0 means no limit.',
                    type    : 'number',
                    step    : 1,
                    min     : 0,
                    value   : upcomingLimit,
                    onChange: newValue => {
                        let value = Math.max(0, Math.floor(newValue));
                        props.setAttributes({upcomingLimit: value});
                    }
                }),
            );

            const permissionsTab = el(PanelBody, {title: 'Permissions', initialOpen: false, icon: (readOnly || loggedOnly) ? noticeIcon : null},
                el(PanelRow, {},
                    el(ToggleControl,
                        {
                            label   : 'Read-only',
                            help    : 'Makes booking not possible through this widget instance',
                            checked : readOnly,
                            onChange: newValue => {
                                props.setAttributes({readOnly: newValue});
                            }
                        }
                    ),
                ),
                readOnly || el(PanelRow, {},
                    el(ToggleControl,
                        {
                            label   : 'Logged-only',
                            help    : 'Makes booking not possible through this widget instance for guests',
                            checked : loggedOnly,
                            onChange: newValue => {
                                props.setAttributes({loggedOnly: newValue});
                            }
                        }
                    ),
                ),
                el(PanelRow, {},
                    el('div', {className: 'components-tip'}, [tipIcon, el('p', {},
                        'Those permissions are applied to all the services presented through this widget, regardless of the service-specific permission settings.'
                    )])
                )
            );

            return (el(
                Fragment,
                {},
                el(
                    InspectorControls, {},
                    viewModeTab,
                    view !== 'reservations' && restrictionsTabServices,
                    view !== 'reservations' && view !== 'unscheduled' && restrictionsTabProviders,
                    view !== 'reservations' && permissionsTab
                ),
                el(
                    'div',
                    blockProps,
                    el(
                        'div',
                        {className: 'tbk-frontend', id: props.attributes.uuid},
                        el(
                            'div',
                            {className: 'tbk-inner-content'},
                            content
                        )
                    )
                )
            ));
        },
        save      : function (props) {
            return el(
                'div',
                {
                    className                : 'tbk-frontend',
                    id                       : props.attributes.uuid,
                    'data-view'              : props.attributes.view,
                    'data-services'          : props.attributes.restrictServices.join(','),
                    'data-providers'         : props.attributes.restrictProviders.join(','),
                    'data-readonly'          : props.attributes.readOnly,
                    'data-loggedonly'        : props.attributes.loggedOnly,
                    'data-max-upcoming'      : props.attributes.upcomingLimit,
                    'data-default-upcoming'  : props.attributes.upcomingEvents,
                    'data-show-more-upcoming': props.attributes.showMore,
                },
                el(
                    'div',
                    {className: 'tbk-inner-content'}
                )
            )
        }
    };

    blocks.registerBlockType('tbk/widget', blockTypeParams);
}(
    window.wp.blocks,
    window.wp.element
));