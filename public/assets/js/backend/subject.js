define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'subject/index',
                    add_url: 'subject/add',
                    edit_url: 'subject/edit',
                    del_url: '',
                    multi_url: 'subject/multi',
                    table: 'subject',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showExport: false,
                // commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true, width: '5%', operate: false},
                        {field: 'subject_name', title: __('标签'), width: '15%'},
                        {field: 'status_text', title: __('Status'), operate: false},
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {
                                '0': __('隐藏'),
                                '1': __('显示')
                            },
                            visible: false
                        },
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, width: '10%', operate: false},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, width: '10%', operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});