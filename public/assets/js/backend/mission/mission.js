define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mission/mission/index',
                    add_url: 'mission/mission/add',
                    edit_url: 'mission/mission/edit',
                    del_url: 'mission/mission/del',
                    multi_url: 'mission/mission/multi',
                    table: 'mission',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'mission_group', title: __('Mission_group')},
                        {field: 'title', title: __('Title')},
                        {field: 'mission_explain', title: __('Mission_explain')},
                        {field: 'repeat_type', title: __('Repeat_type')},
                        {field: 'mission_tag', title: __('Mission_tag')},
                        {field: 'bonus_setting', title: __('Bonus_setting')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'bonus_limit', title: __('Bonus_limit')},
                        {field: 'quantity_condition', title: __('Quantity_condition')},
                        {field: 'status', title: __('Status')},
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