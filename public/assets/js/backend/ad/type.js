define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var base_data;
    $.ajax({
        url:'ad/Type/tableBaseData',
        async:false,
        success:function (data) {
            base_data = data;
        }
    });

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ad/type/index',
                    add_url: 'ad/type/add',
                    edit_url: 'ad/type/edit',
                    // del_url: 'ad/type/del',
                    // multi_url: 'ad/type/multi',
                    table: 'advertising_type',
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
                        {field: 'id', title: __('Id'),sortable: true},
                        {field: 'type', title: __('Type'),operate:'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {0:'关闭',1:'开启'}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'),sortable: true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'),sortable: true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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