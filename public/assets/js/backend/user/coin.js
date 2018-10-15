define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/coin/index',
                    add_url: 'user/coin/add',
                    edit_url: 'user/coin/edit',
                    del_url: 'user/coin/del',
                    multi_url: 'user/coin/multi',
                    table: 'user_coin',
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
                        {field: 'trade_no', title: __('Trade_no')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'type', title: __('Type')},
                        {field: 'amount', title: __('Amount')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'reason', title: __('Reason')},
                        {field: 'mission_tag', title: __('Mission_tag')},
                        {field: 'status', title: __('Status')},
                        {field: 'is_freeze', title: __('Is_freeze')},
                        {field: 'verification', title: __('Verification')},
                        {field: 'cs', title: __('Cs')},
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